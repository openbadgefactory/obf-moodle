<?php

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');

class obf_config_form extends obfform implements renderable {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $connectionestablished = obf_client::get_instance()->test_connection();

        if ($connectionestablished) {
            $mform->addElement('html', $OUTPUT->notification(get_string('connectionisworking', 'local_obf'), 'notifysuccess'));
            $mform->addElement('header', 'config', get_string('showconnectionconfig', 'local_obf'));

            if (method_exists($mform, 'setExpanded')) {
                $mform->setExpanded('config', false);
            }
        }

        $mform->addElement('textarea', 'obftoken', get_string('requesttoken', 'local_obf'),
                array('rows' => 10));
        $mform->addHelpButton('obftoken', 'requesttoken', 'local_obf');

        $buttonarray = array($mform->createElement('submit', 'submitbutton',
                    get_string('savechanges')));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
