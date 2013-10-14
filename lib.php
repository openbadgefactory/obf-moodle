<?php
//defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/class/criterion/criterionbase.php');

function obf_course_completed(stdClass $eventdata) {
    global $DB;

    $user = $DB->get_record('user', array('id' => $eventdata->userid));
    $typeid = obf_criterion_base::CRITERIA_TYPE_COURSESET;
    $criteria = obf_criterion_base::get_criteria(array('c.criterion_type_id' => $typeid));
    $recipients = array($user->email);
    
    foreach ($criteria as $criterionid => $criterion) {
        
        $criteriamet = $criterion->review($eventdata);
        
        if ($criteriamet) {
            $badge = $criterion->get_badge();
            $email = is_null($badge->get_email()) ? new obf_email() : $badge->get_email();
            
            $badge->issue($recipients, time(), $email->get_subject(), $email->get_body(),
                    $email->get_footer());
        }
    }
    
    return true;
}
?>
