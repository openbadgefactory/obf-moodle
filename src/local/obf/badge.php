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
 * Page for displaying content closely related to badges.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/badge.php');
require_once($CFG->libdir . '/adminlib.php');

$badgeid = optional_param('id', '', PARAM_ALPHANUM);
$action = optional_param('action', 'list', PARAM_ALPHANUM);
$courseid = optional_param('courseid', null, PARAM_INT);
$criteriatype = optional_param('criteriatype', null, PARAM_INT);
$message = optional_param('msg', '', PARAM_TEXT);
$context = empty($courseid) ? context_system::instance() : context_course::instance($courseid);

$url = new moodle_url('/local/obf/badge.php', array('action' => $action));
$badge = empty($badgeid) ? null : obf_badge::get_instance($badgeid);

if (!empty($badgeid)) {
    $url->param('id', $badgeid);
}


// Site context.
if (empty($courseid)) {
    require_login();
} else { // Course context.
    $url->param('courseid', $courseid);
    require_login($courseid);
}

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout(empty($courseid) ? 'admin' : 'course');
$PAGE->set_title(get_string('obf', 'local_obf'));
$PAGE->add_body_class('local-obf');

$content = '';
$hasissuecapability = has_capability('local/obf:issuebadge', $context);

switch ($action) {

    // Show issuance history.
    case 'history':
        require_capability('local/obf:viewhistory', $context);

        $page = optional_param('page', 0, PARAM_INT);

        try {
            $client = obf_client::get_instance();
            $content .= $PAGE->get_renderer('local_obf')->print_badge_info_history(
                    $client, $badge, $context, $page);
        } catch (Exception $e) {
            $content .= $OUTPUT->notification($e->getMessage());
        }
        break;

    // Show the list of badges.
    case 'list':
        require_capability('local/obf:viewallbadges', $context);

        try {
            $badges = obf_badge::get_badges();

            if ($context instanceof context_system) {
                $content .= $PAGE->get_renderer('local_obf')->render_badgelist($badges,
                        $hasissuecapability, $context, $message);
            } else {
                $content .= $PAGE->get_renderer('local_obf')->render_badgelist_course($badges,
                        $hasissuecapability, $context, $message);
            }
        } catch (Exception $e) {
            $content .= $OUTPUT->notification($e->getMessage(), 'notifyproblem');
        }

        break;

    // Display badge info.
    case 'show':
        require_capability('local/obf:viewdetails', $context);

        $client = obf_client::get_instance();
        $page = optional_param('page', 0, PARAM_INT);
        $show = optional_param('show', 'details', PARAM_ALPHANUM);
        $baseurl = new moodle_url('/local/obf/badge.php',
                array('action' => 'show', 'id' => $badgeid));

        if ($context instanceof context_system) {
            navigation_node::override_active_url(new moodle_url('/local/obf/badge.php',
                    array('action' => 'list')));
            $PAGE->navbar->add($badge->get_name(), $baseurl);
        } else {
            navigation_node::override_active_url(new moodle_url('/local/obf/badge.php',
                    array('action' => 'list', 'courseid' => $courseid)));
            $coursebadgeurl = clone $baseurl;
            $coursebadgeurl->param('courseid', $courseid);
            $PAGE->navbar->add($badge->get_name(), $coursebadgeurl);
        }

        $renderer = $PAGE->get_renderer('local_obf', 'badge');
        $content .= $PAGE->get_renderer('local_obf')->render_badge_heading($badge,
                $context);

        switch ($show) {
            // Email template.
            case 'email':
                require_capability('local/obf:configure', $context);

                $emailurl = new moodle_url(
                        '/local/obf/badge.php', array('id' => $badge->get_id(),
                    'action' => 'show', 'show' => 'email'));

                $PAGE->navbar->add(
                        get_string('badgeemail', 'local_obf'), $emailurl);
                $form = new obf_email_template_form(
                        $emailurl, array('badge' => $badge));
                $html = '';

                if (!empty($message)) {
                    $html .= $OUTPUT->notification($message, 'notifysuccess');
                }

                if (!is_null($data = $form->get_data())) {
                    global $DB;

                    $email = is_null($badge->get_email()) ? new obf_email() : $badge->get_email();
                    $email->set_badge_id($badge->get_id());
                    $email->set_subject($data->emailsubject);
                    $email->set_body($data->emailbody);
                    $email->set_footer($data->emailfooter);
                    $email->set_link_text($data->emaillinktext);
                    $email->save($DB);

                    $redirecturl = clone $emailurl;
                    $redirecturl->param(
                            'msg', get_string('emailtemplatesaved', 'local_obf'));

                    redirect($redirecturl);
                }

                $html .= $form->render();
                $content .= $renderer->page($badge, 'email', $html);
                break;

            // Badge details.
            case 'details':
                $taburl = clone $baseurl;
                $taburl->param('show', $show);

                if ($context instanceof context_system) {
                    $PAGE->navbar->add(
                            get_string('badge' . $show, 'local_obf'), $taburl);
                }

                $content .= $PAGE->get_renderer('local_obf')->page_badgedetails(
                        $client, $badge, $context, $show, $page, $message);

                $content .= $PAGE->get_renderer('local_obf')->render_button($badge,
                    $context, 'issue');


                break;

            // Badge criteria.
            case 'criteria':
                $content .= $PAGE->get_renderer('local_obf')->page_badgedetails(
                        $client, $badge, $context, $show, $page, $message);
                break;

            // Badge issuance history.
            case 'history':
                $content .= $PAGE->get_renderer('local_obf')->page_badgedetails(
                        $client, $badge,  $context, $show, $page, $message);
                break;
        }

        break;
}

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();
