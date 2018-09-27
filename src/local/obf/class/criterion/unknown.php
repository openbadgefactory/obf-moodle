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
 * Unknown criterion.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/course.php');

/**
 * Unknown criterion.
 *
 * Unknown criterion handles some initial criterion form function,
 * like listing criterion item types to choose from.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_criterion_unknown extends obf_criterion_item {
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
     * Get criterion.
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
     * Review mock.
     *
     * @param  obf_criterion $criterion
     * @param  mixed[] $otheritems
     * @param  array  $extra
     * @return array An empty array
     */
    public function review($criterion = null, $otheritems = null, &$extra = array()) {
        return array();
    }
    /**
     * Save.
     */
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
     * Delete criterion item.
     */
    public function delete() {
        global $DB;

        $DB->delete_records('local_obf_criterion_courses', array('id' => $this->id));
        obf_criterion::delete_empty($DB);
    }

    /**
     * Get name.
     * @return string
     */
    public function get_name() {
        return get_string('unknowncriterion', 'local_obf');
    }
    /**
     * Get name.
     * @return string
     */
    public function get_text() {
        $html = html_writer::tag('strong', $this->get_name());
        return $html;
    }
    /**
     * Is criterion item reviewable?
     * @return boolean False
     */
    public function is_reviewable() {
        return false;
    }
    /**
     * Populate from record.
     * @param stdClass $record
     * @return $this
     */
    public function populate_from_record($record) {
        if (isset($record->id)) {
            $this->set_id($record->id)->set_criterionid($record->obf_criterion_id);
            $this->set_courseid($record->courseid)->set_criteriatype($record->criteria_type);
            $this->set_completedby($record->completed_by);
        } else if (isset($record->criteria_type) && $record->criteria_type != obf_criterion_item::CRITERIA_TYPE_UNKNOWN) {
            return obf_criterion_item::build(array_merge(
                    (array)$record,
                    array('criteriatype' => $record->criteria_type)));
        }
        return $this;
    }
    /**
     * Print options. (Nothing)
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj
     */
    public function get_options(&$mform, &$obj) {
    }

    /**
     * Get options to for criteria types.
     * @return array An associative array of criteria type options
     */
    public static function get_criteria_type_options() { 
        global $CFG;
        $optionlist = array(
            obf_criterion_item::CRITERIA_TYPE_UNKNOWN => get_string('selectcriteriatype', 'local_obf'),
            obf_criterion_item::CRITERIA_TYPE_COURSE => get_string('criteriatypecourseset', 'local_obf'),
            obf_criterion_item::CRITERIA_TYPE_ACTIVITY => get_string('criteriatypeactivity', 'local_obf'),
            obf_criterion_item::CRITERIA_TYPE_PROFILE => get_string('criteriatypeprofile', 'local_obf')
        );
        if (property_exists($CFG, 'totara_build')) {
            $totaraoptions = array(
                obf_criterion_item::CRITERIA_TYPE_TOTARA_PROGRAM => get_string('criteriatypetotaraprogram', 'local_obf'),
                obf_criterion_item::CRITERIA_TYPE_TOTARA_CERTIF => get_string('criteriatypetotaracertif', 'local_obf')
            );
            foreach ($totaraoptions as $key => $val) {
                $optionlist[$key] = $val;
            }
        }
        return  $optionlist;
    }
    /**
     * Prints criteria type select for criteria forms.
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj
     */
    public function get_form_config(&$mform, &$obj) {
        global $PAGE, $OUTPUT, $CFG;
        $optionlist = self::get_criteria_type_options();

        $criterionid = $this->get_criterionid();
        $courseid = $this->get_courseid();
        if (!empty($criterionid) && ($this->get_criterionid() > 0) && !empty($courseid)) {
            if ($PAGE->pagetype == 'local-obf-badge') {
                $url = new moodle_url('/local/obf/badge.php',
                        array('id' => $this->get_criterion()->get_badgeid(),
                        'action' => 'show', 'show' => 'criteria', 'courseid' => $this->get_courseid()));
            } else {
                $url = new moodle_url('/local/obf/criterion.php',
                        array('id' => $this->get_criterionid(),
                        'action' => 'edit', 'show' => 'criteria', 'courseid' => $this->get_courseid()));
            }
        }

        $mform->addElement('html',
                html_writer::tag('p', get_string('selectcriteriatype_help', 'local_obf')));

        $select = $mform->addElement('select', 'criteriatype',
                get_string('selectcriteriatype', 'local_obf'), $optionlist);

        $select->setSelected(obf_criterion_item::CRITERIA_TYPE_UNKNOWN);

        $mform->addElement('hidden', 'picktype', 'yes');
        $mform->setType('picktype', PARAM_TEXT);
    }
    /**
     * Print completion options. (Nothing)
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj
     * */
    public function get_form_completion_options(&$mform, $obj = null) {

    }
    /**
     * Print after save options. (Nothing)
     * @param MoodleQuickForm& $mform
     * @param mixed& $obj
     * */
    public function get_form_after_save_options(&$mform, &$obj) {

    }
    
    public function get_form_criteria_addendum_options(&$mform, &$obj) {

    }

    /**
     * Return all form field names and types, that need to be present on a form,
     * to make sure form->get_data works.
     *
     * @return array An empty array
     */
    public function get_form_fields() {
        return array();
    }

    /**
     * This criteria item supports multiple courses?
     * @return bool False
     */
    public function criteria_supports_multiple_courses() {
        return false;
    }
}
