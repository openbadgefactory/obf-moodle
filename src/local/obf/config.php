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
 * Plugin configuration page.
 *
 * @package    local_obf
 * @copyright  2013-2021, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/class/client.php');
require_once(__DIR__ . '/form/config_oauth2.php');
require_once(__DIR__ . '/form/settings.php');
require_once(__DIR__ . '/form/badgeexport.php');

$context = context_system::instance();
$action = optional_param('action', 'list', PARAM_TEXT);
$clientid = optional_param('id', 0, PARAM_INT);

$urlparams = $action == 'list' ? array() : array('action' => $action, 'id' => $clientid);

$url = new moodle_url('/local/obf/config.php', $urlparams);

require_login();
require_capability('local/obf:configure', $context);

if (!empty(get_config('local_obf', 'obfclientid'))) {
    redirect(new moodle_url('/local/obf/config_legacy.php'));
    exit();
}

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$listclients = new moodle_url('/local/obf/config.php');


$foo = new curl();

echo $OUTPUT->header();

switch ($action) {
    case 'list':
        $clients = $DB->get_records('local_obf_oauth2', null, $DB->sql_order_by_text('client_name'));

        if (empty($clients)) {
            $new_oauth2 = $CFG->wwwroot . '/local/obf/config.php?action=edit&id=0';
            $new_legacy = $CFG->wwwroot . '/local/obf/config_legacy.php';
            echo '<div class="actionbuttons">';
            echo $OUTPUT->single_button($new_oauth2, get_string('addnewoauth2', 'local_obf'), 'get');
            echo ' &nbsp; ';
            echo $OUTPUT->single_button($new_legacy, get_string('addnewlegacy', 'local_obf'), 'get');
            echo  '</div>';
        }
        else {
            $table = new html_table();

            $table->id = 'obf-displayclients';
            $table->attributes = array('class' => 'local-obf generaltable');

            $table->head = array(
                get_string('clientname', 'local_obf'),
                get_string('obfurl', 'local_obf'),
                get_string('clientid', 'local_obf'),
                get_string('clientsecret', 'local_obf'),
                get_string('actions', 'moodle')
            );

            foreach($clients as $client) {
                $row = new html_table_row();

                $editurl = new moodle_url('/local/obf/config.php?action=edit&id=' . $client->id);
                $editicon = new pix_icon('t/edit', get_string('edit'));
                $editaction = $OUTPUT->action_icon($editurl, $editicon);

                $deleteurl = new moodle_url('/local/obf/config.php?action=delete&id=' . $client->id . '&sesskey=' . sesskey());
                $deleteicon = new pix_icon('t/delete', get_string('delete'));
                $deleteaction = $OUTPUT->action_icon($deleteurl, $deleteicon, new confirm_action(get_string('deleteclientconfirm', 'local_obf')));

                $icons = new html_table_cell($editaction . ' ' . $deleteaction);

                $row->cells = array(
                    $client->client_name,
                    $client->obf_url,
                    $client->client_id,
                    '* * * * * * * * * *' . substr($client->client_secret, -3, 3),
                    $icons);
                $table->data[] = $row;
            }

            echo html_writer::table($table);

            $url = $CFG->wwwroot . '/local/obf/config.php?action=edit&id=0';
            echo '<div class="actionbuttons">' . $OUTPUT->single_button($url, get_string('addnew', 'local_obf'), 'get') . '</div>';

            $settings = new stdClass();
            $settings->disableassertioncache = get_config('local_obf', 'disableassertioncache');
            $settings->coursereset = get_config('local_obf', 'coursereset');
            $settings->usersdisplaybadges = get_config('local_obf', 'usersdisplaybadges');
            $settings->apidataretrieve = get_config('local_obf', 'apidataretrieve');
            $settingsform = new obf_settings_form($FULLME, array('settings' => $settings));

            if (!is_null($data = $settingsform->get_data())) {
                set_config('disableassertioncache', $data->disableassertioncache, 'local_obf');
                set_config('coursereset', $data->coursereset, 'local_obf');
                set_config('usersdisplaybadges', $data->usersdisplaybadges, 'local_obf');
                set_config('apidataretrieve', $data->apidataretrieve, 'local_obf');
                redirect(new moodle_url('/local/obf/config.php',
                    array('msg' => get_string('settingssaved',
                    'local_obf'))));
            }

            echo '<hr>';
            echo $PAGE->get_renderer('local_obf')->render($settingsform);
        }

        break;

    ///

    case 'edit':

        $roles = [];

        if ($clientid) {
            $isadding = false;
            $clientrecord = $DB->get_record('local_obf_oauth2', array('id' => $clientid), '*', MUST_EXIST);
            $client_secret = $clientrecord->client_secret;
            //mask previusly set client secret in config form
            $clientrecord->client_secret = '* * * * * * * * * * * * * * * * * *' . substr($client_secret, -3, 3);

            $roles = $DB->get_fieldset_select('local_obf_oauth2_role', 'role_id', 'oauth2_id = ?', array($clientid));
        }
        else {
            $isadding = true;
            $clientrecord = new stdClass;
            $clientrecord->obf_url = 'https://openbadgefactory.com';
        }

        $mform = new obf_config_oauth2_form($PAGE->url, $isadding, $roles);
        $mform->set_data($clientrecord);

        if ($mform->is_cancelled()) {
            redirect($listclients);

        }
        else if ($data = $mform->get_data()) {

            $roles = [];
            foreach (array_keys((array)$data) as $k) {
                if (preg_match('/^role_(\d+)$/', $k, $m) && $data->$k == 1) {
                    $roles[] = $m[1];
                    unset($data->$k);
                }
            }
            if ($isadding) {
                $clientid = $DB->insert_record('local_obf_oauth2', $data, true);
            } else {
                $clientrecord->client_name = $data->client_name;
                $clientrecord->client_secret = $client_secret;
                $DB->update_record('local_obf_oauth2', $clientrecord);
            }

            $DB->delete_records('local_obf_oauth2_role', array('oauth2_id' => $clientid));
            foreach($roles as $r) {
                $DB->execute('INSERT INTO {local_obf_oauth2_role} (oauth2_id, role_id) VALUES (?,?)', array($clientid, $r));
            }

            redirect($listclients, get_string('clientsaved', 'local_obf'));

        } else {
            $mform->display();
        }
        break;

    ///

    case 'delete':
        if (confirm_sesskey()) {
            $DB->delete_records('local_obf_oauth2', array('id' => $clientid));
        }
        redirect($listclients, get_string('clientdeleted', 'local_obf'));
        break;

    default:
        echo '<p>Not Found</p>';
}

echo $OUTPUT->footer();
