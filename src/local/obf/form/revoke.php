<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Revoke form.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');
require_once(__DIR__ . '/../renderer.php');
/**
 * Revoke form.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_revoke_form extends local_obf_form_base {
    /**
     * Defines forms elements
     */
    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $assertion = $this->_customdata['assertion'];
        $users = $this->_customdata['users'];
        $showurl = $this->_customdata['showurl'];
        $showrevoke = $this->_customdata['showrevoke'];
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
            if ($showrevoke) {
                $mform->addElement('advcheckbox', 'email['.$i.']', null, $name, $attributes, array(null, $email));
            } else {
                $mform->addElement('html', html_writer::tag('li', $name));
            }

            $i += 1;
        }
        if ($showrevoke && count($users) > 1) {
            $this->add_checkbox_controller(1, null, null, null);
        }
        if ($showrevoke) {
            $mform->addElement('submit', 'submitbutton',
                    get_string('revoke', 'local_obf'),
                    array('class' => 'revokebutton'));
        } else if (!empty($showurl)) {
            $mform->addElement('html', html_writer::tag('div',
                    html_writer::link($showurl, get_string('revokeuserbadges', 'local_obf')) ));
        }

    }
}
