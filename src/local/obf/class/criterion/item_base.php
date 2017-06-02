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
 * Abstract criterion item base.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/criterion.php');

/**
 * Abstract criterion item base.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class obf_criterion_item {

    /**
     * @var Criteria that does nothing, but help set up new criteria.
     */
    const CRITERIA_TYPE_UNKNOWN = 0;
    /**
     * @var Criteria that does nothing, but help set up new criteria.
     */
    const CRITERIA_TYPE_ANY = 0; // For internal filtering purposes.
    /**
     * Criteria is associated to courses
     */
    const CRITERIA_TYPE_COURSE = 1;
    /**
     * Criteria is associated to activities.
     */
    const CRITERIA_TYPE_ACTIVITY = 2;
    /**
     * Criteria is associated to user profile
     */
    const CRITERIA_TYPE_PROFILE = 3;


    /**
     * Criteria is associated with a totara program.
     */
    const CRITERIA_TYPE_TOTARA_PROGRAM = 5;
    /**
     * Criteria is associated with a totara certificate.
     */
    const CRITERIA_TYPE_TOTARA_CERTIF = 6;

    /**
     * Awarded badge issue dates are set according to normal badge settings.
     */
    const EXPIRY_DATE_BADGE = 1;
    /**
     * Awarded badge issue dates are set according to criterias custom settings.
     */
    const EXPIRY_DATE_CUSTOM = 2;


    /**
     * @var int The completed by -field of the course criterion as a unix timestamp.
     */
    protected $completedby = -1;
    /**
     * @var int The course id
     */
    protected $courseid = -1;

    /**
     * @var $criteriatype Set to self::CRITERIA_TYPE_*
     */
    protected $criteriatype = self::CRITERIA_TYPE_UNKNOWN;

    /**
     * @var obf_criterion The criterion this course belongs to.
     */
    protected $criterion = null;
    /**
     * @var int ID in the database.
     */
    protected $id = -1;
    /**
     * @var int Criterion ID the item is attached to.
     * @see obf_criterion
     */
    protected $criterionid = -1;

    /**
     * @var array $obfbadgecriteriatypeclasses Criteria type classes, for dynamic class loading.
     */
    private static $obfbadgecriteriatypeclasses = array(
        self::CRITERIA_TYPE_UNKNOWN => 'unknown',
        self::CRITERIA_TYPE_COURSE => 'course',
        self::CRITERIA_TYPE_ACTIVITY => 'activity',
        self::CRITERIA_TYPE_PROFILE => 'profile',
        self::CRITERIA_TYPE_TOTARA_PROGRAM => 'totaraprogram',
        self::CRITERIA_TYPE_TOTARA_CERTIF => 'totaraprogram'
    );
    /**
     * Constructor
     *
     * @param array $params
     * @see self::build
     */
    public function __construct($params = null) {
        $this->id = isset($params['id']) ? $params['id'] : -1;
        $this->courseid = isset($params['courseid']) ? $params['courseid'] : -1;
        $this->criteriatype = isset($params['criteriatype']) ? $params['criteriatype'] : self::CRITERIA_TYPE_UNKNOWN;
        $criterionid = isset($params['criterionid']) ? $params['criterionid'] : (isset($params['obf_criterion_id']) ? $params['obf_criterion_id'] : null);
        if (!is_null($criterionid)) {
            $this->criterionid = $criterionid;
            $this->criterion = $this->get_criterion();
        }
    }
    /**
     * Build criteria object based on params.
     *
     * @param array $params
     * @return obf_criterion_base|mixed Returns obf_criterion_(course|activity|unknown)
     *         based on what criteriatype was passed in params.
     * @see self::$obfbadgecriteriatypeclasses
     */
    public static function build($params) {
        $typeid = isset($params['criteriatype']) ? $params['criteriatype'] : (isset($params['criteria_type']) ? $params['criteria_type'] : null);
        if (!isset($typeid) || is_null($typeid) || !isset(self::$obfbadgecriteriatypeclasses[$typeid])) {
            throw new Exception("Error Building criterion." . $params['criteriatype']);
        }
        $type = self::$obfbadgecriteriatypeclasses[$typeid];
        $class = 'obf_criterion_' . $type;
        require_once(__DIR__ . '/' . $type . '.php');
        return new $class($params);
    }
    /**
     * Return a criteria item with the right class.
     * @param int $type Criteria type (obf_criterion_item::CRITERIA_TYPE_*)
     * @return mixed obf_criterion_(course|activity|unknown)
     * @see self::build
     */
    public static function build_type($type) {
        return self::build(array('criteriatype' => $type));
    }
    /**
     * Get text represention of the criterion type.
     *
     * @param int $type Criteria type (obf_criterion_item::CRITERIA_TYPE_*)
     * @return string Text repesantation (course|activity|unknown)
     */
    public static function get_criterion_type_text($type) {
        return self::$obfbadgecriteriatypeclasses[$type];
    }
    /**
     * Get instance of obf_criterion_*.
     *
     * @param int $instanceid
     * @param int $method
     * @return mixed obf_criterion_* based on what $method or criteria on record type was.
     */
    public static function get_instance($instanceid, $method = null) {
        if (is_null($method)) {
            global $DB;
            $record = $DB->get_record('local_obf_criterion_courses', array('id' => $instanceid));
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
     * @param obf_criterion $criterion
     * @return obf_criterion_course[]
     */
    public static function get_criterion_items(obf_criterion $criterion) {
        global $DB;

        $records = $DB->get_records('local_obf_criterion_courses',
                array('obf_criterion_id' => $criterion->get_id()));
        $ret = array();

        foreach ($records as $record) {
            $obj = self::build(array('criteriatype' => $record->criteria_type));
            $ret[] = $obj->populate_from_record($record);
        }

        return $ret;
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

    /**
     * Checks that criteria is reviewable and we should
     * show review after save settings on forms.
     */
    public function is_reviewable() {
        return $this->courseid != -1 && $this->criterionid != -1 &&
                $this->criteriatype != self::CRITERIA_TYPE_UNKNOWN;
    }
    /**
     * If criterion required a field.
     * Child class may override this function if required fields differ.
     * @param string $field Fielnd name.
     * @return bool True if field is required.
     */
    public function requires_field($field) {
        return in_array($field, array('courseid', 'criterionid'));
    }

    /**
     * Get expires date that overrides expiration date set on badge settings.
     *
     * @param stdClass $user
     * @return Expires by time in unix-timestamp format
     */
    public function get_issue_expires_override($user = null) {
        return null;
    }

    /**
     * Get id.
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Set id.
     * @param int $id
     * @return $this
     */
    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Get criterion id.
     * @return int Main criterion on which this is an item on
     * @see obf_criterion
     */
    public function get_criterionid() {
        return $this->criterionid;
    }

    /**
     * Set criterion id.
     * @param int $criterionid Main criterion on which this will be an item on
     * @see obf_criterion
     */
    public function set_criterionid($criterionid) {
        $this->criterionid = $criterionid;
        return $this;
    }
    /**
     * Returns the criterion related to this object.
     *
     * @return obf_criterion
     * @see obf_criterion
     */
    public function get_criterion() {
        if (is_null($this->criterion)) {
            $this->criterion = obf_criterion::get_instance($this->criterionid);
        }

        return $this->criterion;
    }

    /**
     * Set course id.
     * @param int $courseid
     * @return $this
     */
    public function set_courseid($courseid) {
        $this->courseid = $courseid;
        return $this;
    }

    /**
     * Get course id
     * @return int
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Is there a courseid in this criterion?
     *
     * @return boolean True if course id is set
     */
    public function has_courseid() {
        return !empty($this->courseid) && $this->courseid > 0;
    }

    /**
     * Get criteria type.
     * @return int Type as obf_criterion_item::CRITERIA_TYPE_*
     */
    public function get_criteriatype() {
        return $this->criteriatype;
    }

    /**
     * Set criteria type.
     * @param int $type Type as obf_criterion_item::CRITERIA_TYPE_*
     */
    public function set_criteriatype($type) {
        $this->criteriatype = $type;
        return $this;
    }

    /**
     * Get completed by timestamp.
     * @return int Completed by time requirement as a unix-timestamp
     */
    public function get_completedby() {
        return $this->completedby;
    }

    /**
     * Set completed by time.
     * @param int $completedby Completed by time requirement as a unix-timestamp
     */
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
    /**
     * Does the criteria support multiple courses?
     *
     * @return boolean
     */
    public function criteria_supports_multiple_courses() {
        return false;
    }
    /**
     * Save criterion item.
     *
     * @see obf_criterion_course::save
     */
    abstract public function save();
    /**
     * Get name (abstract).
     * @return string
     */
    abstract public function get_name();
    /**
     * Get text/name (abstract).
     * @return string
     */
    abstract public function get_text();
    /**
     * Get text/name array.
     * @return string[]
     */
    public function get_text_array() {
        return array($this->get_text());
    }
    /**
     * Delete criterion item.
     * @see obf_criterion_course::delete
     */
    abstract public function delete();
}
