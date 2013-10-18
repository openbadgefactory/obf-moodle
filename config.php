<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/form/config.php');

//admin_externalpage_setup('obfconfig');

$context = context_system::instance();
$url = new moodle_url('/local/obf/config.php');
$msg = optional_param('msg', '', PARAM_TEXT);

require_login();
require_capability('local/obf:configure', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$content = $OUTPUT->header();
$form = new obf_config_form($FULLME);

if (!is_null($data = $form->get_data())) {
    
    if (!empty($data->obfurl)) {
        set_config('obfurl', $data->obfurl, 'local_obf');
    }
    if (!empty($data->obftoken)) {
        $client = obf_client::get_instance();

        try {
            $client->authenticate($data->obftoken);
            redirect(new moodle_url('/local/obf/config.php',
                    array('msg' => get_string('authenticationsuccess', 'local_obf'))));
        } catch (Exception $e) {
            $content .= $OUTPUT->notification($e->getMessage());
        }
    }
}

if (!empty($msg)) {
  $content .= $OUTPUT->notification(s($msg), 'notifysuccess');
}

$content .= $PAGE->get_renderer('local_obf')->render($form);
$content .= $OUTPUT->footer();
echo $content;