<?php

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');

class obf_config_form extends obfform implements renderable {

    protected function definition() {

        $mform = $this->_form;
        $connectionestablished = obf_client::get_instance()->test_connection();

//        $obfurl = obf_client::get_api_url();
//        $mform->addElement('text', 'obfurl', get_string('url', 'local_obf'));
//        $mform->setType('obfurl', PARAM_URL);
//        $mform->setDefault('obfurl', $obfurl === false ? '' : $obfurl);

        if ($connectionestablished) {
            $mform->addElement('html',
                    html_writer::tag('p', get_string('connectionisworking', 'local_obf')));
            $mform->addElement('header', 'config', get_string('showconnectionconfig', 'local_obf'));

            if (method_exists($mform, 'setExpanded')) {
                $mform->setExpanded('config', false);
            }
        }

        $mform->addElement('textarea', 'obftoken', get_string('requesttoken', 'local_obf'),
                array('rows' => 10));
//        $mform->addRule('obftoken', '', 'required');
        $mform->addHelpButton('obftoken', 'requesttoken', 'local_obf');

        $buttonarray = array($mform->createElement('submit', 'submitbutton',
                    get_string('savechanges')));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
