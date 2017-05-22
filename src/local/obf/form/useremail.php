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
 * User config form.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');
require_once(__DIR__ . '/../renderer.php');
/**
 * User email verify form.
 *
 * @copyright  2013-2016, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_user_email_form extends local_obf_form_base {
    /**
     * Defines forms elements
     */
    protected function definition() {
        global $OUTPUT;

        /* @var $mform obf_user_email_form */
        $mform = $this->_form;

        $modal_title = get_string('addemailheader', 'local_obf');
        $mform->addElement('html', '<div class="modal-dialog"><div class="modal-content">');
        $mform->addElement('html', '<div class="modal-header">'.
            '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'.
            '<h4 class="modal-title" id="verifyEmailModalLabel">'.$modal_title.'</h4>'.
        '</div>');
        $mform->addElement('html', '<div class="modal-body">');

        // Step 1
        $mform->addElement('html', '<div class="step step-one">');
        $mform->addElement('html', '<p>' . get_string('addemaildescription', 'local_obf') . '</p>');
        // Type your email address. A verification code will be sent to that address.
        $mform->addElement('text', 'email', get_string('email'));
        $mform->addElement('submit', 'create_token_button', get_string('add'), array('class' => 'create-token'));
        $mform->addElement('html', '</div>');

        // Step 2
        $mform->addElement('html', '<div class="step step-two hide">');
        $mform->addElement('html', '<p>' . get_string('verifytokendescription', 'local_obf') . '</p>');
        //An email has been sent to the provided address. Check your email for a verification code.
        $mform->addElement('text', 'token', get_string('verifytoken', 'local_obf'));
        $mform->addElement('submit', 'verify_token_button', get_string('verifytokenbutton', 'local_obf'), array('class' => 'verify-token'));
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<div class="step step-three status hide">');
        $mform->addElement('html', '<div class="message body">{{ message }}</div>');
        $mform->addElement('html', '</div>');
        $mform->setType('email', PARAM_EMAIL);
        $mform->setType('token', PARAM_TEXT);

        $mform->addElement('html', '</div>'); // modal-body
        $mform->addElement('html', '</div></div>'); // modal-content + modal-dialog
    }
}
