<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . '/class/badge.php');
require_once(__DIR__ . '/form/issuance.php');
require_once($CFG->dirroot . '/user/lib.php');

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

$content = $OUTPUT->header();

//$content = $OUTPUT->header();
$issuerform = new obf_issuance_form(new moodle_url('/local/obf/issue.php', array('id' => $badgeid)),
        array('badge' => $badge, 'renderer' => $PAGE->get_renderer('local_obf')));


if ($issuerform->is_cancelled()) {
    // TODO: check referer maybe and redirect there
    redirect(new moodle_url('/local/obf/badge.php', array('id' => $badge->get_id(), 'action' => 'show',
        'show' => 'details')));    
    
} else if (!is_null($data = $issuerform->get_data())) {
    $emailsubject = $data->emailsubject;
    $emailbody = $data->emailbody;
    $emailfooter = $data->emailfooter;
    $issuedon = $data->issuedon;
    $expiresby = $data->expiresby;
    $recipient_ids = $data->recipientlist;

    $users = user_get_users_by_id($recipient_ids);
    $recipients = array();

    foreach ($users as $user) {
        $recipients[] = $user->email;
    }

    $badge->set_expires($expiresby);
    $issuance = obf_issuance::get_instance()
            ->set_badge($badge)
            ->set_emailbody($emailbody)
            ->set_emailsubject($emailsubject)
            ->set_emailfooter($emailfooter)
            ->set_issuedon($issuedon)
            ->set_recipients($recipients);

    $success = $issuance->process();

    if ($success) {
        redirect(new moodle_url('/local/obf/badge.php',
                array('id' => $badge->get_id(),
            'action' => 'show', 'show' => 'history', 'msg' => get_string('badgeissued', 'local_obf'))));
    } else {
        $content .= $OUTPUT->notification('Badge issuance failed. Reason: ' . $issuance->get_error());
    }
}

$content .= $issuerform->render();
$content .= $OUTPUT->footer();

echo $content;
?>
