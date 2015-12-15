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
 * Page for issuing a badge.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/badge.php');
require_once(__DIR__ . '/form/issuance.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once(__DIR__ . '/class/event.php');

$badgeid = required_param('id', PARAM_ALPHANUM);
$courseid = optional_param('courseid', null, PARAM_INT);
$context = !is_null($courseid) ? context_course::instance($courseid) : context_system::instance();
$urlparams = array();

if (!is_null($badgeid)) {
    $urlparams['id'] = $badgeid;
}

// Course context.
if (!is_null($courseid)) {
    $urlparams['courseid'] = $courseid;
    require_login($courseid);
} else { // Site context.
    require_login();
}

require_capability('local/obf:issuebadge', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/obf/issue.php', $urlparams));
$PAGE->set_title(get_string('obf', 'local_obf'));
$PAGE->set_pagelayout(!is_null($courseid) ? 'course' : 'admin');

$PAGE->requires->jquery_plugin('obf-simplemde', 'local_obf');
$PAGE->requires->jquery_plugin('obf-criteria-markdown', 'local_obf');

$content = $OUTPUT->header();
$badge = obf_badge::get_instance($badgeid);

// Fix breadcrumbs.
navigation_node::override_active_url(new moodle_url('/local/obf/badge.php',
        array('action' => 'list')));
$PAGE->navbar->add($badge->get_name(),
        new moodle_url('/local/obf/badge.php',
        array('action' => 'show',
    'id' => $badgeid, 'show' => 'details')));
$PAGE->navbar->add(get_string('issue', 'local_obf'));

$url = new moodle_url('/local/obf/issue.php', array('id' => $badgeid));

if (!is_null($courseid)) {
    $url->param('courseid', $courseid);
}

$issuerform = new obf_issuance_form($url,
        array('badge' => $badge, 'courseid' => $courseid, 'renderer' => $PAGE->get_renderer('local_obf')));

// Issuance was cancelled.
if ($issuerform->is_cancelled()) {
    // TODO: Check referer maybe and redirect there.

    if (!empty($courseid)) {
        redirect(new moodle_url('/local/obf/issue.php', array('courseid' => $courseid)));
    } else {
        redirect(new moodle_url('/local/obf/badge.php',
                array('id' => $badge->get_id(), 'action' => 'show',
            'show' => 'details')));
    }
} else if (!is_null($data = $issuerform->get_data())) { // Issuance form was submitted.
    $users = user_get_users_by_id($data->recipientlist);
    $recipients = array();
    $userids = array();

    foreach ($users as $user) {
        $userids[] = $user->id;
    }

    $backpackemails = obf_backpack::get_emails_by_userids($userids);

    foreach ($users as $user) {
        $recipients[] = isset($backpackemails[$user->id]) ? $backpackemails[$user->id] : $user->email;
    }
    
    if (isset($data->criteriaaddendum) && isset($data->addcriteriaaddendum) && true == $data->addcriteriaaddendum) {
        $criteriaaddendum = $data->criteriaaddendum;
    } else {
        $criteriaaddendum = '';
    }

    $badge->set_expires($data->expiresby);
    $assertion = obf_assertion::get_instance()->set_badge($badge);
    $assertion->set_issuedon($data->issuedon)->set_recipients($recipients);
    $assertion->set_criteria_addendum($criteriaaddendum);
    $assertion->get_email_template()->set_subject($data->emailsubject)->set_footer($data->emailfooter)->set_body($data->emailbody)->set_link_text($data->emaillinktext);

    $success = $assertion->process();

    cache_helper::invalidate_by_event('new_obf_assertion', $userids);

    // Badage was successfully issued.
    if ($success) {
        if (!is_bool($success)) {
            $issuevent = new obf_issue_event($success, $DB);
            $issuevent->set_userid($USER->id);
            $issuevent->save($DB);
        }

        // Course context.
        if (!empty($courseid)) {
            redirect(new moodle_url('/local/obf/badge.php',
                    array('action' => 'list', 'courseid' => $courseid,
                'msg' => get_string('badgeissued', 'local_obf'))));
        } else { // Site context.
            redirect(new moodle_url('/local/obf/badge.php',
                    array('id' => $badge->get_id(),
                'action' => 'show', 'show' => 'history', 'msg' => get_string('badgeissued',
                        'local_obf'))));
        }
    } else { // Oh noes, issuance failed.
        $content .= $OUTPUT->notification('Badge issuance failed. Reason: ' . $assertion->get_error());
    }
}

$content .= $issuerform->render();
$content .= $OUTPUT->footer();
echo $content;
