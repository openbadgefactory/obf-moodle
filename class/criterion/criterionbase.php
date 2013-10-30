<?php

require_once __DIR__ . '/../badge.php';

/**
 * Description of criteria_base
 *
 * @author olli
 */
abstract class obf_criterion_base {

//    const CRITERIA_COMPLETION_ALL = 1;
//    const CRITERIA_COMPLETION_ANY = 2;
//    const CRITERIA_TYPE_COURSESET = 1;
//
//    static $CRITERIA_TYPES = array(
//        self::CRITERIA_TYPE_COURSESET => 'courseset'
//    );
//    protected $badge = null;
//    protected $id = -1;
//    protected $completion_method = null;
//    protected $attributes = array();
//    protected $type_id = -1;

    protected $id = -1;
    protected $criterionid = -1;
//    protected $typeid = -1;

//    public function __construct(obf_badge $badge) {
//        $this->badge = $badge;
//    }
//
//    public function get_yui_modules() {
//        return array();
//    }

    /**
     *
     * @global moodle_database $DB
     * @param type $id
     * @return boolean
     */
//    public static function get_instance($id) {
//        global $DB;
//        $record = $DB->get_record('obf_criterion', array('id' => $id));
//
//        if (!$record) {
//            return false;
//        }
//
//        $badge = obf_badge::get_instance($record->badge_id);
//        $obj = self::get_empty_instance($record->criterion_type_id, $badge);
//        $obj->set_id($record->id);
//        $obj->set_completion_method($record->completion_method);
//
//        return $obj;
//    }

    /**
     *
     * @param int $criteriatype
     * @param obf_badge $badge
     * @return obf_criterion_base
     * @throws Exception
     */
//    public static function get_empty_instance($criteriatype, obf_badge $badge) {
//        if (!isset(obf_criterion::$CRITERIA_TYPES[$criteriatype])) {
//            throw new Exception('Invalid criteria type');
//        }
//
//        $classname = 'obf_criterion_' . obf_criterion::$CRITERIA_TYPES[$criteriatype];
//
//        require_once __DIR__ . '/' . obf_criterion::$CRITERIA_TYPES[$criteriatype] . '.php';
//
//        $obj = new $classname($badge);
//        $obj->set_typeid($criteriatype);
//
//        return $obj;
//    }

    /**
     *
     * @global moodle_database $DB
     */
//    public function update() {
//        global $DB;
//
//        $obj = new stdClass();
//        $obj->id = $this->id;
//        $obj->criterion_type_id = $this->type_id;
//        $obj->badge_id = $this->badge->get_id();
//        $obj->completion_method = $this->completion_method;
//
//        $DB->update_record('obf_criterion', $obj);
//    }

    /**
     *
     * @global moodle_database $DB
     * @return boolean
     */
//    public function save() {
//        global $DB;
//
//        $obj = new stdClass();
//        $obj->criterion_type_id = $this->type_id;
//        $obj->badge_id = $this->badge->get_id();
//        $obj->completion_method = $this->completion_method;
//
//        $id = $DB->insert_record('obf_criterion', $obj, true);
//
//        if ($id === false) {
//            return false;
//        }
//
//        $this->set_id($id);
//
//        return true;
//    }
//    public function exists() {
//        return $this->id > 0;
//    }

    /**
     *
     * @global moodle_database $DB
     * @return boolean
     */
//    public function delete() {
//        global $DB;
//
//        if (!empty($this->id)) {
//            $this->delete_attributes();
//            $DB->delete_records('obf_criterion', array('id' => $this->id));
//            return true;
//        }
//
//        return false;
//    }

    /**
     *
     * @global moodle_database $DB
     */
//    public function update_attributes() {
//        global $DB;
//        $this->attributes = $DB->get_records('obf_criterion_attributes',
//                array('obf_criterion_id' => $this->id));
//    }

    /**
     *
     * @global moodle_database $DB
     */
//    public function delete_attributes() {
//        global $DB;
//        $DB->delete_records('obf_criterion_attributes', array('obf_criterion_id' => $this->id));
//    }

    /**
     *
     * @param obf_badge $badge
     * @return obf_criterion_base[]
     */
//    public static function get_badge_criteria(obf_badge $badge, $type = -1) {
//        $conditions = array('c.badge_id' => $badge->get_id());
//
//        if ($type > 0) {
//            $conditions['c.criterion_type_id'] = $type;
//        }
//
//        return self::get_criteria($conditions);
//    }

