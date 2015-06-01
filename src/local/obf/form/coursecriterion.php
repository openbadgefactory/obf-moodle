<?php

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');

class obf_coursecriterion_form extends obfform {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $criterioncourse = $this->_customdata['criterioncourse'];

        // Minimum grade -field
        $mform->addElement('text', 'mingrade',
                get_string('minimumgrade', 'local_obf'));
        $mform->addRule('mingrade', null, 'numeric', null, 'client');
        $mform->setType('mingrade', PARAM_INT);

        if ($criterioncourse->has_grade()) {
            $mform->setDefault('mingrade', $criterioncourse->get_grade());
        }

        $mform->addElement('date_selector', 'completedby',
                get_string('coursecompletedby', 'local_obf'),
                array('optional' => true, 'startyear' => date('Y')));

        if ($criterioncourse->has_completion_date()) {
            $mform->setDefault('completedby',
                    $criterioncourse->get_completedby());
        }

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

        if ($criterioncourse->exists()) {
            $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton',
                            get_string('deletecriterion', 'local_obf'));
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
