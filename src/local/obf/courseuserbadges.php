<?php
/**
 * Page for displaying the badges of course participants.
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/class/event.php';

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'badges', PARAM_ALPHANUM);
$url = new moodle_url('/local/obf/courseuserbadges.php',
        array('courseid' => $courseid, 'action' => $action));
$context = context_course::instance($courseid);

require_login($courseid);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$content = $OUTPUT->header();

switch ($action) {
    case 'badges':
        require_capability('local/obf:seeparticipantbadges', $context);
        $participants = get_enrolled_users($context, 'local/obf:earnbadge', 0, 'u.*', null, 0, 0, true);
        $content .= $PAGE->get_renderer('local_obf')->render_course_participants($courseid, $participants);
        break;

    case 'history':
        require_capability('local/obf:viewhistory', $context);
        $relatedevents = obf_issue_event::get_events_in_course($courseid, $DB);
        $client = new obf_client();
        $content .= $PAGE->get_renderer('local_obf')->print_badge_info_history($client, null, $context, 0, $relatedevents);
        break;
}

$content .= $OUTPUT->footer();

echo $content;
