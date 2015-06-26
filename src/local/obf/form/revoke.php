<?php

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');
require_once(__DIR__ . '/../renderer.php');

class obf_revoke_form extends local_obf_form_base {
    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $assertion = $this->_customdata['assertion'];
        $users = $this->_customdata['users'];
        $revokedemails = array_keys($assertion->get_revoked());

        $i = 0;
        foreach ($users as $user) {
            $name = $user instanceof stdClass ? fullname($user) : $user;
            $email = $user instanceof stdClass ? $user->email : $user;
            $attributes = array('group' => 1);
            $revoked = in_array($email, $revokedemails);
            if ($revoked) {
                $attributes['class'] = 'revoked';
            }
            $mform->addElement('advcheckbox', 'email['.$i.']', null, $name, $attributes, array(null, $email));
            $i += 1;
        }
        $this->add_checkbox_controller(1, null, null, null);

        $mform->addElement('submit', 'submitbutton',
                get_string('revoke', 'local_obf'),
                array('class' => 'revokebutton'));
    }
}
