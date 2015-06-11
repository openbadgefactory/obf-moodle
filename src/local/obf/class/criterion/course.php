<?php
require_once __DIR__ . '/item_base.php';
require_once __DIR__ . '/criterion.php';
require_once __DIR__ . '/../badge.php';

/**
 * Class representing a single course criterion.
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
     * Get the instance of this class by id.
     *
     * @global moodle_database $DB
     * @param int $id The id of the course criterion
     * @return obf_criterion_course
     */
    public static function get_instance($id, $method = null) {
        global $DB;

        $record = $DB->get_record('obf_criterion_courses', array('id' => $id));
        $obj = new self();

        return $obj->populate_from_record($record);
    }

    /**
     * Returns all the course criterion objects related to $criterion
     *
     * @global moodle_database $DB
     * @param obf_criterion $criterion
     * @return obf_criterion_course[]
     */
    public static function get_criterion_courses(obf_criterion $criterion) {
        global $DB;

        $records = $DB->get_records('obf_criterion_courses',
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
        $this->set_id($record->id)
                ->set_criterionid($record->obf_criterion_id)
                ->set_courseid($record->courseid)
                ->set_grade($record->grade)
                ->set_criteriatype($record->criteria_type)
                ->set_completedby($record->completed_by);

        return $this;
    }


    /**
     * Saves this course criterion to database. If it exists already, the
     * existing record will be updated.
     *
     * @global moodle_database $DB
     * @return mixed Returns this object if everything went ok, false otherwise.
     */
    public function save() {
        global $DB;

        $obj = new stdClass();
        $obj->obf_criterion_id = $this->criterionid;
        $obj->courseid = $this->courseid;
        $obj->grade = $this->has_grade() ? $this->grade : null;
        $obj->completed_by = $this->has_completion_date() ? $this->completedby : null;
        $obj->criteria_type = $this->criteriatype;

        // Updating existing record
        if ($this->id > 0) {
            $obj->id = $this->id;
            $DB->update_record('obf_criterion_courses', $obj);
        }

        // Inserting a new record
        else {
            $id = $DB->insert_record('obf_criterion_courses', $obj);

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
     * @global moodle_database $DB
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
     * @global moodle_database $DB
     */
    public function delete() {
        global $DB;

        $DB->delete_records('obf_criterion_courses', array('id' => $this->id));
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
        // First delete criterion courses
        $db->delete_records('obf_criterion_courses',
                array('courseid' => $course->id));

        // Then delete "empty" criteria (= criteria that don't have any related courses
        obf_criterion::delete_empty($db);
    }

    public function get_grade() {
        return $this->grade;
    }

    public function set_grade($grade) {
        $this->grade = $grade;
        return $this;
    }

    public function get_params() {
        global $DB;
        $params = array();
        if (!$this->exists()) {
            return $params;
        }
        $records = $DB->get_records('obf_criterion_params', array('obf_criterion_id' => $this->get_criterionid()));
        foreach ($records as $record) {
            $arr = explode('_', $record->name);
            $params[$arr[1]][$arr[0]] = $record->value;
        }
        return $params;
    }
    /**
     * Prints criteria course settings for criteria forms.
     * @param moodle_form $mform
     */
    public function get_options($mform) {
        $criterioncourseid = $this->get_id();
        $grade = $this->get_grade();
        $completedby = $this->get_completedby();

        // Minimum grade -field
        $mform->addElement('text', 'mingrade[' . $criterioncourseid . ']',
                get_string('minimumgrade', 'local_obf'));

        // Fun fact: Moodle would like the developer to call $mform->setType()
        // for every form element just in case and shows a E_NOTICE in logs
        // if it detects a missing setType-call. But if we call setType,
        // server-side validation stops working and thus makes $mform->addRule()
        // completely useless. That's why we don't call setType() here.
        //
        // ... EXCEPT that Behat-tests are failing because of the E_NOTICE, so let's add client
        // side validation + server side cleaning
        $mform->addRule('mingrade[' . $criterioncourseid . ']', null, 'numeric', null, 'client');
        $mform->setType('mingrade[' . $criterioncourseid . ']', PARAM_INT);

        if ($this->has_grade()) {
            $mform->setDefault('mingrade[' . $criterioncourseid . ']', $grade);
        }

        // Course completion date -selector. We could try naming the element
        // using array (like above), but it's broken with date_selector.
        // Instead of returning an array like it should, $form->get_data()
        // returns something like array["completedby[60]"] which is fun.
        $mform->addElement('date_selector', 'completedby_' . $criterioncourseid . '',
                get_string('coursecompletedby', 'local_obf'),
                array('optional' => true, 'startyear' => date('Y')));

        if ($this->has_completion_date()) {
            $mform->setDefault('completedby_' . $criterioncourseid, $completedby);
        }
    }
    /**
     * Prints required config fields for criteria forms.
     * @param moodle_form $mform
     */
    public function get_form_config($mform) {
        global $OUTPUT;
        $mform->addElement('hidden','criteriatype', obf_criterion_item::CRITERIA_TYPE_COURSE);
        $mform->setType('criteriatype', PARAM_INT);

        $mform->createElement('hidden','picktype', 'no');
        $mform->setType('picktype', PARAM_TEXT);
    }
    /**
     * Course criteria do support multiple courses.
     * @return boolean false
     */
    public function criteria_supports_multiple_courses() {
        return true;
    }
}
