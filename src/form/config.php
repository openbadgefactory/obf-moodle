<?php

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');

class obf_config_form extends obfform implements renderable {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $errorcode = obf_client::get_instance()->test_connection();

        // Connection to API is working
        if ($errorcode === -1) {
            $mform->addElement('html', $OUTPUT->notification(get_string('connectionisworking', 'local_obf'), 'notifysuccess'));
            $mform->addElement('header', 'config', get_string('showconnectionconfig', 'local_obf'));
            $this->setExpanded($mform, 'config', false);
        }
        // Connection is not working
        else {
            // We get error code 0 if pinging the API fails (like if the keyfiles are missing).
            // In plugin config we should show a more spesific error to admin, so let's do that by
            // changing the error code.
            $errorcode = $errorcode == 0 ? 496 : $errorcode;
            $mform->addElement('html', $OUTPUT->notification(get_string('apierror' . $errorcode, 'local_obf')));
        }

        $mform->addElement('textarea', 'obftoken', get_string('requesttoken', 'local_obf'),
                array('rows' => 10));
        $mform->addHelpButton('obftoken', 'requesttoken', 'local_obf');

        $buttonarray = array($mform->createElement('submit', 'submitbutton',
                    get_string('savechanges')));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
