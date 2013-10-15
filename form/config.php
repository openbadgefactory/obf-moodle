<?php
defined('MOODLE_INTERNAL') or die();

require_once($CFG->libdir . '/formslib.php');

class obf_config_form extends moodleform implements renderable {
    protected function definition() {
        $mform = $this->_form;
        $obfurl = get_config('local_obf', 'obfurl');
        
        $mform->addElement('text', 'obfurl', get_string('url', 'local_obf'));
        $mform->setType('obfurl', PARAM_URL);
        $mform->setDefault('obfurl', $obfurl === false ? '' : $obfurl);
        
        $mform->addElement('textarea', 'obftoken', get_string('requesttoken', 'local_obf'),
                array('rows' => 10));
        $mform->addHelpButton('obftoken', 'requesttoken', 'local_obf');
        
        $this->add_action_buttons(false);
    }    
}
?>
