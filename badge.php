<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . "/lib.php");

$badgeid = optional_param('id', '', PARAM_ALPHANUM);
$action = optional_param('action', 'list', PARAM_ALPHANUM);
$badge = empty($badgeid) ? null : obf_badge::get_instance($badgeid);
$context = context_system::instance();
$content = '';
$url = new moodle_url('/local/obf/badge.php', array('id' => $badgeid, 'action' => $action));

require_login();

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

// TODO: fix breadcrumbs

switch ($action) {

    // show issuance history
    case 'history':
        require_capability('local/obf:viewhistory', $context);

        $page = optional_param('page', 0, PARAM_INT);
        $PAGE->set_title(get_string('obf', 'local_obf' . ' - ' . get_string('history', 'local_obf')));
        $PAGE->set_heading(get_string('history', 'local_obf'));
        $content = $PAGE->get_renderer('local_obf')->page_history($badge, $page);
        break;

    // show the list of badges
    case 'list':
        require_capability('local/obf:viewallbadges', $context);

        $reload = optional_param('reload', false, PARAM_BOOL);
        $PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . get_string('badgelist', 'local_obf'));
        $PAGE->set_heading(get_string('badgelisttitle', 'local_obf'));
        $content = $PAGE->get_renderer('local_obf')->page_badgelist($reload);
        break;

    // display badge info
    case 'show':
        require_capability('local/obf:viewdetails', $context);

        $page = optional_param('page', 0, PARAM_INT);
        $show = optional_param('show', 'details', PARAM_ALPHANUM);
        $PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . $badge->get_name());
        $PAGE->set_heading(get_string('badgedetails', 'local_obf'));
        $content = $PAGE->get_renderer('local_obf')->page_badgedetails($badge, $show, $page);
        break;
}

echo $content;
?>
