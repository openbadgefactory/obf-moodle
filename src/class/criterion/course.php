<?php
require_once __DIR__ . '/criterion.php';
require_once __DIR__ . '/../badge.php';

/**
 * Class representing a single course criterion.
 */
class obf_criterion_course {

    /**
     * @var int The course id
     */
    protected $courseid = -1;

    /**
     * @var int The minimum grade.
     */
    protected $grade = -1;

    /**
     * @var int The completed by -field of the course criterion as a unix timestamp
     */
    protected $completedby = -1;

    /**
     * @var string For caching the name of the course
     */
    protected $coursename = '';

    /**
     * @var obf_criterion The criterion this course belongs to.
     */
    protected $criterion = null;
    protected $id = -1;
    protected $criterionid = -1;

    /**
     * Get the instance of this class by id.
     *
     * @global moodle_database $DB
     * @param int $id The id of the course criterion
     * @return obf_criterion_course
     */
    public static function get_instance($id) {
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
     * Is there a completion date in this course criterion?
     *
     * @return boolean
     */
    public function has_completion_date() {
        return (!empty($this->completedby) && $this->completedby > 0);
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
                ->set_completedby($record->completed_by);

        return $this;
    }

    /**
     * Returns the criterion related to this object.
     *
     * @return obf_criterion
     */
    public function get_criterion() {
        if (is_null($this->criterion)) {
            $this->criterion = obf_criterion::get_instance($this->criterionid);
        }

        return $this->criterion;
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

    /**
     * Checks whether this instance exists in the database.
     * 
     * @return boolean Returns true if the instance exists in the database
     *      and false otherwise.
     */
    public function exists() {
        return $this->id > 0;
    }

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function get_criterionid() {
        return $this->criterionid;
    }

    public function set_criterionid($criterionid) {
        $this->criterionid = $criterionid;
        return $this;
    }

    public function get_courseid() {
        return $this->courseid;
    }

    public function get_grade() {
        return $this->grade;
    }

    public function get_completedby() {
        return $this->completedby;
    }

    public function set_courseid($courseid) {
        $this->courseid = $courseid;
        return $this;
    }

    public function set_grade($grade) {
        $this->grade = $grade;
        return $this;
    }

    public function set_completedby($completedby) {
        $this->completedby = $completedby;
        return $this;
    }

}
