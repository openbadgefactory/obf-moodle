<?php
/**
 * Description of criteria_base
 *
 * @author olli
 */
abstract class obf_criterion {

    const CRITERIA_COMPLETION_ALL = 1;
    const CRITERIA_COMPLETION_ANY = 2;
    
    public function __construct() {
        
    }

    public function get_yui_modules() {
        return array();
    }

    /**
     * 
     * @global moodle_database $DB
     * @param obf_badge $badge
     */
    public static function get_badge_criteria(obf_badge $badge) {
        global $DB;
        
        $sql = 'SELECT a.*, c.id AS criterionid, c.obf_criterion_type_id, c.badge_id, ' .
                    'c.completion_method, t.name AS groupname ' .
               'FROM {obf_criterion_attributes} a ' .
               'LEFT JOIN {obf_criterion} c ON a.obf_criterion_id = c.id ' .
               'LEFT JOIN {obf_criterion_types} t ON c.obf_criterion_type_id = t.id ' .
               'WHERE c.badge_id = ?';
        $records =  $DB->get_records_sql($sql, array($badge->get_id()));
        $ret = array();
        
        foreach ($records as $record) {
            $criterionid = $record->obf_criterion_id;
            if (!isset($ret[$criterionid])) {
                $ret[$criterionid] = new stdClass();
                $ret[$criterionid]->groupname = $record->groupname;
                $ret[$criterionid]->completion_method = $record->completion_method;
                $ret[$criterionid]->attributes = array();
            }
            
            $ret[$criterionid]->attributes[] = $record;
        }
        
        return $ret;
    }
    
    /**
     * 
     * @global moodle_database $DB
     * @param type $criterionid
     * @param type $name
     * @param type $value
     */
    protected function add_criterion_attribute($criterionid, $name, $value) {
        global $DB;
        
        $attribute = new stdClass();
        $attribute->obf_criterion_id = $criterionid;
        $attribute->name = $name;
        $attribute->value = $value;

        $DB->insert_record('obf_criterion_attributes', $attribute, false, true);
    }

    abstract public function render(obf_badge $badge);
    abstract public function parse_attributes(array $attributes);
    abstract public function get_attribute_text($attribute);
}

?>
