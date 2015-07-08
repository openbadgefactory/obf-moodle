<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Totara program and certificate completion criterion.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
require_once(__DIR__ . '/item_base.php');

require_once($CFG->dirroot . '/user/lib.php');


/**
 * Totara program and certificate completion criterion -class.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_criterion_totaraprogram extends obf_criterion_course {
    /**
     * @var $criteriatype Set to self::CRITERIA_TYPE_TOTARA_PROGRAM
     */
    protected $criteriatype = obf_criterion_item::CRITERIA_TYPE_TOTARA_PROGRAM;

    /**
     * @var string $requiredparam
     * @see obf_criterion_course::save_params
     */
    protected $requiredparam = 'program';
    /**
     * @var string[] $optionalparams Optional params to be saved.
     * @see obf_criterion_course::save_params
     */
    protected $optionalparams = array('completedby', 'expiresbycertificate');
    /**
     * @var $programscache Programs cache.
     */
    protected $programscache = array();
    /**
     * @var $certexpirescache Certificate expiration time timestamp may be stored here.
     */
    protected $certexpirescache = null;


    /**
     * Get the instance of this class by id.
     *
     * @param int $id The id of the activity criterion
     * @param int $method
     * @return obf_criterion_activity
     */
    public static function get_instance($id, $method = null) {
        global $DB;

        $record = $DB->get_record('local_obf_criterion_courses', array('id' => $id));
        $obj = new self();
        if ($record) {
            return $obj->populate_from_record($record);
        } else {
            throw new Exception("Trying to get criterion item instance that does not exist.", $id);
        }
        return false;
    }

    /**
     * Initializes this object with values from $record
     *
     * @param \stdClass $record The record from Moodle's database
     * @return \obf_criterion_activity
     */
    public function populate_from_record(\stdClass $record) {
        $this->set_id($record->id)->set_criterionid($record->obf_criterion_id);
        $this->set_courseid($record->courseid)->set_completedby($record->completed_by);
        $this->set_criteriatype($record->criteria_type);
        // TODO:  Params?
        return $this;
    }



    /**
     * Returns the name of the activity this criterion is related to.
     *
     * @param int[] $programids List if ids
     * @return stdClass[] Programs matching ids.
     */
    public static function get_programs_by_id($programids) {
        global $DB;

        $ret = array();
        list($insql, $inparams) = $DB->get_in_or_equal($programids, SQL_PARAMS_NAMED, 'progid');
        $sql = "SELECT * FROM {prog} WHERE id " . $insql;
        $records = $DB->get_records_sql($sql, $inparams);
        foreach ($records as $record) {
            $ret[] = $record;
        }
        return $ret;
    }
    /**
     * Get program matchind $progid.
     * @param int $progid
     * @return program Totara program
     */
    public function get_program_from_cache($progid) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/program/program.class.php');
        if (array_key_exists($progid, $this->programscache)) {
            return $this->programscache[$progid];
        }
        try {
            $program = new program($progid);
            $this->programscache[$progid] = $program;
            return $this->programscache[$progid];
        } catch (Exception $e) {
            debugging($e->getMessage());
        }
        return false;
    }



    /**
     * Returns all programs.
     *
     * @return stdClass[] Programs.
     */
    public static function get_all_programs() {
        global $DB;

        $ret = array();
        $records = $DB->get_records('prog');
        foreach ($records as $record) {
            $ret[$record->id] = $record;
        }
        return $ret;
    }
    /**
     * Get program ids this criterion has completion settings for.
     * @return int[] Program IDs
     */
    public function get_programids() {
        $params = $this->get_params();
        return array_keys(array_filter($params, function ($v) {
            return array_key_exists('program', $v) ? true : false;
        }));
    }
    /**
     * Get users this criterion may have effect on.
     * @return stdClass[]
     */
    protected function get_affected_users() {
        // The $ASSIGNMENT_CATEGORY_CLASSNAMES variable is a global totara variable.
        global $DB, $CFG, $ASSIGNMENT_CATEGORY_CLASSNAMES;
        require_once($CFG->dirroot . '/totara/program/program.class.php');
        $programids = $this->get_programids();
        $users = array();
        foreach ($programids as $programid) {
            $program = $this->get_program_from_cache($programid);
            $progassignments = $program->get_assignments();
            if ($progassignments) {
                $assignments = $progassignments->get_assignments();
                foreach ($assignments as $assignment) {
                    $assignmentsclass = new $ASSIGNMENT_CATEGORY_CLASSNAMES[$assignment->assignmenttype]();
                    $affectedusers = $assignmentsclass->get_affected_users_by_assignment($assignment);
                    foreach ($affectedusers as $user) {
                        $users[$user->id] = $user;
                    }
                }
            }
        }
        // Also get email-addresses for users, as they are needed when issuing badges.
        $userids = array_map(
                function ($u) {
                    return $u->id;
                }, $users);
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'user');
        $sql = "SELECT u.id, u.email FROM {user} AS u WHERE id " . $insql;
        $records = $DB->get_records_sql($sql, $inparams);
        foreach ($records as $record) {
            $users[$record->id]->email = $record->email;
        }
        return array_unique($users, SORT_REGULAR);
    }
    /**
     * Reviews criteria for single user.
     *
     * @param stdClass $user
     * @param obf_criterion $criterion The main criterion.
     * @param obf_criterion_item[] $otheritems Other items related to main criterion.
     * @param array& $extra Extra options passed to review method.
     * @return boolean If the course criterion is completed by the user.
     */
    protected function review_for_user($user, $criterion = null, $otheritems = null, &$extra = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/program/program.class.php');
        require_once($CFG->dirroot . '/grade/querylib.php');
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->libdir . '/completionlib.php');

        $userid = $user->id;

        $programids = $this->get_programids();
        $criterion = !is_null($criterion) ? $criterion : $this->get_criterion();
        $requireall = $criterion->get_completion_method() == obf_criterion::CRITERIA_COMPLETION_ALL;
        $programscomplete = $requireall; // Default to true when requiring completion of all programs, false if completion of any.
        $completedat = false;
        $completedprogramcount = 0;
        foreach ($programids as $programid) {
            $program = $this->get_program_from_cache($programid);
            $certexpires = null;
            if ($this->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_TOTARA_CERTIF) {
                $certifid = $program->certifid;
                $progcompletionrecord = $DB->get_record('certif_completion', array('certifid' => $certifid, 'userid' => $userid));
                $completedat = $progcompletionrecord ? $progcompletionrecord->timecompleted : time();
                $progcomplete = $progcompletionrecord && $progcompletionrecord->status == CERTIFSTATUS_COMPLETED;

                if ($progcomplete) {
                    $certification = $DB->get_record('certif', array('id' => $certifid));
                    $lastcompleted = certif_get_content_completion_time($certifid, $userid);
                    $certiftimebase = get_certiftimebase($certification->recertifydatetype,
                            $progcompletionrecord->timeexpires, $lastcompleted);
                    $certexpires = get_timeexpires($certiftimebase, $certification->activeperiod);
                }
            } else {
                $progcompletionrecord = $DB->get_record('prog_completion',
                        array('programid' => $programid, 'userid' => $userid, 'coursesetid' => 0));
                $completedat = $progcompletionrecord ? $progcompletionrecord->timecompleted : time();
                $progcomplete = $progcompletionrecord && $progcompletionrecord->status == STATUS_PROGRAM_COMPLETE;
            }
            if (!$progcomplete) {
                if ($requireall) {
                    return false;
                }
            } else { // User has completed program.
                $dateok = !$this->has_prog_completedby($programid) ||
                        $completedat <= $this->get_prog_completedby($programid);
                if (!$dateok) {
                    if ($requireall) {
                        return false;
                    }
                } else {
                    if (!is_null($certexpires)) {
                        $oldval = !is_null($this->certexpirescache) ? $this->certexpirescache : 0;
                        $newval = max($certexpires, $oldval);
                        if ($newval != 0) {
                            $this->certexpirescache = $newval;
                        }
                    }
                    $completedprogramcount += 1;
                }
            }
        }

        if ($completedprogramcount < 1) {
            return false;
        }

        return true;
    }
    /**
     * Get expires date that overrides expiration date set on badge settings.
     *
     * Certificates have expiration dates that may override badge expiration dates.
     *
     * @param stdClass $user
     * @return Expires by time in unix-timestamp format
     */
    public function get_issue_expires_override($user = null) {
        if ($this->get_expires_method() == obf_criterion_item::EXPIRY_DATE_CUSTOM) {
            return $this->certexpirescache;
        } else {
            return null;
        }
    }
    /**
     * Get the method used for expiration dates.
     *
     * @return int obf_criterion_item::EXPIRY_DATE_CUSTOM or
     *         obf_criterion_item::EXPIRY_DATE_BADGE.
     */
    public function get_expires_method() {
        $params = $this->get_params();
        if (array_key_exists('global', $params) &&
                array_key_exists('expiresbycertificate', $params['global'])) {
            return $params['global']['expiresbycertificate'];
        }
        return obf_criterion_item::EXPIRY_DATE_CUSTOM;
    }
    /**
     * Does a program have a completed by setting set.
     * @param int $programid
     * @return bool if Program needs to be completed by date.
     */
    protected function has_prog_completedby($programid) {
        $params = $this->get_params();
        $progparams = array_key_exists($programid, $params) ? $params[$programid] : array();
        return array_key_exists('completedby', $progparams);
    }
    /**
     * Does a program have a completed by setting set.
     *
     * @param int $programid
     * @return int Program's completed by date or -1 if not set.
     */
    protected function get_prog_completedby($programid) {
        $params = $this->get_params();
        $progparams = array_key_exists($programid, $params) ? $params[$programid] : array();
        return array_key_exists('completedby', $progparams) ? $progparams['completedby'] : -1;
    }
    /**
     * Get name. This should not be visible anywhere.
     * @return string
     */
    public function get_name() {
        return get_string('totaraprogram', 'local_obf');
    }
    /**
     * Returns this criterion as text, including the name of the course.
     *
     * @return string
     */
    public function get_text() {
        $html = html_writer::tag('strong', $this->get_name());

        if ($this->has_completion_date()) {
            $html .= ' ' . get_string('completedbycriterion', 'local_obf',
                            userdate($this->completedby,
                                    get_string('dateformatdate', 'local_obf')));
        }

        if ($this->has_grade()) {
            $html .= ' ' . get_string('gradecriterion', 'local_obf',
                            $this->grade);
        }

        return $html;
    }
    /**
     * Get text array to be printed on badge awarding rules -page.
     * @return array html encoded activity descriptions.
     */
    public function get_text_array() {
        $texts = array();
        $programids = $this->get_programids();
        if (count($programids) == 0) {
            return $texts;
        }
        $programs = self::get_programs_by_id($programids);
        $params = $this->get_params();
        foreach ($programs as $program) {
            $html = html_writer::tag('strong', $program->fullname);
            if (array_key_exists('completedby', $params[$program->id])) {
                $html .= ' ' . get_string('completedbycriterion', 'local_obf',
                                userdate($params[$program->id]['completedby'],
                                        get_string('dateformatdate', 'local_obf')));
            }
            $texts[] = $html;
        }
        return $texts;
    }
    /**
     * Check if $field is a required field.
     * @param strign $field
     * @return bool True if required.
     */
    public function requires_field($field) {
        return in_array($field, array_merge(array('criterionid')));
    }
    /**
     * Check if criteria is reviewable.
     * @return bool True if reviewable.
     */
    public function is_reviewable() {
        return $this->criterionid != -1 && count($this->get_programids()) > 0 &&
                $this->criteriatype != obf_criterion_item::CRITERIA_TYPE_UNKNOWN;
    }
    /**
     * Show review options?
     * @return bool Should be true,
     *         unless something is broken and criterion id is not there.
     */
    protected function show_review_options() {
        return $this->criterionid != -1;
    }

    /**
     * Print activities to form.
     * @param MoodleQuickForm& $mform
     * @param program[] $programs programs so the database is not accessed too much
     * @param array $params
     */
    private function get_form_programs(&$mform, $programs, $params) {
        $mform->addElement('html', html_writer::tag('p', get_string('selectprogram', 'local_obf')));

        $existing = array();
        $completedby = array_map(
                function($a) {
                    if (array_key_exists('completedby', $a)) {
                        return $a['completedby'];
                    }
                    return false;
                }, $params);
        foreach ($params as $key => $param) {
            if (array_key_exists('program', $param)) {
                $existing[] = $param['program'];
            }
        }

        foreach ($programs as $key => $prog) {
            $mform->addElement('advcheckbox', 'program_' . $key,
                    $prog->fullname, null, array('group' => 1), array(0, $key));
            $mform->addElement('date_selector', 'completedby_' . $key,
                    get_string('activitycompletedby', 'local_obf'),
                    array('optional' => true, 'startyear' => date('Y')));
        }
        foreach ($existing as $progid) {
            $mform->setDefault('program_'.$progid, $progid);
        }
        foreach ($completedby as $key => $value) {
            $mform->setDefault('completedby_'.$key, $value);
        }
    }
    /**
     * Prints criteria activity settings for criteria forms.
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj
     */
    public function get_options(&$mform, &$obj) {
        global $OUTPUT;

        $programs = self::get_all_programs();
        $params = $this->get_params();

        $this->get_form_programs($mform, $programs, $params);
    }

    /**
     * Prints required config fields for criteria forms.
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj
     */
    public function get_form_config(&$mform, &$obj) {
        global $OUTPUT;
        $crittype = $this->get_criteriatype();

        $mform->addElement('hidden', 'criteriatype', $crittype);
        $mform->setType('criteriatype', PARAM_INT);

        $mform->createElement('hidden', 'picktype', 'no');
        $mform->setType('picktype', PARAM_TEXT);

        if ($this->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_TOTARA_CERTIF) {
            $mform->addElement('header', 'header_totaraprogramselectexpires',
                    get_string('totaraprogramselectexpires', 'local_obf'));

            $radiobuttons = array();
            $radiobuttons[] = $mform->createElement('radio', 'expiresbycertificate_global', '',
                    get_string('totaraprogramexpiresbycertificate', 'local_obf'),
                    obf_criterion_item::EXPIRY_DATE_CUSTOM);
            $radiobuttons[] = $mform->createElement('radio', 'expiresbycertificate_global', '',
                    get_string('totaraprogramexpiresbybadge', 'local_obf'),
                    obf_criterion_item::EXPIRY_DATE_BADGE);
            $mform->addGroup($radiobuttons, 'radioar', '', '<br />', false);

            $mform->setDefault('expiresbycertificate_global', $this->get_expires_method());

            $obj->setExpanded($mform, 'header_totaraprogramselectexpires', true);
        }
    }
    /**
     * Activities do not support multiple courses.
     * @return boolean false
     */
    public function criteria_supports_multiple_courses() {
        return false;
    }
}
