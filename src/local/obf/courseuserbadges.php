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
require_once(__DIR__ . '/class/badge.php');
require_once(__DIR__ . '/class/event.php');

$badgeid = optional_param('id', '', PARAM_ALPHANUM);
$courseid = optional_param('courseid', 1, PARAM_INT);
$action = optional_param('action', 'badges', PARAM_ALPHANUM);
$url = new moodle_url('/local/obf/courseuserbadges.php',
        array('courseid' => $courseid, 'action' => $action));
$curr_page = optional_param('page', '0', PARAM_INT);
$context = context_course::instance($courseid);
$badge = empty($badgeid) ? null : obf_badge::get_instance($badgeid);
$onlydetailstab = 1;

require_login($courseid);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

switch ($action) {
    // Display badge info.
    case 'show':
        require_capability('local/obf:viewdetails', $context);
        $client = obf_client::get_instance();
        $show = optional_param('show', 'details', PARAM_ALPHANUM);
        $coursebadgeurl =  new moodle_url('/local/obf/courseuserbadges.php',
            array('action' => 'show', 'id' => $badgeid));

        $PAGE->navbar->add(get_string('siteadmin', 'local_obf'),
            new moodle_url('/admin/search.php'));
        $PAGE->navbar->add(get_string('obf', 'local_obf'),
            new moodle_url('/admin/category.php', array('category' => 'obf')));
        $PAGE->navbar->add(get_string('badgelist', 'local_obf'),
            new moodle_url('/local/obf/badge.php', array('action' => 'list')));

        $PAGE->navbar->add($badge->get_name(), $coursebadgeurl);
        
        $content .= $PAGE->get_renderer('local_obf')->render_badge_heading($badge,
            $context);

        switch ($show) {
            // Badge details.
            case 'details':
                $content .= $PAGE->get_renderer('local_obf')->page_badgedetails(
                    $client, $badge, $context, $show, $page, $message, $onlydetailstab);
        }
        break;

    case 'badges':
        require_capability('local/obf:seeparticipantbadges', $context);
        $participants = get_enrolled_users($context, 'local/obf:earnbadge', 0, 'u.*', null, 0, 0, true);
        $content .= $PAGE->get_renderer('local_obf')->render_course_participants($courseid, $participants);
        break;

    case 'history':
        require_capability('local/obf:viewhistory', $context);
        $relatedevents = obf_issue_event::get_events_in_course($courseid, $DB);
        $client = new obf_client();
        $allevents = $client->get_assertions();
        $events = array();

        foreach ($allevents as $event) {
            if($event["log_entry"]["course_id"] == $courseid ) {
                $events[] = $event["id"];
            }
        }

        if (count($events) >= 1) {
            $relatedevents = obf_issue_event::get_course_related_events($events, $DB);
        }
        $content .= $PAGE->get_renderer('local_obf')->print_badge_info_history($client, null, $context, $curr_page, $relatedevents);
        break;
}

echo $OUTPUT->header();
$content .= $OUTPUT->footer();
echo $content;
