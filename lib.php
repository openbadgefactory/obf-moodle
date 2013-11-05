<?php

defined('MOODLE_INTERNAL') || die();

define('OBF_DEFAULT_ADDRESS', 'https://elvis.discendum.com/obf/');
define('OBF_API_URL', OBF_DEFAULT_ADDRESS . 'v1');
define('OBF_API_CONSUMER_ID', 'Moodle');

require_once(__DIR__ . '/class/criterion/criterion.php');
require_once(__DIR__ . '/class/criterion/course.php');

/**
 * Reviews the badge criteria and issues the badges (if necessary) when a course is completed.
 *
 * @global moodle_database $DB
 * @param stdClass $eventdata
 * @return boolean Returns true if everything went ok.
 */
function local_obf_course_completed(stdClass $eventdata) {
    global $DB;

    $user = $DB->get_record('user', array('id' => $eventdata->userid));
    $recipients = array($user->email);

    // No capability -> no badge.
    if (!has_capability('local/obf:earnbadge', context_course::instance($eventdata->course),
                    $eventdata->userid)) {
        return true;
    }

    // Get all criteria related to course completion
    $criteria = obf_criterion::get_criteria();

    foreach ($criteria as $criterionid => $criterion) {
        // User has already met this criterion
        if ($criterion->is_met_by_user($user)) {
            continue;
        }

        // Has the user completed all the required criteria (completion/grade/date)
        // in this criterion?
        $criterionmet = $criterion->review($eventdata->userid, $eventdata->course);

        // Criterion was met, issue the badge
        if ($criterionmet) {
            $badge = $criterion->get_badge();
            $email = is_null($badge->get_email()) ? new obf_email() : $badge->get_email();

            $badge->issue($recipients, time(), $email->get_subject(), $email->get_body(),
                    $email->get_footer());
            $criterion->set_met_by_user($user->id);
        }
    }

    return true;
}

/**
 * When the course is deleted, this function deletes also the related badge issuance criteria.
 *
 * @param stdClass $course
 * @return boolean
 */
function local_obf_course_deleted(stdClass $course) {
    obf_criterion_course::delete_by_course($course);
    return true;
}

/**
 * Adds the OBF-links to Moodle's navigation.
 *
 * @global type $COURSE The current course
 * @param settings_navigation $navigation
 */
function obf_extends_navigation(global_navigation $navigation) {
    global $COURSE, $PAGE;

//    var_dump($PAGE->settingsnav->get_children_key_list());

    if (@$PAGE->settingsnav) {
        if (($branch = $PAGE->settingsnav->get('courseadmin'))) {
            $obfnode = navigation_node::create(get_string('obf', 'local_obf'),
                            new moodle_url('/local/obf/badge.php',
                            array('action' => 'list', 'courseid' => $COURSE->id)));
            $branch->add_node($obfnode, 'backup');
        }

        if (($branch = $PAGE->settingsnav->get('usercurrentsettings'))) {
            $node = navigation_node::create(get_string('obf', 'local_obf'),
                            new moodle_url('/local/obf/userconfig.php'));
            $branch->add_node($node);
        }
    }
}

/**
 * Checks the certificate expiration of the OBF-client and sends a message to admin if the
 * certificate is expiring. This function is called periodically when Moodle's cron job is run.
 * The interval is defined in version.php.
 *
 * @global type $CFG
 * @return boolean
 */
function local_obf_cron() {
    global $CFG;

    require_once($CFG->libdir . '/messagelib.php');
    require_once($CFG->libdir . '/datalib.php');

    $certexpiresin = obf_client::get_instance()->get_certificate_expiration_date();
    $diff = $certexpiresin - time();
    $days = floor($diff / (60 * 60 * 24));

    // Notify only if there's certain amount of days left before the certification expires.
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
