<?php

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');

class obf_criterion_deletion_form extends obfform implements renderable {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('html', html_writer::tag('p', get_string('confirmcriteriondeletion', 'local_obf')));
        $this->add_action_buttons(true, get_string('deletecriterion', 'local_obf'));
    }
}