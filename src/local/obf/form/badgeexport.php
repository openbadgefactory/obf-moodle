<?php

defined('MOODLE_INTERNAL') or die();

require_once($CFG->libdir . '/formslib.php');

class obf_badge_export_form extends local_obf_form_base {

    protected function definition() {
        global $OUTPUT;
        $mform = $this->_form;
        $badges = $this->_customdata['badges'];

        $mform->addElement('header', 'header_badgeselect',
                get_string('selectbadgestoexport', 'local_obf'));

        if (count($badges) === 0) {
            $mform->addElement('html', '<p>' . get_string('nobadgestoexport', 'local_obf') . '</p>');
        }

        foreach ($badges as $badge) {
            $label = print_badge_image($badge, $badge->get_context()) . ' ' . s($badge->name);
            $mform->addElement('advcheckbox', 'toexport[' . $badge->id . ']', '',
                    $label,array('group' => 1));
        }
        $this->add_checkbox_controller(1);
        $mform->addElement('html', $OUTPUT->notification('Valituista merkeistä luodaan kopio Open Badge Factory -palveluun.', 'notifymessage'));

        $mform->addElement('header', 'header_disablebadges',
                get_string('exportextrasettings', 'local_obf'));

        if (count($badges) > 0) {
            /**$mform->addElement('advcheckbox', 'makedrafts', '',
                    get_string('makeexporteddrafts', 'local_obf'));
            $mform->setDefault('makedrafts', false);*/
            $mform->addElement('hidden', 'makedrafts', 0);
            $mform->setType('makedrafts', PARAM_INT);
        }

        $mform->addElement('advcheckbox', 'disablemoodlebadges', '',
                get_string('disablemoodlebadges', 'local_obf'));
        $mform->addHelpButton('disablemoodlebadges', 'disablemoodlebadges', 'local_obf');
        $mform->setDefault('disablemoodlebadges', true);

        $this->add_action_buttons(false,
                get_string('saveconfiguration', 'local_obf'));
    }

}
