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
 * Activity completion critrion.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/item_base.php');
require_once(__DIR__ . '/criterion.php');
require_once(__DIR__ . '/course.php');
require_once(__DIR__ . '/../badge.php');

/**
 * Class representing a activity criterion.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_criterion_activity extends obf_criterion_course {
    /**
     * @var $criteriatype Set to self::CRITERIA_TYPE_ACTIVITY
     */
    protected $criteriatype = obf_criterion_item::CRITERIA_TYPE_ACTIVITY;
    /**
     * @var string $requiredparam
     * @see obf_criterion_course::save_params
     */
    protected $requiredparam = 'module';
    /**
     * @var string[] $optionalparams Optional params to be saved.
     * @see obf_criterion_course::save_params
     */
    protected $optionalparams = array('completedby');
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

        return $obj->populate_from_record($record);
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
        // TODO:  Populate params?
        return $this;
    }

    /**
     * Saves this activity criterion to database. If it exists already, the
     * existing record will be updated.
     *
     * @return mixed Returns this object if everything went ok, false otherwise.
     */
    public function save() {
        global $DB;

        $obj = new stdClass();
        $obj->obf_criterion_id = $this->criterionid;
        $obj->courseid = $this->courseid;
        $obj->completed_by = $this->has_completion_date() ? $this->completedby : null;
        $obj->criteria_type = $this->get_criteriatype();

        // Updating existing record.
        if ($this->id > 0) {
            $obj->id = $this->id;
            $DB->update_record('local_obf_criterion_courses', $obj);
        } else { // Inserting a new record.
            $id = $DB->insert_record('local_obf_criterion_courses', $obj);

            if (!$id) {
                return false;
            }

            $this->set_id($id);
        }

        return $this;
    }

    /**
     * Returns the name of the activity this criterion is related to.
     *
     * @param int $cminstance Course module instance id
     * @return string The full name of the activity.
     */
    public function get_activityname($cminstance) {
        global $DB;

        $activityname = '';
        $cmrecord = $DB->get_record('course_modules', array('id' => $cminstance));
        if ($cmrecord) {
            $modulename = $DB->get_field('modules', 'name',
                    array('id' => $cmrecord->module));
            $activityname = $DB->get_field($modulename, 'name', array('id' => $cmrecord->instance));
        }
        return $activityname;
    }
    /**
     * Get name.
     * @return string
     */
    public function get_name() {
        $params = $this->get_params();
        $name = '';
        foreach ($params as $key => $param) {
            if (array_key_exists('module', $param)) {
                $cminstance = $param['module'];
                $name .= (empty($name) ? '' : ', ') . $this->get_activityname($cminstance);
            }
        }
        return $name;
    }

    /**
     * Get course activities.
     * @param int $courseid
     * @return stdClass[] Activities
     */
    public static function get_course_activities($courseid) {
        global $DB;

        $activities = array();
        $cmrecords = $DB->get_records('course_modules', array('course' => $courseid));
        foreach ($cmrecords as $cmrecord) {
            $modulename = $DB->get_field('modules', 'name', array('id' => $cmrecord->module));
            $activities[$cmrecord->id] = $DB->get_field($modulename, 'name', array('id' => $cmrecord->instance));
        }
        return $activities;
    }

    /**
     * Returns this criterion as text, including the name of the activity.
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

        return $html;
    }
    /**
     * Get an array of activity names.
     * @return array html encoded activity descriptions.
     */
    public function get_text_array() {
        $params = $this->get_params();
        $modids = self::get_module_instanceids_from_params($params);
        $texts = array();
        foreach ($modids as $modid) {
            $html = html_writer::tag('strong', $this->get_activityname($modid));
            if (array_key_exists('completedby', $params[$modid])) {
                $html .= ' ' . get_string('completedbycriterion', 'local_obf',
                                userdate($params[$modid]['completedby'],
                                        get_string('dateformatdate', 'local_obf')));
            }
            $texts[] = $html;
        }
        return $texts;
    }

    /**
     * Returns this criterion as text without the activity name.
     *
     * @return string
     */
    public function get_text_for_single_activity() {
        $html = get_string('toearnthisbadge', 'local_obf');

        if ($this->has_completion_date()) {
            $html .= ' ' . get_string('completedbycriterion', 'local_obf',
                            userdate($this->completedby,
                                    get_string('dateformatdate', 'local_obf')));
        }

        $html .= '.';

        return $html;
    }
    /**
     * Prints criteria activity settings for criteria forms.
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj
     */
    public function get_options(&$mform, &$obj) {
        global $OUTPUT;

        $modules = self::get_course_activities($this->get_courseid());
        $params = $this->get_params();

        $this->get_form_activities($mform, $modules, $params);
    }
    /**
     * Prints required config fields for criteria forms.
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj
     */
    public function get_form_config(&$mform, &$obj) {
        global $OUTPUT;
        $mform->addElement('hidden', 'criteriatype', obf_criterion_item::CRITERIA_TYPE_ACTIVITY);
        $mform->setType('criteriatype', PARAM_INT);

        $mform->createElement('hidden', 'picktype', 'no');
        $mform->setType('picktype', PARAM_TEXT);
    }
    /**
     * Activities do not support multiple courses.
     * @return boolean false
     */
    public function criteria_supports_multiple_courses() {
        return false;
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
        require_once($CFG->dirroot . '/grade/querylib.php');
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->libdir . '/completionlib.php');

        $requireall = $criterion->get_completion_method() == obf_criterion::CRITERIA_COMPLETION_ALL;

        $criterioncompleted = false;

        $coursecompleted = true;

        $userid = $user->id;
        $courseid = $this->get_courseid();
        $course = $criterion->get_course($courseid);
        $completioninfo = new completion_info($course);

        $params = $this->get_params();
        $modules = array_keys(array_filter($params, function ($v) {
            return array_key_exists('module', $v) ? true : false;
        }));
        $completedmodulecount = 0;

        foreach ($modules as $modid) {
            $cm = $DB->get_record('course_modules', array('id' => $modid));
            $completiondata = $completioninfo->get_data($cm, false, $userid);

            $modulecomplete = in_array($completiondata->completionstate, array(COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS));
            $datepassed = false;
            $completedby = array_key_exists('completedby', $params[$modid]) ? $params[$modid][completedby] : null;
            // Check completion date.
            if (!is_null($completedby)) {
                if ($completioninfo->timemodified <= $completedby) {
                    $datepassed = true;
                }
            } else {
                $datepassed = true;
            }

            if ($modulecomplete && $datepassed) {
                $completedmodulecount += 1;
            } else if ($requireall) {
                return false;
            }

        }
        if ($completedmodulecount < 1) {
            return false;
        }

        return true;
    }
    /**
     * Print activities to form.
     * @param MoodleQuickForm& $mform
     * @param array $modules modules so the database is not accessed too much
     * @param array $params
     */
    private function get_form_activities(&$mform, $modules, $params) {
        $mform->addElement('html', html_writer::tag('p', get_string('selectactivity', 'local_obf')));

        $existing = array();
        $completedby = array_map(
                function($a) {
                    if (array_key_exists('completedby', $a)) {
                            return $a['completedby'];
                    }
                    return false;
                }, $params);
        foreach ($params as $key => $param) {
            if (array_key_exists('module', $param)) {
                $existing[] = $param['module'];
            }
        }

        foreach ($modules as $key => $mod) {
            $mform->addElement('advcheckbox', 'module_' . $key,
                    $mod, null, array('group' => 1), array(0, $key));
            $mform->addElement('date_selector', 'completedby_' . $key,
                    get_string('activitycompletedby', 'local_obf'),
                    array('optional' => true, 'startyear' => date('Y')));
        }
        foreach ($existing as $modid) {
            $mform->setDefault('module_'.$modid, $modid);
        }
        foreach ($completedby as $key => $value) {
            $mform->setDefault('completedby_'.$key, $value);
        }
    }
    /**
     * Get module instance ids this activity criterion item is asscociated to.
     * @param array $params
     * @return array ids of activity instances
     */
    private static function get_module_instanceids_from_params($params) {
        $ids = array();
        foreach ($params as $key => $param) {
            if (array_key_exists('module', $param)) {
                $ids[] = $param['module'];
            }
        }
        return $ids;
    }
}
