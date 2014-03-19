<?php
defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');

class obf_config_form extends obfform implements renderable {

    /**
     * @global moodle_core_renderer $OUTPUT
     */
    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $client = $this->_customdata['client'];
        $errorcode = $client->test_connection();

        // Connection to API is working
        if ($errorcode === -1) {
            $expires = userdate($client->get_certificate_expiration_date(),
                    get_string('dateformatdate', 'local_obf'));
            $mform->addElement('html',
                    $OUTPUT->notification(get_string('connectionisworking',
                                    'local_obf', $expires), 'notifysuccess'));
            $mform->addElement('hidden', 'deauthenticate', 1);
            $mform->setType('deauthenticate', PARAM_INT);
            $mform->addElement('submit', 'submitbutton',
                    get_string('deauthenticate', 'local_obf'));
        }

        // Connection is not working
        else {
            // We get error code 0 if pinging the API fails (like if the keyfiles
            // are missing). In plugin config we should show a more spesific
            // error to admin, so let's do that by changing the error code.
            $errorcode = $errorcode == 0 ? OBF_API_CODE_NO_CERT : $errorcode;
            $mform->addElement('html',
                    $OUTPUT->notification(get_string('apierror' . $errorcode,
                                    'local_obf'), 'redirectmessage'));
            $mform->addElement('textarea', 'obftoken',
                    get_string('requesttoken', 'local_obf'), array('rows' => 10));
            $mform->addHelpButton('obftoken', 'requesttoken', 'local_obf');

            $buttonarray = array(
                $mform->createElement('submit', 'submitbutton',
                        get_string('authenticate', 'local_obf')));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        }
    }

}
