<?php
/**
 * Plugin configuration page.
 */
require_once __DIR__ . '/../../config.php';
require_once $CFG->libdir . '/adminlib.php';
require_once __DIR__ . '/form/config.php';
require_once __DIR__ . '/form/badgeexport.php';
require_once __DIR__ . '/class/client.php';

$context = context_system::instance();
$msg = optional_param('msg', '', PARAM_TEXT);
$action = optional_param('action', 'authenticate', PARAM_TEXT);
$url = new moodle_url('/local/obf/config.php', array('action' => $action));
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

        if (!is_null($data = $form->get_data())) {

            // Deauthentication
            if (isset($data->deauthenticate) && $data->deauthenticate == 1) {
                $client->deauthenticate();
                redirect(new moodle_url('/local/obf/config.php'),
                        get_string('deauthenticationsuccess', 'local_obf'));
            }

            // OBF request token is set, (re)do authentication.
            else if (!empty($data->obftoken)) {

                try {
                    $client->authenticate($data->obftoken);

                    if ($badgesupport) {
                        require_once($CFG->libdir . '/badgeslib.php');

                        $badges = array_merge(badges_get_badges(BADGE_TYPE_COURSE),
                                badges_get_badges(BADGE_TYPE_SITE));

                        // Redirect to page where the user can export existing
                        // badges to OBF and change some settings.
                        redirect(new moodle_url('/local/obf/config.php',
                                array('action' => 'exportbadges')));
                    }

                    // No local badges, no need to export
                    redirect(new moodle_url('/local/obf/config.php',
                            array('msg' => get_string('authenticationsuccess',
                                'local_obf'))));
                } catch (Exception $e) {
                    $content .= $OUTPUT->notification($e->getMessage());
                }
            }
            else {
                redirect(new moodle_url('/local/obf/config.php'));
            }
        }

        if (!empty($msg)) {
            $content .= $OUTPUT->notification(s($msg), 'notifysuccess');
        }

        $content .= $PAGE->get_renderer('local_obf')->render($form);
        break;

    // Let the user select the badges that can be exported to OBF
    case 'exportbadges':

        require_once($CFG->libdir . '/badgeslib.php');

        $badges = array_merge(badges_get_badges(BADGE_TYPE_COURSE),
                badges_get_badges(BADGE_TYPE_SITE));
        $exportform = new obf_badge_export_form($FULLME,
                array('badges' => $badges));

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
                                    'image' => base64_encode(file_get_contents(moodle_url::make_pluginfile_url($badge->get_context()->id,
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

            // Disable Moodle's own badge system
            if ($data->disablemoodlebadges) {
                set_config('enablebadges', 0);
            }

            redirect($url);
        }

        $content .= $PAGE->get_renderer('local_obf')->render_badge_exporter($exportform);
        break;
}

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();
