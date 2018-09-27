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
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/form/config.php');
require_once(__DIR__ . '/form/settings.php');
require_once(__DIR__ . '/form/badgeexport.php');
require_once(__DIR__ . '/class/client.php');

$context = context_system::instance();
$msg = optional_param('msg', '', PARAM_TEXT);
$action = optional_param('action', 'authenticate', PARAM_TEXT);
$url = new moodle_url('/local/obf/config.php', $action != 'authenticate' ? array('action' => $action) : array());
$client = obf_client::get_instance();
$badgesupport = file_exists($CFG->libdir . '/badgeslib.php');

require_login();
require_capability('local/obf:configure', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$content = '';

switch ($action) {

    // Handle authentication.
    case 'authenticate':
        $form = new obf_config_form($FULLME, array('client' => $client));
        if ($client->has_client_id()) {
            $settings = new stdClass();
            $settings->disableassertioncache = get_config('local_obf', 'disableassertioncache');
            $settings->coursereset = get_config('local_obf', 'coursereset');
            $settings->usersdisplaybadges = get_config('local_obf', 'usersdisplaybadges');
            $settingsform = new obf_settings_form($FULLME, array('settings' => $settings));
        }
        
        if (!is_null($data = $form->get_data())) {
            // Deauthentication.
            if (isset($data->deauthenticate) && $data->deauthenticate == 1) {
                $client->deauthenticate();
                redirect(new moodle_url('/local/obf/config.php'),
                        get_string('deauthenticationsuccess', 'local_obf'));
            } else if (!empty($data->obftoken) && !empty($data->url) ) { // OBF request token is set, (re)do authentication.
                try {

                    $client->authenticate($data->obftoken,$data->url);
                    
                    try {
                        $client_info = $client->get_client_info();
                        $images = array('verified_by', 'issued_by');
                        set_config('verified_client', $client_info['verified'] == 1, 'local_obf');
                        foreach($images as $imagename) {
                            $imageurl = $client->get_branding_image_url($imagename);
                            set_config($imagename . '_image_url', $imageurl, 'local_obf');
                        }
                    } catch (Exception $ex) {
                        error_log(var_export($ex, true));
                    }
                    if ($badgesupport) {
                        require_once($CFG->libdir . '/badgeslib.php');

                        $badges = array_merge(badges_get_badges(BADGE_TYPE_COURSE),
                                badges_get_badges(BADGE_TYPE_SITE));
                        

                        // Redirect to page where the user can export existing
                        // badges to OBF and change some settings.
                        redirect(new moodle_url('/local/obf/config.php',
                                array('action' => 'exportbadges')));
                    }

                    // No local badges, no need to export.
                    redirect(new moodle_url('/local/obf/config.php',
                            array('msg' => get_string('authenticationsuccess',
                                'local_obf'))));
                } catch (Exception $e) {
                    $content .= $OUTPUT->notification($e->getMessage());
                }
            } else {
                redirect(new moodle_url('/local/obf/config.php'));
            }
        }
        
        if (!empty($msg)) {
            $content .= $OUTPUT->notification(s($msg), 'notifysuccess');
        }

        $content .= $PAGE->get_renderer('local_obf')->render($form);
        
        if (isset($settingsform)) { 
            if (!is_null($data = $settingsform->get_data())) {
                set_config('disableassertioncache', $data->disableassertioncache, 'local_obf');
                set_config('coursereset', $data->coursereset, 'local_obf');
                set_config('usersdisplaybadges', $data->usersdisplaybadges, 'local_obf');
                redirect(new moodle_url('/local/obf/config.php',
                            array('msg' => get_string('settingssaved',
                                'local_obf'))));
            }
            $content .= $PAGE->get_renderer('local_obf')->render($settingsform);
        }
        
        break;

    // Let the user select the badges that can be exported to OBF.
    case 'exportbadges':

        require_once($CFG->libdir . '/badgeslib.php');

        $badges = array_merge(badges_get_badges(BADGE_TYPE_COURSE),
                badges_get_badges(BADGE_TYPE_SITE));
        try {
            $obfbadges = obf_badge::get_badges();
        } catch (Exception $e) {
            $content .= $OUTPUT->notification($e->getMessage(), 'notifyproblem');
            break;
        }
        $exportform = new obf_badge_export_form($FULLME,
                array('badges' => $badges, 'obfbadges' => $obfbadges));

        if (!is_null($data = $exportform->get_data())) {
            // At least one badge has been selected to be included in exporting.
            if (isset($data->toexport)) {

                // Export each selected badge separately.
                foreach ($data->toexport as $badgeid => $doexport) {
                    // Just to be sure the value of the checkbox is "1" and not "0", although
                    // technically that shouldn't be possible (those shouldn't be included).
                    if ($doexport) {
                        $badge = new badge($badgeid);

                        $email = new obf_email();
                        $email->set_body($badge->message);
                        $email->set_subject($badge->messagesubject);

                        $obfbadge = obf_badge::get_instance_from_array(array(
                                    'name' => $badge->name,
                                    'criteria_html' => '',
                                    'css' => '',
                                    'expires' => null,
                                    'id' => null,
                                    'tags' => array(),
                                    'ctime' => null,
                                    'description' => $badge->description,
                                    'image' => base64_encode(file_get_contents(
                                            moodle_url::make_pluginfile_url($badge->get_context()->id,
                                                    'badges',
                                                    'badgeimage',
                                                    $badge->id, '/',
                                                    'f1', false))),
                                    'draft' => $data->makedrafts
                        ));
                        $obfbadge->set_email($email);
                        $success = $obfbadge->export($client);

                        if (!$success) {
                            debugging('Exporting badge ' . $badge->name . ' failed.');
                            // Exporting badge probably failed. Do something?
                        }
                    }
                }
            }

            // Disable Moodle's own badge system.
            if ($data->disablemoodlebadges) {
                set_config('enablebadges', 1);
                set_config('disablemoodlebadges', 1, 'local_obf');
            } else {
                set_config('enablebadges', 0);
                set_config('disablemoodlebadges', 0, 'local_obf');
            }
            
            if (isset($data->displaymoodlebadges)) {
                set_config('displaymoodlebadges', $data->displaymoodlebadges, 'local_obf');
            }
            $url->param('action', 'authenticate');
            redirect($url);
        }

        $content .= $PAGE->get_renderer('local_obf')->render_badge_exporter($exportform);
        break;
}

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();
