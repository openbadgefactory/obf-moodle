<?php
/**
 * Page for issuing a badge.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/class/badge.php';
require_once __DIR__ . '/form/issuance.php';
require_once $CFG->dirroot . '/user/lib.php';
require_once __DIR__ . '/class/event.php';

$badgeid = required_param('id', PARAM_ALPHANUM);
$courseid = optional_param('courseid', null, PARAM_INT);
$context = !is_null($courseid) ? context_course::instance($courseid) : context_system::instance();
$urlparams = array();

if (!is_null($badgeid)) {
    $urlparams['id'] = $badgeid;
}

// course context
if (!is_null($courseid)) {
    $urlparams['courseid'] = $courseid;
    require_login($courseid);
}
// site context
else {
    require_login();
}

require_capability('local/obf:issuebadge', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/obf/issue.php', $urlparams));
$PAGE->set_title(get_string('obf', 'local_obf'));
$PAGE->set_pagelayout(!is_null($courseid) ? 'course' : 'admin');

$content = $OUTPUT->header();
$badge = obf_badge::get_instance($badgeid);

// fix breadcrumbs
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

// Issuance was cancelled
if ($issuerform->is_cancelled()) {
    // TODO: check referer maybe and redirect there

    if (!empty($courseid)) {
        redirect(new moodle_url('/local/obf/issue.php', array('courseid' => $courseid)));
    } else {
        redirect(new moodle_url('/local/obf/badge.php',
                array('id' => $badge->get_id(), 'action' => 'show',
            'show' => 'details')));
    }
}

// Issuance form was submitted
else if (!is_null($data = $issuerform->get_data())) {
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

    $badge->set_expires($data->expiresby);
    $assertion = obf_assertion::get_instance()
            ->set_badge($badge)
            ->set_emailbody($data->emailbody)
            ->set_emailsubject($data->emailsubject)
            ->set_emailfooter($data->emailfooter)
            ->set_issuedon($data->issuedon)
            ->set_recipients($recipients);

    $success = $assertion->process();

    cache_helper::invalidate_by_event('new_obf_assertion', $userids);

    // Badage was successfully issued.
    if ($success) {
        if (!is_bool($success)) {
            $issuevent = new obf_issue_event($success, $DB);
            $issuevent->set_userid($USER->id);
            $issuevent->save($DB);
        }

        // Course context
        if (!empty($courseid)) {
            redirect(new moodle_url('/local/obf/badge.php',
                    array('action' => 'list', 'courseid' => $courseid,
                'msg' => get_string('badgeissued', 'local_obf'))));
        }
        // Site context
        else {
            redirect(new moodle_url('/local/obf/badge.php',
                    array('id' => $badge->get_id(),
                'action' => 'show', 'show' => 'history', 'msg' => get_string('badgeissued',
                        'local_obf'))));
        }
    }
    // Oh noes, issuance failed.
    else {
        $content .= $OUTPUT->notification('Badge issuance failed. Reason: ' . $assertion->get_error());
    }
}

$content .= $issuerform->render();
$content .= $OUTPUT->footer();
echo $content;
