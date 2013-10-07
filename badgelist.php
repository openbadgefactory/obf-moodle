<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';

require_login();

$reload = optional_param('reload', false, PARAM_BOOL);

$PAGE->set_context(context_system::instance()); // TODO: context?
$PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . get_string('badgelist', 'local_obf'));
$PAGE->set_heading(get_string('badgelisttitle', 'local_obf'));
$PAGE->set_url(new moodle_url('/local/obf/badgelist.php'));
$PAGE->set_pagelayout('admin');

require_capability('local/obf:viewallbadges', $PAGE->context);

echo $PAGE->get_renderer('local_obf')->page_badgelist($reload);
