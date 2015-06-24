<?php
require_once __DIR__ . '/criterion.php';

abstract class obf_criterion_item {

    /**
     * Criteria that does nothing, but help set up new criteria
     */
    const CRITERIA_TYPE_UNKNOWN = 0;
    const CRITERIA_TYPE_ANY = 0; // For internal filtering purposes
    /**
     * Criteria is associated to courses
     */
    const CRITERIA_TYPE_COURSE = 1;
    /**
     * Criteria is associated to activities
     */
    const CRITERIA_TYPE_ACTIVITY = 2;

    /**
     * Criteria is associated to a totara program or certificate.
     */
    const CRITERIA_TYPE_TOTARA_PROGRAM = 5;
    const CRITERIA_TYPE_TOTARA_CERTIF = 6;

    /**
     * @var int The completed by -field of the course criterion as a unix timestamp
     */

    protected $completedby = -1;
    /**
     * @var int The course id
     */
    protected $courseid = -1;

    /**
     *
     */
    protected $criteriatype = obf_criterion_item::CRITERIA_TYPE_UNKNOWN;

    /**
     * @var obf_criterion The criterion this course belongs to.
     */
    protected $criterion = null;
    protected $id = -1;
    protected $criterionid = -1;

    private static $OBF_BADGE_CRITERIA_TYPE_CLASSES = array(
        obf_criterion_item::CRITERIA_TYPE_UNKNOWN => 'unknown',
        obf_criterion_item::CRITERIA_TYPE_COURSE => 'course',
        obf_criterion_item::CRITERIA_TYPE_ACTIVITY => 'activity',
        obf_criterion_item::CRITERIA_TYPE_TOTARA_PROGRAM => 'totaraprogram',
        obf_criterion_item::CRITERIA_TYPE_TOTARA_CERTIF => 'totaraprogram'
    );

    public function __construct($params = null) {
        $this->id = isset($params['id']) ? $params['id'] : -1;
        $this->courseid = isset($params['courseid']) ? $params['courseid'] : -1;
        $this->criteriatype = isset($params['criteriatype']) ? $params['criteriatype'] : obf_criterion_item::CRITERIA_TYPE_UNKNOWN;
        if (isset($params['criterionid'])) {
            $this->criterion = $this->get_criterion($params['criterionid']);
        }
    }
    /**
     * Build criteria object based on params.
     * @param type $params
     * @return mixed obf_criterion_(course|activity|unknown)
     */
    public static function build($params) {
        $typeid = $params['criteriatype'];
        if (!isset($typeid) || !isset(self::$OBF_BADGE_CRITERIA_TYPE_CLASSES[$typeid])) {
            throw new Exception("Error Building criterion." . $params['criteriatype']);
        }
        $type = self::$OBF_BADGE_CRITERIA_TYPE_CLASSES[$typeid];
        $class = 'obf_criterion_' . $type;
        require_once(__DIR__ . '/' . $type . '.php');
        return new $class($params);
    }
    /**
     * Return a criteria item with the right class.
     * @param type $type Criteria type (obf_criterion_item::CRITERIA_TYPE_*)
     * @return mixed obf_criterion_(course|activity|unknown)
     */
    public static function build_type($type) {
        return self::build(array('criteriatype' => $type));
    }
    /**
     * @param type $type Criteria type (obf_criterion_item::CRITERIA_TYPE_*)
     * @return string Text repesantation (course|activity|unknown)
     */
    public static function get_criterion_type_text($type) {
        return self::$OBF_BADGE_CRITERIA_TYPE_CLASSES[$type];
    }
    public static function get_instance($instanceid, $method = null) {
        if (is_null($method)) {
            global $DB;
            $record = $DB->get_record('obf_criterion_courses', array('id' => $instanceid));
            $method = !empty($record) ? $record->criteria_type : 0;
        }
        $type = self::get_criterion_type_text($method);
        $class = 'obf_criterion_' . $type;
        require_once(__DIR__ . '/' . $type . '.php');
        return $class::get_instance($instanceid);
    }
    /**
     * Returns all the course criterion objects related to $criterion
     *
     * @global moodle_database $DB
     * @param obf_criterion $criterion
     * @return obf_criterion_course[]
     */
    public static function get_criterion_items(obf_criterion $criterion) {
        global $DB;

        $records = $DB->get_records('obf_criterion_courses',
                array('obf_criterion_id' => $criterion->get_id()));
        $ret = array();

        foreach ($records as $record) {
            $obj = self::build(array('criteriatype' => $record->criteria_type));
            $ret[] = $obj->populate_from_record($record);
        }

        return $ret;
    }


    /**
     * Common get/set functions.
     */

    /**
     * Checks whether this instance exists in the database.
     *
     * @return boolean Returns true if the instance exists in the database
     *      and false otherwise.
     */
    public function exists() {
        return $this->id > 0;
    }
    /**
     * Checks that criteria is reviewable and we should
     * show review after save settings on forms.
     */
    public function is_reviewable() {
        return $this->courseid != -1 && $this->criterionid != -1 &&
                $this->criteriatype != obf_criterion_item::CRITERIA_TYPE_UNKNOWN;
    }
    public function requires_field($field) {
        return in_array($field, array('courseid','criterionid'));
    }
    public function get_id() {
        return $this->id;
    }
    //Criteria may override badge expires settings.
    public function get_issue_expires_override($user = null) {
        return null;
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

    public function set_courseid($courseid) {
        $this->courseid = $courseid;
        return $this;
    }
    public function get_courseid()
    {
        return $this->courseid;
    }
    public function has_courseid() {
        return !empty($this->courseid) && $this->courseid > 0;
    }
    public function get_criteriatype() {
        return $this->criteriatype;
    }
    public function set_criteriatype($type) {
        $this->criteriatype = $type;
        return $this;
    }
    public function get_completedby() {
        return $this->completedby;
    }

    public function set_completedby($completedby) {
        $this->completedby = $completedby;
        return $this;
    }
    /**
     * Is there a completion date in this course criterion?
     *
     * @return boolean
     */
    public function has_completion_date() {
        return (!empty($this->completedby) && $this->completedby > 0);
    }
    public function criteria_supports_multiple_courses() {
        return false;
    }
    abstract public function save();
    abstract public function get_name();
    abstract public function get_text();
    public function get_text_array() {
        return array($this->get_text());
    }
    abstract public function delete();
}