    /**
     *
     * @global moodle_database $DB
     * @param array $conditions
     * @return type
     */
//    public static function get_criteria(array $conditions = array()) {
//        global $DB;
//
//        $sql = 'SELECT cc.*, c.id AS criterionid, c.criterion_type_id, c.badge_id, ' .
//                      'c.completion_method ' .
//                 'FROM {obf_criterion_courses} cc ' .
//            'LEFT JOIN {obf_criterion} c ON cc.obf_criterion_id = c.id';
//        $params = array();
//        $cols = array();
//
//        if (count($conditions) > 0) {
//            foreach ($conditions as $column => $value) {
//                $cols[] = $column . ' = ?';
//                $params[] = $value;
//            }
//
//            $sql .= ' WHERE ' . implode(' AND ', $cols);
//        }
//
//        $records = $DB->get_records_sql($sql, $params);
//        $ret = array();
//
//        foreach ($records as $record) {
//            // Group by criterion
//            if (!isset($ret[$record->criterionid])) {
//                // FIXME: this probably makes an API call every time.
//                $badge = obf_badge::get_instance($record->badge_id);
//                $obj = self::get_empty_instance($record->criterion_type_id, $badge);
//                $obj->set_id($record->criterionid);
//                $obj->set_completion_method($record->completion_method);
//
//                $ret[$record->criterionid] = $obj;
//            }
//
//            $ret[$record->criterionid]->add_criterion_item()
//        }
//
//        foreach ($records as $record) {
//            if (!isset($ret[$record->criterionid])) {
//
//                // FIXME: this probably makes an API call every time.
//                $badge = obf_badge::get_instance($record->badge_id);
//
//                $obj = self::get_empty_instance($record->criterion_type_id, $badge);
//                $obj->set_id($record->criterionid);
//                $obj->set_completion_method($record->completion_method);
//
//                $ret[$record->criterionid] = $obj;
//            }
//
//            $ret[$record->criterionid]->add_attribute($record);
//        }
//
//        return $ret;
//}

    /**
     *
     * @global moodle_database $DB
     * @param type $criterionid
     * @param type $name
     * @param type $value
     */
//    public function save_attribute($name, $value) {
//        global $DB;
//
//        if (!empty($this->id)) {
//            $attribute = new stdClass();
//            $attribute->obf_criterion_id = $this->id;
//            $attribute->name = $name;
//            $attribute->value = $value;
//
//            $DB->insert_record('obf_criterion_attributes', $attribute, false, true);
//        }
//    }
//
//    public function is_met_by_user(stdClass $user) {
//        global $DB;
//
//        return ($DB->count_records('obf_criterion_met', array('obf_criterion_id' => $this->id,
//            'user_id' => $user->id)) > 0);
//    }
//
//    public function is_met() {
//        global $DB;
//        return ($DB->count_records('obf_criterion_met', array('obf_criterion_id' => $this->id)) > 0);
//    }
//
//    public function set_met_by_user(stdClass $user) {
//        global $DB;
//
//        $obj = new stdClass();
//        $obj->obf_criterion_id = $this->id;
//        $obj->user_id = $user->id;
//        $obj->met_at = time();
//        $DB->insert_record('obf_criterion_met', $obj);
//    }

    /**
     *
     * @return obf_badge
     */
//    public function get_badge() {
//        return $this->badge;
//    }
//
//    public function get_id() {
//        return $this->id;
//    }
//
//    public function set_id($id) {
//        $this->id = $id;
//        return $this;
//    }
//    public function get_completion_method() {
//        return $this->completion_method;
//    }
//
//    public function set_completion_method($completion_method) {
//        $this->completion_method = $completion_method;
//        return $this;
//    }
//
//    public function get_attributes() {
//        if (count($this->attributes) === 0) {
//            $this->update_attributes();
//        }
//
//        return $this->attributes;
//    }
//    public function add_attribute(stdClass $attribute) {
//        $this->attributes[] = $attribute;
//        return $this;
//    }
//
//    public function set_type_id($id) {
//        $this->type_id = $id;
//        return $this;
//    }
//
//    public function get_type_id() {
//        return $this->type_id;
//    }
//
//    abstract public function get_parsed_attributes();
//    abstract public function get_attribute_text($attribute);
//    abstract public function customizeform(obf_criterion_form &$form);
//    abstract public function review($data);

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

    abstract public function get_text();
    abstract public function populate_from_record(stdClass $record);
}
