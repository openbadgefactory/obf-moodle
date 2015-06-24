<?php

class block_obf_displayer_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('advcheckbox', 'config_showobf', get_string('showobf', 'block_obf_displayer'));
        $mform->setDefault('config_showobf', 1);
        $mform->addElement('advcheckbox', 'config_showobp', get_string('showobp', 'block_obf_displayer'));
        $mform->setDefault('config_showobp', 1);
        $mform->addElement('advcheckbox', 'config_showmoz', get_string('showmoz', 'block_obf_displayer'));
        $mform->setDefault('config_showmoz', 1);
    }
}
