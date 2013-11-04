<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . '/form/userconfig.php');
require_once(__DIR__ . '/class/backpack.php');

$error = optional_param('error', '', PARAM_TEXT);
$context = context_system::instance();
$url = new moodle_url('/local/obf/userconfig.php');

require_login();
require_capability('local/obf:configureuser', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$content = $OUTPUT->header();
$backpack = obf_backpack::get_instance($USER);
$form = new obf_userconfig_form($url, array('backpack' => $backpack));


// Disconnect-button was pressed
if ($form->is_cancelled()) {
    $backpack->disconnect();
    redirect($url);
}

// User configuration was saved.
else if (($data = $form->get_data())) {

    $email = $data->backpackemail;

    if (isset($data->backpackgroups)) {
        $backpack->set_groups(array_keys($data->backpackgroups));
    }

    $redirecturl = clone $url;

    try {
        $backpack->connect($email);
    }
    catch (Exception $e) {
        $redirecturl->param('error', $e->getMessage());
    }


    redirect($redirecturl);
}
$content .= $PAGE->get_renderer('local_obf')->render_userconfig($form, $error);
$content .= $OUTPUT->footer();

echo $content;
