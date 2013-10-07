<?php
// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
//require_once(__DIR__ . "/lib.php");

$currentpage = optional_param('page', 0, PARAM_INT);

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/obf/history.php'));
$PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . get_string('history', 'local_obf'));
$PAGE->set_heading(get_string('history', 'local_obf'));
$PAGE->set_pagelayout('admin');

require_capability('local/obf:viewhistory', $PAGE->context);

echo $PAGE->get_renderer('local_obf')->page_history(null, $currentpage);
?>
