<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/class/criterion/criterionbase.php');

function obf_course_completed(stdClass $eventdata) {
    global $DB;

    $user = $DB->get_record('user', array('id' => $eventdata->userid));
    $recipients = array($user->email);

    // Get all criteria related to course completion
    $typeid = obf_criterion_base::CRITERIA_TYPE_COURSESET;
    $criteria = obf_criterion_base::get_criteria(array('c.criterion_type_id' => $typeid));

    foreach ($criteria as $criterionid => $criterion) {
        // User has already met this criterion
        if ($criterion->is_met_by_user($user))
            continue;

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

function local_obf_extends_settings_navigation(settings_navigation $navigation) {
    global $COURSE;

    if (($branch = $navigation->get('courseadmin'))) {
        $obfnode = navigation_node::create(get_string('obf', 'local_obf'));
        $obfnode->add(get_string('badgelist', 'local_obf'),
                new moodle_url('/local/obf/badge.php',
                array('action' => 'list', 'courseid' => $COURSE->id)));
        $branch->add_node($obfnode, 'backup');
    }
}