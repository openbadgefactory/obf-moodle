<?php

class block_obf_displayer_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('advcheckbox', 'config_largebadges', get_string('largebadges', 'block_obf_displayer'));
        $mform->setDefault('config_largebadges', 0);

        $mform->addElement('header', 'config_providers_header', get_string('providerselect', 'block_obf_displayer'));

        $mform->addElement('advcheckbox', 'config_showobf', get_string('showobf', 'block_obf_displayer'));
        $mform->setDefault('config_showobf', 1);
        $mform->addElement('advcheckbox', 'config_showobp', get_string('showobp', 'block_obf_displayer'));
        $mform->setDefault('config_showobp', 0);
        $mform->addElement('advcheckbox', 'config_showmoz', get_string('showmoz', 'block_obf_displayer'));
        $mform->setDefault('config_showmoz', 0);

        $this->setExpanded($mform, 'config_providers_header', true);
    }
    protected function setExpanded(&$mform, $header, $expanded) {
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded($header, $expanded);
        }
    }
}
