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
 * Config form for OAuth2 API authentication.
 *
 * @package    local_obf
 * @copyright  2013-2021, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Plugin config / Authentication form.
 * @copyright  2013-2021, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_config_oauth2_form extends moodleform {
    protected $isadding;

    private $access_token  = '';
    private $token_expires = 0;
    private $client_name   = '';

    function __construct($actionurl, $isadding) {
        $this->isadding = $isadding;
        parent::__construct($actionurl);
    }

    function definition() {
        $mform =& $this->_form;

        // Then show the fields about where this block appears.
        $mform->addElement('header', 'obfeditclientheader', get_string('client', 'local_obf'));

        if ($this->isadding) {
            $mform->addElement('text', 'obf_url', get_string('obfurl', 'local_obf'), array('size' => 60));
            $mform->setType('obf_url', PARAM_URL);
            $mform->addRule('obf_url', null, 'required');

            $mform->addElement('text', 'client_id', get_string('clientid', 'local_obf'), array('size' => 60));
            $mform->setType('client_id', PARAM_NOTAGS);
            $mform->addRule('client_id', null, 'required');

            $mform->addElement('text', 'client_secret', get_string('clientsecret', 'local_obf'), array('size' => 60));
            $mform->setType('client_secret', PARAM_NOTAGS);
            $mform->addRule('client_secret', null, 'required');
        }
        else {

            $mform->addElement('text', 'client_name', get_string('clientname', 'local_obf'), array('size' => 60));
            $mform->setType('client_name', PARAM_NOTAGS);
            $mform->addRule('client_name', null, 'required');

            $mform->addElement('static', 'obf_url', get_string('obfurl', 'local_obf'));
            $mform->addElement('static', 'client_id', get_string('clientid', 'local_obf'));
            $mform->addElement('static', 'client_secret', get_string('clientsecret', 'local_obf'));
        }

        $submitlabel = null; // Default
        if ($this->isadding) {
            $submitlabel = get_string('addnewclient', 'local_obf');
        }
        $this->add_action_buttons(true, $submitlabel);
    }


    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($this->isadding && empty($errors)) {
            try {
                $client = obf_client::get_instance();
                $input = (object) $data;
                $client->set_oauth2($input);
                $res = $client->oauth2_access_token();
                $this->access_token  = $res['access_token'];
                $this->token_expires = $res['token_expires'];

                $issuer = $client->get_issuer();
                $this->client_name = $issuer['name'];
            }
            catch (Exception $e) {
                $errors['client_secret'] = get_string('invalidclientsecret', 'local_obf');
            }
        }

        return $errors;
    }

    function get_data() {
        $data = parent::get_data();
        if ($data && $this->isadding) {
            $data->access_token  = $this->access_token;
            $data->token_expires = $this->token_expires;
            $data->client_name   = $this->client_name;
        }
        return $data;
    }
}
