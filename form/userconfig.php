<?php

require_once($CFG->libdir . '/formslib.php');

class obf_userconfig_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;
        $backpack = $this->_customdata['backpack'];
        $langkey = 'backpack' . (!$backpack->is_connected() ? 'dis' : '') . 'connected';
        $statustext = html_writer::tag('span', get_string($langkey, 'local_obf'),
                        array('class' => $langkey));

        $mform->addElement('static', 'connectionstatus',
                get_string('connectionstatus', 'local_obf'), $statustext);

        $mform->addElement('text', 'backpackemail', get_string('backpackemail', 'local_obf'));
        $mform->addRule('backpackemail', null, 'required');
        $mform->addRule('backpackemail', null, 'email');
        $mform->setType('backpackemail', PARAM_NOTAGS);
        $mform->addHelpButton('backpackemail', 'backpackemail', 'local_obf');
        $mform->setDefault('backpackemail', $backpack->get_email());

        if ($backpack->is_connected()) {
            $groups = $backpack->get_groups();

            if (count($groups) === 0) {
                $mform->addElement('static', 'nogroups', get_string('backpackgroups', 'local_obf'),
                        get_string('nobackpackgroups', 'local_obf'));
            }
            else {
                $radiobuttons = array();

                foreach ($groups as $group) {
                    $langkey = 'numberofbadges' . ($group->badges == 1 ? 'single' : 'many');
                    $badgestr = $group->badges . ' ' . get_string($langkey, 'local_obf');
                    $radiobuttons[] = $mform->createElement('radio', 'selectedgroup', '',
                            s($group->name) . ' (' . $badgestr . ')', (int) $group->groupId);
                }

                $mform->addGroup($radiobuttons, 'groupbuttons', get_string('backpackgroups', 'local_obf'), '<br  />');
                $mform->addHelpButton('groupbuttons', 'backpackgroups', 'local_obf');

                if ($backpack->get_group_id() > 0) {
                    $mform->setDefault('groupbuttons[selectedgroup]', $backpack->get_group_id());
                }
            }
        }

        $this->add_action_buttons(false);
    }

}
