<?php

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');

class obf_coursecriterion_form extends obfform {
    private $criteriatype;
    private $courseid;
    private $course;
    private $criterioncourse;

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $this->criterioncourse = $this->_customdata['criterioncourse'];
        if ($this->criterioncourse->exists()) {
            $this->criteriatype = $this->criterioncourse->get_criteriatype();
        } else {
            $this->criteriatype = array_key_exists('criteriatype', $this->_customdata) ?
                    $this->_customdata['criteriatype'] : $this->criterioncourse->get_criteriatype();
        }


        $this->criterioncourse->get_options($mform);
        $this->criterioncourse->get_form_config($mform);


        $mform->addElement('html',
                $OUTPUT->notification(get_string('warningcannoteditafterreview',
                                'local_obf')));
        $mform->addElement('advcheckbox', 'reviewaftersave',
                get_string('reviewcriterionaftersave', 'local_obf'));
        $mform->addHelpButton('reviewaftersave', 'reviewcriterionaftersave',
                'local_obf');

//        $this->add_action_buttons(false);
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                        get_string('savechanges'));

        if ($this->criterioncourse->exists()) {
            $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton',
                            get_string('deletecriterion', 'local_obf'));
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
