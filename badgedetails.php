<?php
// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . "/lib.php");

$badgeid = required_param('id', PARAM_ALPHANUM);
$show = optional_param('show', 'details', PARAM_ALPHANUM);
$currentpage = optional_param('page', 0, PARAM_INT);
$context = context_system::instance();

require_login();
require_capability('local/obf:viewdetails', $context);

$badge = obf_badge::get_instance($badgeid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/obf/badgedetails.php', array('id' => $badgeid, 'show' => $show)));
$PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . $badge->get_name());
$PAGE->set_heading(get_string('badgedetails', 'local_obf'));
$PAGE->set_pagelayout('admin');

$navigationurl = new moodle_url('/local/obf/badgelist.php');
navigation_node::override_active_url($navigationurl);
$PAGE->navbar->add($badge->get_name());

echo $PAGE->get_renderer('local_obf')->page_badgedetails($badge, $show, $currentpage);
?>
