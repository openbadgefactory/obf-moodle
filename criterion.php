<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . '/class/criterion/criterionbase.php');
require_once(__DIR__ . "/lib.php");

$id = optional_param('id', null, PARAM_INT);
$action = optional_param('action', 'new', PARAM_ALPHANUM);
$badgeid = required_param('badgeid', PARAM_ALPHANUM);
$type = optional_param('type', 1, PARAM_INT);
$context = context_system::instance();
$content = '';

require_login();

$badge = obf_badge::get_instance($badgeid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/obf/criterion.php',
        array('action' => $action,
    'id' => $id, 'badgeid' => $badgeid, 'type' => $type)));
$PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . $badge->get_name());
$PAGE->set_heading(get_string('addcriteria', 'local_obf'));
$PAGE->set_pagelayout('admin');

switch ($action) {

    case 'save':
        require_once(__DIR__ . '/form/criterion.php');

        $url = new moodle_url('/local/obf/criterion.php',
                array('badgeid' => $badge->get_id(),
            'action' => 'save', 'type' => $type));
        $criterionobj = obf_criterion_base::get_empty_instance($type, $badge);
        $criterionform = new obf_criterion_form($url, array('criterion' => $criterionobj));

        // Form submission was cancelled
        if ($criterionform->is_cancelled()) {
            redirect(new moodle_url('/local/obf/badge.php',
                    array('id' => $badge->get_id(), 'action' => 'show', 'show' => 'criteria')));
        }

        // Form was successfully submitted
        else if (!is_null($data = $criterionform->get_data())) {
            $criterionobj->set_completion_method(obf_criterion_base::CRITERIA_COMPLETION_ALL);

            if ($criterionobj->save() === false) {
                $content .= $OUTPUT->error_text(get_string('creatingcriterionfailed', 'local_obf'));
                $content = $PAGE->get_renderer('local_obf')->render($criterionform);
            } else {

                $courseids = $data->course;

                foreach ($courseids as $courseid) {
                    $course = $DB->get_record('course',
                            array('id' => $courseid, 'enablecompletion' => COMPLETION_ENABLED));

                    if ($course !== false) {
                        $criterionobj->save_attribute('course_' . $courseid, $courseid);
                    }
                }

                redirect(new moodle_url('/local/obf/criterion.php',
                        array('badgeid' => $badge->get_id(), 'action' => 'edit', 'id' => $criterionobj->get_id())));
            }
        } else {
            $content .= $PAGE->get_renderer('local_obf')->render($criterionform);
        }
        break;

    // Creating a new criterion
    case 'new':
        require_once(__DIR__ . '/form/criterion.php');

        $url = new moodle_url('/local/obf/criterion.php',
                array('badgeid' => $badge->get_id(),
            'action' => 'save', 'type' => $type));
        $criterionobj = obf_criterion_base::get_empty_instance($type, $badge);
        $criterionform = new obf_criterion_form($url, array('criterion' => $criterionobj));
        $content = $PAGE->get_renderer('local_obf')->render($criterionform);

        break;

    // Editing an existing criterion
    case 'edit':
        require_once(__DIR__ . '/form/criterion.php');

        $url = new moodle_url('/local/obf/criterion.php',
                array('badgeid' => $badge->get_id(),
            'action' => 'update', 'id' => $id));
        $criterionobj = obf_criterion_base::get_instance($id);
        $criterionform = new obf_criterion_form($url, array('criterion' => $criterionobj));
        $content = $PAGE->get_renderer('local_obf')->render($criterionform);
        break;

    // Updating an existing criterion
    case 'update':
        require_once(__DIR__ . '/form/criterion.php');
        
        $criterionobj = obf_criterion_base::get_instance($id);
        $criterionform = new obf_criterion_form($FULLME, array('criterion' => $criterionobj));

        // Form was cancelled
        if ($criterionform->is_cancelled()) {
            redirect(new moodle_url('/local/obf/badge.php', array('id' => $badge->get_id(),
            'action' => 'show', 'show' => 'criteria')));
        }
        
        // Form was successfully submitted, save data
        else if (!is_null($data = $criterionform->get_data())) {
            // TODO: wrap into a transaction
            if ($data->completion_method != $criterionobj->get_completion_method()) {
                $criterionobj->set_completion_method($data->completion_method);
                $criterionobj->update();
            }

            // ... delete old attributes ...
            $criterionobj->delete_attributes();
            
            // ... and then add the criterion attributes
            foreach ($data->mingrade as $courseid => $grade) {
                $grade = (int) $grade;
                $completedby = $data->{'completedby_' . $courseid};

                // first add the course...
                $criterionobj->save_attribute('course_' . $courseid, $courseid);

                // ... then the grade-attribute if selected...
                if ($grade > 0) {
                    $criterionobj->save_attribute('grade_' . $courseid, $grade);
                }

                // ... and finally completion date -attribute if selected
                if ($completedby > 0) {
                    $criterionobj->save_attribute('completedby_' . $courseid, $completedby);
                }
            }

            redirect(new moodle_url('/local/obf/badge.php',
                    array('id' => $badge->get_id(), 'action' => 'show',
                'show' => 'criteria')));
        } else {
            $content .= $PAGE->get_renderer('local_obf')->render($criterionform);
        }
        break;

    // Deleting a criterion
    case 'delete':
        require_once __DIR__ . '/form/criteriondeletion.php';

        $criterionobj = obf_criterion_base::get_instance($id);
        $deletionform = new obf_criterion_deletion_form($FULLME, array('criterion' => $criterionobj));
        $url = new moodle_url('/local/obf/badge.php',
                array('action' => 'show', 'show' => 'criteria',
            'id' => $badgeid));

        // deletion cancelled
        if ($deletionform->is_cancelled()) {
            redirect($url);
        }
        // deletion confirmed                
        else if ($deletionform->is_submitted()) {
            $criterionobj->delete();
            redirect($url, get_string('criteriondeleted', 'local_obf'));
        } else {
            $content = $PAGE->get_renderer('local_obf')->render($deletionform);
        }

        break;
    case 'list':
        break;
}

echo $content;
?>
