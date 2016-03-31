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
 * Config form.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');
/**
 * Plugin config / Authentication form.
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_config_form extends local_obf_form_base implements renderable {

    /**
     * Form definition.
     */
    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $client = $this->_customdata['client'];
        $errorcode = $client->test_connection();
        $url = $client->default_url();  //added
        // Connection to API is working.



        $formdata = $this->get_data();
        if ($errorcode === -1) {
            $expires = userdate($client->get_certificate_expiration_date(),
                    get_string('dateformatdate', 'local_obf'));
            $mform->addElement('html',
                    $OUTPUT->notification(get_string('connectionisworking',
                                    'local_obf', $expires), 'notifysuccess'));
            $mform->addElement('hidden', 'deauthenticate', 1);
            $mform->setType('deauthenticate', PARAM_INT);
            $mform->addElement('submit', 'submitbutton',
                    get_string('deauthenticate', 'local_obf'));
        } else { // Connection is not working.

            // We get error code 0 if pinging the API fails (like if the keyfiles
            // are missing). In plugin config we should show a more spesific
            // error to admin, so let's do that by changing the error code.
            $errorcode = $errorcode == 0 ? OBF_API_CODE_NO_CERT : $errorcode;
            $mform->addElement('html',
                    $OUTPUT->notification(get_string('apierror' . $errorcode,
                                    'local_obf'), 'redirectmessage'));
            $urlgroup = array();
            $urlgroup[] =& $mform->createElement('text', 'url');
            $urlgroup[] =& $mform->createElement('checkbox', 'availablefromenabled', '', get_string('edit', 'local_obf'));
            $mform->addGroup($urlgroup, 'urlgroup',  get_string('urlgroup', 'local_obf'), ' ', false);
            $mform->disabledIf('urlgroup', 'availablefromenabled');
            $mform->setType('url',  PARAM_TEXT);
            $mform->setDefault('url', $url);
            $mform->addHelpButton('urlgroup', 'urlgroup', 'local_obf');;
            $mform->addElement('textarea', 'obftoken',
                    get_string('requesttoken', 'local_obf'), array('rows' => 10));
            $mform->addHelpButton('obftoken', 'requesttoken', 'local_obf');
            $buttonarray = array(
                $mform->createElement('submit', 'submitbutton',
                        get_string('authenticate', 'local_obf')));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        }
    }

}
