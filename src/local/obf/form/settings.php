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
 * Settings form.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');

/**
 * Description of settings
 *
 * @author jsuorsa
 */
class obf_settings_form extends local_obf_form_base implements renderable {
    //put your code here
    /**
     * Form definition.
     */
    protected function definition() {
        require_once(__DIR__ . '/../class/user_preferences.php');
        global $OUTPUT;

        $mform = $this->_form;
        $settings = $this->_customdata['settings'];

        $mform->addElement('advcheckbox', 'disableassertioncache', get_string('disableassertioncache', 'local_obf'));
        $mform->setType('disableassertioncache', PARAM_INT);
        $mform->addHelpButton('disableassertioncache', 'disableassertioncache', 'local_obf');
        
        $mform->addElement('advcheckbox', 'coursereset', get_string('coursereset', 'local_obf'));
        $mform->setType('coursereset', PARAM_INT);
        
        
        $badgedisplayoptions = array(
            obf_user_preferences::USERS_CAN_MANAGE_DISPLAY_OF_BADGES => get_string('userscanmanagedisplayofbadges', 'local_obf'),
            obf_user_preferences::USERS_FORCED_TO_DISPLAY_BADGES => get_string('usersforcedtodisplaybadges', 'local_obf'),
            obf_user_preferences::USERS_NOT_ALLOWED_TO_DISPLAY_BADGES => get_string('usersnotallowedtodisplaybadges', 'local_obf')
        );
        $mform->addElement('select', 'usersdisplaybadges', get_string('usersdisplaybadges', 'local_obf'), $badgedisplayoptions);
        
        $apiassertionoptions = array(
            obf_client::RETRIEVE_LOCAL => get_string('apidataretrievelocal', 'local_obf'),
            obf_client::RETRIEVE_ALL => get_string('apidataretrieveall', 'local_obf')
            
        );
        $mform->addElement('select', 'apidataretrieve', get_string('apidataretrieve', 'local_obf'), $apiassertionoptions);
        
        $mform->addElement('submit', 'submitbutton',
                get_string('savesettings', 'local_obf'));
        $this->set_data($settings);
    }
}
