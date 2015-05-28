<?php
/**
 * Page for handling the CRUD of a badge criterion.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/class/criterion/course.php';
require_once __DIR__ . "/class/badge.php";

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
$PAGE->set_title(get_string('obf', 'local_obf'));
$PAGE->set_pagelayout('admin');

navigation_node::override_active_url(new moodle_url('/local/obf/badge.php',
        array('action' => 'list')));

$PAGE->navbar->add($badge->get_name(),
        new moodle_url('/local/obf/badge.php',
        array('action' => 'show',
    'show' => 'details', 'id' => $badgeid)));
$PAGE->navbar->add(get_string('badgecriteria', 'local_obf'),
        new moodle_url('/local/obf/badge.php',
        array('action' => 'show',
    'show' => 'criteria', 'id' => $badgeid)));
$PAGE->navbar->add(get_string('configurecriteria', 'local_obf'));

switch ($action) {

    // Save the criterion
    case 'save':
        require_capability('local/obf:editcriterion', $context);
        require_once(__DIR__ . '/form/criterion.php');

        $url = new moodle_url('/local/obf/criterion.php',
                array('badgeid' => $badge->get_id(),
            'action' => 'save', 'type' => $type));
        $criterion = new obf_criterion();
        $criterion->set_badge($badge);
        $criterionform = new obf_criterion_form($url, array('criterion' => $criterion));

        // Form submission was cancelled
        if ($criterionform->is_cancelled()) {
            redirect(new moodle_url('/local/obf/badge.php',
                    array('id' => $badge->get_id(), 'action' => 'show', 'show' => 'criteria')));
        }

        // Form was successfully submitted
        else if (!is_null($data = $criterionform->get_data())) {
            $criterion->set_completion_method(obf_criterion::CRITERIA_COMPLETION_ALL);

            // Save the criterion object first.
            if ($criterion->save() === false) {
                $content .= $OUTPUT->error_text(get_string('creatingcriterionfailed', 'local_obf'));
                $content = $PAGE->get_renderer('local_obf')->render($criterionform);
            }

            // Then add the selected courses.
            else {
                $courseids = $data->course;

                foreach ($courseids as $courseid) {
                    // Check course id validity
                    $course = $DB->get_record('course',
                            array('id' => $courseid, 'enablecompletion' => COMPLETION_ENABLED));

                    if ($course !== false) {
                        $courseobj = new obf_criterion_course();
                        $courseobj->set_courseid($courseid);
                        $courseobj->set_criterionid($criterion->get_id());
                        $courseobj->save();
                    }
                }

                redirect(new moodle_url('/local/obf/criterion.php',
                        array('badgeid' => $badge->get_id(), 'action' => 'edit', 'id' => $criterion->get_id())));
            }
        } else {
            $content .= $PAGE->get_renderer('local_obf')->render($criterionform);
        }
        break;

    // Creating a new criterion
    case 'new':
        require_capability('local/obf:editcriterion', $context);
        require_once(__DIR__ . '/form/criterion.php');

        $url = new moodle_url('/local/obf/criterion.php',
                array('badgeid' => $badge->get_id(),
            'action' => 'save', 'type' => $type));
        $criterion = new obf_criterion();
        $criterion->set_badge($badge);

        $criterionform = new obf_criterion_form($url, array('criterion' => $criterion));
        $content = $PAGE->get_renderer('local_obf')->render($criterionform);

        break;

    // Editing an existing criterion
    case 'edit':
        require_capability('local/obf:editcriterion', $context);
        require_once(__DIR__ . '/form/criterion.php');

        $url = new moodle_url('/local/obf/criterion.php',
                array('badgeid' => $badge->get_id(),
            'action' => 'update', 'id' => $id));
        $criterion = obf_criterion::get_instance($id);

        if (!$criterion->is_met()) {
            $criterionform = new obf_criterion_form($url, array('criterion' => $criterion));
            $content = $PAGE->get_renderer('local_obf')->render($criterionform);
        }

        break;

    // Updating an existing criterion
    case 'update':
        require_capability('local/obf:editcriterion', $context);
        require_once(__DIR__ . '/form/criterion.php');

        $criterion = obf_criterion::get_instance($id);
        $criterionform = new obf_criterion_form($FULLME, array('criterion' => $criterion));

        // Form was cancelled or editing is prohibited (criterion has already been met)
        if ($criterionform->is_cancelled() || $criterion->is_met()) {
            redirect(new moodle_url('/local/obf/badge.php',
                    array('id' => $badge->get_id(),
                'action' => 'show', 'show' => 'criteria')));
        }

        // Form was successfully submitted, save data
        else if (!is_null($data = $criterionform->get_data())) {
            // TODO: wrap into a transaction?

            $completion_method = isset($data->completion_method) ? $data->completion_method : obf_criterion::CRITERIA_COMPLETION_ALL;

            if ($completion_method != $criterion->get_completion_method()) {
                $criterion->set_completion_method($completion_method);
                $criterion->update();
            }

            // ... and then add the criterion attributes
            foreach ($data->mingrade as $criterioncourseid => $grade) {
                $criterioncourse = obf_criterion_course::get_instance($criterioncourseid);
                $criterioncourse->set_grade((int) $grade);
                $criterioncourse->set_completedby($data->{'completedby_' . $criterioncourseid});
                $criterioncourse->save();
            }

            $tourl = new moodle_url('/local/obf/badge.php',
                    array('id' => $badge->get_id(), 'action' => 'show',
                'show' => 'criteria'));

            // If the review-checkbox was selected, let's review the criterion and check whether
            // the badge can be issued right now.
            if ($data->reviewaftersave) {
                $recipientcount = $criterion->review_previous_completions();
                $tourl->param('msg', get_string('badgewasautomaticallyissued', 'local_obf', $recipientcount));
            }

            redirect($tourl);
        } else {
            $content .= $PAGE->get_renderer('local_obf')->render($criterionform);
        }
        break;

    // Deleting a criterion
    case 'delete':
        require_capability('local/obf:editcriterion', $context);
        require_once __DIR__ . '/form/criteriondeletion.php';

        $criterion = obf_criterion::get_instance($id);
        $deletionform = new obf_criterion_deletion_form($FULLME, array('criterion' => $criterion));
        $url = new moodle_url('/local/obf/badge.php',
                array('action' => 'show', 'show' => 'criteria',
            'id' => $badgeid));

        // deletion cancelled
        if ($deletionform->is_cancelled()) {
            redirect($url);
        }
        // deletion confirmed
        else if ($deletionform->is_submitted()) {
            $criterion->delete();
            redirect($url, get_string('criteriondeleted', 'local_obf'));
        } else {
            $content = $PAGE->get_renderer('local_obf')->render($deletionform);
        }

        break;
    case 'list':
        break;
}

echo $content;
