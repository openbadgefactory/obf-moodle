<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Page for displaying the badges of course participants.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/event.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'badges', PARAM_ALPHANUM);
$url = new moodle_url('/local/obf/courseuserbadges.php',
        array('courseid' => $courseid, 'action' => $action));
$curr_page = optional_param('page', '0', PARAM_INT);
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
        $content .= $PAGE->get_renderer('local_obf')->print_badge_info_history($client, null, $context, $curr_page, $relatedevents);
        break;
}

$content .= $OUTPUT->footer();

echo $content;
