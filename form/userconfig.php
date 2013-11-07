<?php

require_once($CFG->libdir . '/formslib.php');

class obf_userconfig_form extends moodleform {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $backpack = $this->_customdata['backpack'];
        $langkey = 'backpack' . (!$backpack->is_connected() ? 'dis' : '') . 'connected';
        $statustext = html_writer::tag('span', get_string($langkey, 'local_obf'),
                        array('class' => $langkey));

        $mform->addElement('static', 'connectionstatus',
                get_string('connectionstatus', 'local_obf'), $statustext);

        $mform->addElement('text', 'backpackemail', get_string('backpackemail', 'local_obf'));
        $mform->addRule('backpackemail', null, 'email');
        $mform->setType('backpackemail', PARAM_NOTAGS);
        $mform->addHelpButton('backpackemail', 'backpackemail', 'local_obf');
        $mform->setDefault('backpackemail', $backpack->get_email());

        if ($backpack->is_connected()) {
            $groups = $backpack->get_groups();

            if (count($groups) === 0) {
                $mform->addElement('static', 'nogroups', get_string('backpackgroups', 'local_obf'),
                        get_string('nobackpackgroups', 'local_obf'));
            } else {
                $checkboxes = array();

                foreach ($groups as $group) {
                    $assertions = $backpack->get_group_assertions($group->groupId);
                    $grouphtml = s($group->name) . $OUTPUT->box($this->render_badge_group($assertions),
                                    'generalbox service');
                    $checkboxes[] = $mform->createElement('checkbox', $group->groupId, '',
                            $grouphtml);
                }

                $mform->addGroup($checkboxes, 'backpackgroups',
                        get_string('backpackgroups', 'local_obf'), '<br  />', true);
                $mform->addHelpButton('backpackgroups', 'backpackgroups', 'local_obf');

                foreach ($backpack->get_group_ids() as $id) {
                    $mform->setDefault('backpackgroups[' . $id . ']', true);
                }

//                if ($backpack->get_groups() > 0) {
//                    $mform->setDefault('groupbuttons[selectedgroup]', $backpack->get_group_ids());
//                }
            }
        }

        $buttonarray = array();
        $submittext = $backpack->is_connected() ? get_string('savechanges') : get_string('connect',
                        'local_obf');
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', $submittext);

        if ($backpack->is_connected()) {
            $buttonarray[] = $mform->createElement('cancel', null,
                    get_string('disconnect', 'local_obf'));
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    private function render_badge_group(obf_assertion_collection $assertions) {
        global $PAGE;

        $items = array();
        $renderer = $PAGE->get_renderer('local_obf');
        $size = -1;

        for ($i = 0; $i < count($assertions); $i++) {
            $badge = $assertions->get_assertion($i)->get_badge();
            $items[] = html_writer::div($renderer->print_badge_image($badge, $size) .
                            html_writer::tag('p', s($badge->get_name())));
        }

        return html_writer::alist($items, array('class' => 'badgelist'));
    }

}
