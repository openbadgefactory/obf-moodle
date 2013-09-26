<?php
// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . "/lib.php");

$badgeid = required_param('id', PARAM_ALPHANUM);

require_login();

$badge = obf_badge::get_instance($badgeid);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/obf/badgedetails.php', array('id' => $badgeid)));
$PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . $badge->get_name());
$PAGE->set_heading($badge->get_name());
$PAGE->set_pagelayout('admin');

$navigationurl = new moodle_url('/local/obf/badgelist.php');
navigation_node::override_active_url($navigationurl);
$PAGE->navbar->add($badge->get_name());

echo $OUTPUT->header();
$output = $PAGE->get_renderer('local_obf');
echo $output->print_badge_details($badge);
echo $OUTPUT->footer();
?>
