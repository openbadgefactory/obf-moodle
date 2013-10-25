<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . '/form/userconfig.php');
require_once(__DIR__ . '/class/backpack.php');

$context = context_system::instance();
$url = new moodle_url('/local/obf/userconfig.php');

require_login();
require_capability('local/obf:configureuser', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$content = $OUTPUT->header();
$backpack = obf_backpack::get_instance($USER);
$form = new obf_userconfig_form($FULLME, array('backpack' => $backpack));

if (($data = $form->get_data())) {
    $email = $data->backpackemail;

    if (isset($data->groupbuttons['selectedgroup'])) {
        $backpack->set_group_id($data->groupbuttons['selectedgroup']);
    }

    $backpack->connect($email);

    redirect($FULLME);
}
$content .= $PAGE->get_renderer('local_obf')->render_userconfig($form);
$content .= $OUTPUT->footer();

echo $content;
