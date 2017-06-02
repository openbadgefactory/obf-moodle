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
 * Course completion criterion.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/item_base.php');
require_once(__DIR__ . '/criterion.php');
require_once(__DIR__ . '/../badge.php');

/**
 * Class representing a single course criterion.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_criterion_course extends obf_criterion_item {

    /**
     * @var int The minimum grade.
     */
    protected $grade = -1;


    /**
     * @var string For caching the name of the course
     */
    protected $coursename = '';


    /**
     * @var array[] Params cache.
     */
    protected $params = null;


    /**
     * Get the instance of this class by id.
     *
     * @param int $id The id of the course criterion
     * @param int $method
     * @return obf_criterion_course
     */
    public static function get_instance($id, $method = null) {
        global $DB;

        $record = $DB->get_record('local_obf_criterion_courses', array('id' => $id));
        $class = get_called_class();
        $obj = new $class();

        return $obj->populate_from_record($record);
    }

    /**
     * Returns all the course criterion objects related to $criterion
     *
     * @param obf_criterion $criterion
     * @return obf_criterion_course[]
     */
    public static function get_criterion_courses(obf_criterion $criterion) {
        global $DB;

        $records = $DB->get_records('local_obf_criterion_courses',
                array('obf_criterion_id' => $criterion->get_id()));
        $ret = array();

        foreach ($records as $record) {
            $obj = new self();
            $ret[] = $obj->populate_from_record($record);
        }

        return $ret;
    }

    /**
     * Is there a minimum grade defined in this course criterion?
     *
     * @return boolean
     */
    public function has_grade() {
        return (!empty($this->grade) && $this->grade > 0);
    }

    /**
     * Initializes this object with values from $record
     *
     * @param \stdClass $record The record from Moodle's database
     * @return \obf_criterion_course
     */
    public function populate_from_record(\stdClass $record) {
        $this->set_id($record->id)->set_criterionid($record->obf_criterion_id);
        $this->set_courseid($record->courseid)->set_grade($record->grade);
        $this->set_criteriatype($record->criteria_type)->set_completedby($record->completed_by);

        return $this;
    }


    /**
     * Saves this course criterion to database. If it exists already, the
     * existing record will be updated.
     *
     * @return mixed Returns this object if everything went ok, false otherwise.
     */
    public function save() {
        global $DB;

        if ($this->get_criterionid() == -1) {
            throw new Exception("Invalid criterion id", $this->get_criterionid());
        }
        $obj = new stdClass();
        $obj->obf_criterion_id = $this->criterionid;
        $obj->courseid = $this->courseid;
        $obj->grade = $this->has_grade() ? $this->grade : null;
        $obj->completed_by = $this->has_completion_date() ? $this->completedby : null;
        $obj->criteria_type = $this->criteriatype;

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
     * Returns the name of the course this criterion is related to.
     *
     * @return string The full name of the course.
     */
    public function get_coursename() {
        global $DB;

        if (empty($this->coursename)) {
            $this->coursename = $DB->get_field('course', 'fullname',
                    array('id' => $this->courseid));
        }

        return $this->coursename;
    }

    /**
     * Get name.
     * @return string
     */
    public function get_name() {
        return $this->get_coursename();
    }

    /**
     * Returns this criterion as text, including the name of the course.
     *
     * @return string
     */
    public function get_text() {
        $html = html_writer::tag('strong', $this->get_coursename());

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
     * Returns this criterion as text without the course name.
     *
     * @return string
     */
    public function get_text_for_single_course() {
        $html = get_string('toearnthisbadge', 'local_obf');

        if ($this->has_completion_date()) {
            $html .= ' ' . get_string('completedbycriterion', 'local_obf',
                            userdate($this->completedby,
                                    get_string('dateformatdate', 'local_obf')));
        }

        if ($this->has_grade()) {
            $html .= ' ' . get_string('gradecriterion', 'local_obf',
                            $this->grade);
        }

        $html .= '.';

        return $html;
    }

    /**
     * Deletes this record from the database. Also deletes the related criterion if it doesn't have
     * any courses.
     *
     */
    public function delete() {
        global $DB;

        $DB->delete_records('local_obf_criterion_courses', array('id' => $this->id));
        obf_criterion::delete_empty($DB);
    }

    /**
     * Deletes all course criterion records from the database that are related
     * to $course. Also deletes all the related criteria with no related courses
     * in them.
     *
     * @param stdClass $course The Moodle's course object
     * @param moodle_database $db The database instance
     */
    public static function delete_by_course(stdClass $course,
            moodle_database $db) {
        // First delete criterion courses.
        $db->delete_records('local_obf_criterion_courses',
                array('courseid' => $course->id));

        // Then delete "empty" criteria (= criteria that don't have any related courses.
        obf_criterion::delete_empty($db);
    }

    /**
     * Get grade.
     * @return mixed Grade
     */
    public function get_grade() {
        return $this->grade;
    }
    /**
     * Set grade.
     * @param mixed $grade
     * @return $this
     */
    public function set_grade($grade) {
        $this->grade = $grade;
        return $this;
    }


    /**
     * Reviews criteria for all applicaple users.
     *
     * @param obf_criterion $criterion The main criterion.
     * @param obf_criterion_item[] $otheritems Other items related to main criterion.
     * @param type[] $extra Extra options passed to review method.
     *      Each criterion item may save anything in $extra[$this->id].
     * @return stdClass[] Users that pass (id).
     */
    public function review($criterion = null, $otheritems = null, &$extra = array()) {
        $users = $this->get_affected_users();
        $passingusers = array();
        if (array_key_exists($this->get_id(), $extra) || !$this->is_reviewable()) {
            return $passingusers;
        }
        $extra[$this->get_id()] = 0;
        $criterion = isset($criterion) ? $criterion : $this->get_criterion();
        if (!isset($otheritems)) {
            $otheritems = $criterion->get_items();
        }
        // TODO: Remove self from $otheritems?

        foreach ($users as $user) {
            $pass = $this->review_for_user($user, $criterion, $otheritems, $extra);
            if ($pass) {
                $passingusers[$user->id] = $user;
            }
        }
        $extra[$this->get_id()] = 1;
        return $passingusers;
    }
    /**
     * Get params. Course items don't have params, but child classes might.
     * @return array
     */
    public function get_params() {
        global $DB;
        $params = array();
        if (!$this->exists()) {
            return $params;
        }
        $records = $DB->get_records('local_obf_criterion_params', array('obf_criterion_id' => $this->get_criterionid()));
        foreach ($records as $record) {
            $arr = explode('_', $record->name);
            $params[$arr[1]][$arr[0]] = $record->value;
        }
        $this->params = $params;
        return $params;
    }

    /**
     * Check if criterion item is ready to be saved for the first time,
     * assuming it will be saved with given params / request.
     *
     * @param stdClass|array $data
     */
    public function is_createable_with_params($request) {
        if ($this->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_COURSE && $this->has_courseid()) {
            $request = (array)$request;
            if (array_key_exists('createitem', $request) && $request['createitem'] == 1) {
                return true;
            }
        }
        if (property_exists($this, 'optionalparams') && property_exists($this, 'requiredparam')) {
            $data = (array)$request;
            // Filter out empty params.
            $data = array_filter($data);
            // Get params matching required params.
            $match = array_merge($this->optionalparams, array($this->requiredparam));
            $regex = implode('|', array_map(
                    function($a) {
                        return $a .'_';
                    }, $match));
            $requiredkeys = preg_grep('/^('.$regex.').*$/', array_keys($data));
            $params = array();
            foreach ($requiredkeys as $key) {
                $arr = explode('_', $key);
                $params[$arr[1]][$arr[0]] = $data[$key];
            }
            return count($params) > 0;
        }
        return false;
    }
    /**
     * Save params. (activity selections and completedby dates)
     *
     * @param stdClass|array $data
     */
    public function save_params($data) {
        global $DB;
        $this->save();

        if (!property_exists($this, 'optionalparams') || !property_exists($this, 'requiredparam')) {
            error_log('No optionalparams or requiredparams, exiting. ' . var_export(get_class($this),true));
            return;
        }

        $params = (array)$data;
        // Filter out empty params.
        $params = array_filter($params);
        // Get params matching required params.
        $match = array_merge($this->optionalparams, array($this->requiredparam));
        $regex = implode('|', array_map(
                function($a) {
                    return $a .'_';
                }, $match));
        $requiredkeys = preg_grep('/^('.$regex.').*$/', array_keys($params));
        
        $paramtable = 'local_obf_criterion_params';

        $existing = $DB->get_fieldset_select($paramtable, 'name', 'obf_criterion_id = ?', array($this->get_criterionid()));
        $todelete = array_diff($existing, $requiredkeys);
        $todelete = array_unique($todelete);
        if (!empty($todelete)) {
            list($insql, $inparams) = $DB->get_in_or_equal($todelete, SQL_PARAMS_NAMED, 'cname', true);
            $inparams = array_merge($inparams, array('critid' => $this->get_criterionid()));
            $DB->delete_records_select($paramtable, 'obf_criterion_id = :critid AND name '.$insql, $inparams );
        }
        foreach ($requiredkeys as $key) {
            if (in_array($key, $existing)) {
                $toupdate = $DB->get_record($paramtable,
                        array('obf_criterion_id' => $this->get_criterionid(),
                                'name' => $key) );
                $toupdate->value = $params[$key];
                $DB->update_record($paramtable, $toupdate, true);
            } else {
                $obj = new stdClass();
                $obj->obf_criterion_id = $this->get_criterionid();
                $obj->name = $key;
                $obj->value = $params[$key];
                $DB->insert_record($paramtable, $obj);
            }
        }
    }
    /**
     * Prints criteria course settings for criteria forms.
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj Form object.
     */
    public function get_options(&$mform, &$obj) {
        $criterioncourseid = $this->get_id();
        $courseid = $this->get_courseid();
        $grade = $this->get_grade();
        $completedby = $this->get_completedby();

        // Minimum grade -field.
        $mform->addElement('text', 'mingrade[' . $courseid . ']',
                get_string('minimumgrade', 'local_obf'));

        // Fun fact: Moodle would like the developer to call $mform->setType()
        // for every form element just in case and shows a E_NOTICE in logs
        // if it detects a missing setType-call. But if we call setType,
        // server-side validation stops working and thus makes $mform->addRule()
        // completely useless. That's why we don't call setType() here.
        //
        // ... EXCEPT that Behat-tests are failing because of the E_NOTICE, so let's add client
        // side validation + server side cleaning.
        $mform->addRule('mingrade[' . $courseid . ']', null, 'numeric', null, 'client');
        $mform->setType('mingrade[' . $courseid . ']', PARAM_INT);

        if ($this->has_grade()) {
            $mform->setDefault('mingrade[' . $courseid . ']', $grade);
        }

        // Course completion date -selector. We could try naming the element
        // using array (like above), but it's broken with date_selector.
        // Instead of returning an array like it should, $form->get_data()
        // returns something like array["completedby[60]"] which is fun.
        $mform->addElement('date_selector', 'completedby_' . $courseid . '',
                get_string('coursecompletedby', 'local_obf'),
                array('optional' => true, 'startyear' => date('Y')));

        if ($this->has_completion_date()) {
            $mform->setDefault('completedby_' . $courseid, $completedby);
        }
    }
    /**
     * Prints required config fields for criteria forms.
     *
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj Form object
     */
    public function get_form_config(&$mform, &$obj) {
        global $OUTPUT;
        $mform->addElement('hidden', 'criteriatype', obf_criterion_item::CRITERIA_TYPE_COURSE);
        $mform->setType('criteriatype', PARAM_INT);

        if (!$this->exists() && $this->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_COURSE) {
            $mform->addElement('hidden', 'createitem', 1);
            $mform->setType('createitem', PARAM_INT);

        }

        $mform->createElement('hidden', 'picktype', 'no');
        $mform->setType('picktype', PARAM_TEXT);
    }

    /**
     * Prints completion options to form.
     *
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj Form object
     * @param mixed[] $items
     */
    public function get_form_completion_options(&$mform, $obj = null, $items = null) {
        if ($this->get_criterion()) {
            // Radiobuttons to select whether this criterion is completed
            // when any of the courses are completed or all of them.
            $itemcount = !is_null($items) ? count($items) : count($this->get_criterion()->get_items());
            if ($itemcount > 1) {
                $radiobuttons = array();
                $radiobuttons[] = $mform->createElement('radio', 'completion_method', '',
                        get_string('criteriacompletionmethodall', 'local_obf'),
                        obf_criterion::CRITERIA_COMPLETION_ALL);
                $radiobuttons[] = $mform->createElement('radio', 'completion_method', '',
                        get_string('criteriacompletionmethodany', 'local_obf'),
                        obf_criterion::CRITERIA_COMPLETION_ANY);

                $mform->addElement('header', 'header_completion_method',
                        get_string('criteriacompletedwhen', 'local_obf'));
                $obj->setExpanded($mform, 'header_completion_method');
                $mform->addGroup($radiobuttons, 'radioar', '', '<br />', false);
                $mform->setDefault('completion_method', $this->get_criterion()->get_completion_method());
            }
        }
    }
    /**
     * Prints after save options to form.
     *
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj Form object
     */
    public function get_form_after_save_options(&$mform, &$obj) {
        global $OUTPUT;
        if ($this->show_review_options()) {
            $mform->addElement('header', 'header_review_criterion_after_save',
                    get_string('reviewcriterionaftersave', 'local_obf'));
            $obj->setExpanded($mform, 'header_review_criterion_after_save');
            $mform->addElement('html',
                    $OUTPUT->notification(get_string('warningcannoteditafterreview', 'local_obf')));
            $mform->addElement('advcheckbox', 'reviewaftersave', get_string('reviewcriterionaftersave', 'local_obf'));
            $mform->addHelpButton('reviewaftersave', 'reviewcriterionaftersave', 'local_obf');

        }
    }
    
    /**
     * Prints criteria addendum options to form.
     *
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj Form object
     */
    public function get_form_criteria_addendum_options(&$mform, &$obj) {
        global $OUTPUT;
        if ($this->show_criteria_addendum_options()) {
            $mform->addElement('header', 'header_criteria_addendum',
                    get_string('criteriaaddendumheader', 'local_obf'));
            
            $criterion = $obj->get_criterion();
            $addendum = !empty($criterion) ? $criterion->get_criteria_addendum() : '';
            $useaddendum = !empty($criterion) ? $criterion->get_use_addendum() : false;
            
            if (!empty($addendum)) {
                $obj->setExpanded($mform, 'header_criteria_addendum');
            }
            
            $mform->addElement('advcheckbox', 'addcriteriaaddendum', get_string('criteriaaddendumadd', 'local_obf'));
            $mform->addElement('textarea', 'criteriaaddendum', get_string('criteriaaddendum', 'local_obf'));
            //$mform->setType('criteriaaddendum', PARAM_RAW);
            $mform->addHelpButton('criteriaaddendum', 'criteriaaddendum', 'local_obf');
            $mform->setDefaults(array('criteriaaddendum' => $addendum, 'addcriteriaaddendum' => $useaddendum));
            //$mform->setDefaults(array('criteriaaddendum' => array('text' => $addendum, 'format' => FORMAT_MARKDOWN), 'addcriteriaaddendum' => $useaddendum));
        }
    }

    /**
     * Return all form field names and types, that need to be present on a form,
     * to make sure form->get_data works.
     *
     * @return array
     */
    public function get_form_fields() {
        $fields = array(
                'criteriatype' => PARAM_INT,
                'course[]' => PARAM_RAW,
                'completedby[]' => PARAM_RAW,
                'mingrade[]' => PARAM_INT
        );
        if ($this->has_courseid()) {
            $fields[] = 'completedby_'.$this->get_courseid();
        }
        return $fields;
    }
    /**
     * Course criteria do support multiple courses.
     * @return boolean false
     */
    public function criteria_supports_multiple_courses() {
        return true;
    }
    /**
     * Returns users related to this criteria.
     *
     * @return stdClass[] Users enrolled in courses or somehow related to criteria. (id and email)
     */
    protected function get_affected_users() {
        $context = context_course::instance($this->get_courseid());
        // The all users that are (and were?) enrolled to this course with
        // the capability of earning badges.
        $users = get_enrolled_users($context, 'local/obf:earnbadge', 0,
                'u.id, u.email');
        return $users;
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

        if ($this->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_COURSE) {
            if (!$completioninfo->is_course_complete($userid)) {
                return false;
            }
        } else {
            throw new Exception("Exception in review_for_user criteriatype.", $this->get_criteriatype());
        }

        $datepassed = false;
        $gradepassed = false;
        $completion = new completion_completion(array('userid' => $userid, 'course' => $courseid));
        $completedat = $completion->timecompleted;

        // Check completion date.
        if ($this->has_completion_date()) {
            if ($completedat <= $this->get_completedby()) {
                $datepassed = true;
            }
        } else {
            $datepassed = true;
        }

        // Check grade.
        if ($this->has_grade()) {
            $grade = grade_get_course_grade($userid, $courseid);

            if (!is_null($grade->grade) && $grade->grade >= $this->get_grade()) {
                $gradepassed = true;
            }
        } else {
            $gradepassed = true;
        }

        if (!($datepassed && $gradepassed)) {
            return false;
        }

        return $coursecompleted;
    }
    /**
     * To show review options or not?
     * @return bool True if options should be shown. False otherwise.
     */
    protected function show_review_options() {
        return $this->courseid != -1 && $this->criteriatype != self::CRITERIA_TYPE_UNKNOWN;
    }
    
    /**
     * To show criteria addendum options or not?
     * @return bool True if options should be shown. False otherwise.
     */
    protected function show_criteria_addendum_options() {
        return $this->courseid != -1 && $this->criteriatype != self::CRITERIA_TYPE_UNKNOWN;
    }
}
