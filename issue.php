<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . "/class/badge.php");

$badgeid = required_param('id', PARAM_ALPHANUM);
$context = context_system::instance();
$title = get_string('issuebadge', 'local_obf');

require_login();
require_capability('local/obf:issuebadge', $context);

$badge = obf_badge::get_instance($badgeid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/obf/issue.php', array('id' => $badgeid)));
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('admin');

echo $PAGE->get_renderer('local_obf')->page_issue($badge);
?>
