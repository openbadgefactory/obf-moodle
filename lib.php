<?php

defined('MOODLE_INTERNAL') || die();

define('OBF_DEFAULT_ADDRESS', 'https://192.168.1.23/obf/');

require_once(__DIR__ . '/class/criterion/criterionbase.php');

function local_obf_course_completed(stdClass $eventdata) {
    global $DB;

    $user = $DB->get_record('user', array('id' => $eventdata->userid));
    $recipients = array($user->email);

    // Get all criteria related to course completion
    $typeid = obf_criterion_base::CRITERIA_TYPE_COURSESET;
    $criteria = obf_criterion_base::get_criteria(array('c.criterion_type_id' => $typeid));

    foreach ($criteria as $criterionid => $criterion) {
        // User has already met this criterion
        if ($criterion->is_met_by_user($user)) {
            continue;
        }

        // Has the user completed all the required criteria (completion/grade/date)
        // in this criterion?
        $criteriamet = $criterion->review($eventdata);

        if ($criteriamet) {
            $badge = $criterion->get_badge();
            $email = is_null($badge->get_email()) ? new obf_email() : $badge->get_email();

            $badge->issue($recipients, time(), $email->get_subject(), $email->get_body(),
                    $email->get_footer());
            $criterion->set_met_by_user($user);
        }
    }

    return true;
}

function local_obf_course_deleted(stdClass $course) {
    obf_criterion_courseset::delete_course_criteria($course->id);
    return true;
}

function local_obf_extends_settings_navigation(settings_navigation $navigation) {
    global $COURSE;

    if (($branch = $navigation->get('courseadmin'))) {
        $obfnode = navigation_node::create(get_string('obf', 'local_obf'));
        $obfnode->add(get_string('badgelist', 'local_obf'),
                new moodle_url('/local/obf/badge.php',
                array('action' => 'list', 'courseid' => $COURSE->id)));
        $branch->add_node($obfnode, 'backup');
    } else if (($branch = $navigation->get('usercurrentsettings'))) {
        $node = navigation_node::create(get_string('obf', 'local_obf'),
                        new moodle_url('/local/obf/userconfig.php'));
        $branch->add_node($node);
    }
}

function local_obf_cron() {
    global $CFG;

    require_once($CFG->libdir . '/messagelib.php');
    require_once($CFG->libdir . '/datalib.php');

    $certexpiresin = obf_client::get_instance()->get_certificate_expiration_date();
    $diff = $certexpiresin - time();
    $days = floor($diff / (60 * 60 * 24));
    $notify = in_array($days, array(30, 25, 20, 15, 10, 5, 4, 3, 2, 1));

    if (!$notify) {
        return true;
    }

    $severity = $days <= 5 ? 'errors' : 'notices';
    $admins = get_admins();
    $textparams = new stdClass();
    $textparams->days = $days;
    $textparams->obfurl = OBF_DEFAULT_ADDRESS;

    foreach ($admins as $admin) {
        $eventdata = new object();
        $eventdata->component = 'moodle';
        $eventdata->name = $severity;
        $eventdata->userfrom = $admin;
        $eventdata->userto = $admin;
        $eventdata->subject = get_string('expiringcertificatesubject', 'local_obf');
        $eventdata->fullmessage = get_string('expiringcertificate', 'local_obf', $textparams);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = get_string('expiringcertificate', 'local_obf', $textparams);
        $eventdata->smallmessage = get_string('expiringcertificatesubject', 'local_obf');

        $result = message_send($eventdata);
    }

    return true;
}
