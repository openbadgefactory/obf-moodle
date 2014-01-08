<?php
/**
 * Page for displaying the badges of course participants.
 */
require_once __DIR__ . '/../../config.php';

$courseid = required_param('courseid', PARAM_INT);
$url = new moodle_url('/local/obf/courseuserbadges.php');
$context = context_course::instance($courseid);

require_login($courseid);
require_capability('local/obf:seeparticipantbadges', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$participants = get_enrolled_users($context, 'local/obf:earnbadge', 0, 'u.*', null, 0, 0, true);

$content = $OUTPUT->header();
$content .= $PAGE->get_renderer('local_obf')->render_course_participants($courseid, $participants);
$content .= $OUTPUT->footer();

echo $content;