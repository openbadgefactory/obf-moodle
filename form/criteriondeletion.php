<?php

defined('MOODLE_INTERNAL') or die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class obf_criterion_deletion_form extends moodleform implements renderable {
    protected function definition() {
        $mform = $this->_form;
        $criterion = $this->_customdata['criterion'];
    
        $mform->addElement('html', html_writer::tag('p', get_string('confirmcriteriondeletion', 'local_obf')));
        $this->add_action_buttons(true, get_string('deletecriterion', 'local_obf'));
    }    
}
?>
