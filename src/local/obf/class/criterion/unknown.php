<?php
defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/course.php');

class obf_criterion_unknown extends obf_criterion_item {
    /**
     * Get the instance of this class by id.
     *
     * @global moodle_database $DB
     * @param int $id The id of the activity criterion
     * @return obf_criterion_activity
     */
    public static function get_instance($id, $method = null) {
        global $DB;

        $record = $DB->get_record('obf_criterion_courses', array('id' => $id));
        $obj = new self();

        return $obj->populate_from_record($record);
    }
    public function get_criterion() {
        if (is_null($this->criterion)) {
            $this->criterion = obf_criterion::get_instance($this->criterionid);
        }

        return $this->criterion;
    }
    public function review($criterion = null, $other_items = null, &$extra = array()) {
        return array();
    }
    public function save() {
        global $DB;

        if ($this->get_criterionid() == -1) {
            throw new Exception("Invalid criterion id", $this->get_criterionid());
        }

        $obj = new stdClass();
        $obj->obf_criterion_id = $this->criterionid;
        $obj->courseid = $this->courseid;
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
    public function delete() {
        global $DB;

        $DB->delete_records('obf_criterion_courses', array('id' => $this->id));
        obf_criterion::delete_empty($DB);
    }

    public function get_name() {
        return get_string('unknowncriterion', 'local_obf');
    }
    public function get_text() {
        $html = html_writer::tag('strong', $this->get_name());
        return $html;
    }
    public function is_reviewable() {
        return false;
    }
    public function populate_from_record($record) {
        if (isset($record->id)) {
            $this->set_id($record->id)
                    ->set_criterionid($record->obf_criterion_id)
                    ->set_courseid($record->courseid)
                    ->set_criteriatype($record->criteria_type)
                    ->set_completedby($record->completed_by);
        } else if (isset($record->criteria_type) && $record->criteria_type != obf_criterion_item::CRITERIA_TYPE_UNKNOWN) {
            return obf_criterion_item::build(array_merge(
                    (array)$record,
                    array('criteriatype' => $record->criteria_type)));
        }
        return $this;
    }
    // Unknown has no options. Only form config.
    public function get_options(&$mform) {
    }
    /**
     * Prints criteria type select for criteria forms.
     * @param moodle_form $mform
     */
    public function get_form_config(&$mform) {
        global $PAGE, $OUTPUT, $CFG;
        $optionlist = array(
            obf_criterion_item::CRITERIA_TYPE_UNKNOWN => get_string('selectcriteriatype', 'local_obf'),
            obf_criterion_item::CRITERIA_TYPE_COURSE => get_string('criteriatypecourseset', 'local_obf'),
            obf_criterion_item::CRITERIA_TYPE_ACTIVITY => get_string('criteriatypeactivity', 'local_obf')
        );
        if (property_exists($CFG,'totara_build')) {
            $totaraoptions = array(
                obf_criterion_item::CRITERIA_TYPE_TOTARA_PROGRAM => get_string('criteriatypetotaraprogram', 'local_obf'),
                obf_criterion_item::CRITERIA_TYPE_TOTARA_CERTIF => get_string('criteriatypetotaracertif', 'local_obf')
            );
            foreach ($totaraoptions as $key => $val) {
                $optionlist[$key] = $val;
            }
        }

        if (!empty($this->get_criterionid()) && ($this->get_criterionid() > 0) && !empty($this->get_courseid())) {
            if ($PAGE->pagetype == 'local-obf-badge') {
                //$url = new moodle_url('/local/obf/badge.php', array());
                $url = new moodle_url('/local/obf/badge.php',
                        array('id' => $this->get_criterion()->get_badgeid(), 'action' =>
                    'show', 'show' => 'criteria', 'courseid' => $this->get_courseid()));
            } else {
                $url = new moodle_url('/local/obf/criterion.php',
                        array('id' => $this->get_criterionid(), 'action' =>
                    'edit', 'show' => 'criteria', 'courseid' => $this->get_courseid()));
            }
        }

        $mform->addElement('html',
                html_writer::tag('p', get_string('selectcriteriatype_help', 'local_obf')));


        $select = $mform->addElement('select', 'criteriatype',
                get_string('selectcriteriatype', 'local_obf'), $optionlist);


        $select->setSelected(obf_criterion_item::CRITERIA_TYPE_UNKNOWN);
        $mform->addElement('hidden','picktype', 'yes');
        $mform->setType('picktype', PARAM_TEXT);

        // TODO: TEST
        $mform->addElement('hidden','course[]', '-1');
        $mform->setType('course[]', PARAM_RAW);
    }
    public function get_form_completion_options(&$mform, $obj = null) {

    }
    public function get_form_after_save_options(&$mform,&$obj) {

    }
    public function criteria_supports_multiple_courses() {
        return false;
    }
}
