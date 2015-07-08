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
 * Page for handling the CRUD of a badge criterion.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/criterion/course.php');
require_once(__DIR__ . "/class/badge.php");

$id = optional_param('id', null, PARAM_INT);
$action = optional_param('action', 'new', PARAM_ALPHANUM);
$badgeid = required_param('badgeid', PARAM_ALPHANUM);
$type = optional_param('type', 1, PARAM_INT);
$addcourse = optional_param('addcourse', '', PARAM_TEXT);
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

    // Save the criterion.
    case 'save':
        require_capability('local/obf:editcriterion', $context);
        require_once(__DIR__ . '/form/criterion.php');

        $url = new moodle_url('/local/obf/criterion.php',
                array('badgeid' => $badge->get_id(),
            'action' => 'save', 'type' => $type));
        $criterion = new obf_criterion();
        $criterion->set_badge($badge);
        $criterionform = new obf_criterion_form($url, array('criterion' => $criterion, 'addcourse' => $addcourse));

        // Form submission was cancelled.
        if ($criterionform->is_cancelled()) {
            redirect(new moodle_url('/local/obf/badge.php',
                    array('id' => $badge->get_id(), 'action' => 'show', 'show' => 'criteria')));
        } else if (!is_null($data = $criterionform->get_data())) { // Form was successfully submitted.
            $criterion->set_completion_method(obf_criterion::CRITERIA_COMPLETION_ALL);

            // Save the criterion object first.
            if ($criterion->save() === false) {
                $content .= $OUTPUT->error_text(get_string('creatingcriterionfailed', 'local_obf'));
                $content = $PAGE->get_renderer('local_obf')->render($criterionform);
            } else { // Then add the selected courses.
                if (property_exists($data, 'criteriatype')) {
                    $criteriatype = $data->criteriatype;
                } else {
                    $criteriatype = obf_criterion_item::CRITERIA_TYPE_UNKNOWN;
                }
                $courseids = $data->course;

                foreach ($courseids as $courseid) {
                    if ($courseid == -1) {
                        $courseobj = obf_criterion_item::build_type($data->criteriatype);
                        $courseobj->set_criterionid($criterion->get_id());
                        $courseobj->save();
                    } else {
                        // Check course id validity.
                        $course = $DB->get_record('course',
                                array('id' => $courseid, 'enablecompletion' => COMPLETION_ENABLED));

                        if ($course !== false) {
                            // If multiple courses selected, set criteriatype to course since
                            // multi-course activities are not supported.
                            if (count($courseids) == 1) {
                                $objtype = obf_criterion_item::CRITERIA_TYPE_UNKNOWN;
                            } else {
                                $objtype = obf_criterion_item::CRITERIA_TYPE_COURSE;
                            }

                            $courseobj = obf_criterion_item::build_type($objtype);
                            $courseobj->set_courseid($courseid);
                            $courseobj->set_criterionid($criterion->get_id());
                            $courseobj->save();
                        }
                    }
                }

                redirect(new moodle_url('/local/obf/criterion.php',
                        array('badgeid' => $badge->get_id(), 'action' => 'edit', 'id' => $criterion->get_id())));
            }
        } else {
            $content .= $PAGE->get_renderer('local_obf')->render($criterionform);
        }
        break;

    // Creating a new criterion.
    case 'new':
        require_capability('local/obf:editcriterion', $context);
        require_once(__DIR__ . '/form/criterion.php');

        $url = new moodle_url('/local/obf/criterion.php',
                array('badgeid' => $badge->get_id(),
            'action' => 'save', 'type' => $type));
        $criterion = new obf_criterion();
        $criterion->set_badge($badge);

        $criterionform = new obf_criterion_form($url, array('criterion' => $criterion, 'addcourse' => $addcourse));
        $content = $PAGE->get_renderer('local_obf')->render($criterionform);

        break;

    // Editing an existing criterion.
    case 'edit':
        require_capability('local/obf:editcriterion', $context);
        require_once(__DIR__ . '/form/criterion.php');

        $url = new moodle_url('/local/obf/criterion.php',
                array('badgeid' => $badge->get_id(),
            'action' => 'update', 'id' => $id));
        $criterion = obf_criterion::get_instance($id);

        if (!$criterion->is_met()) {
            $criterionform = new obf_criterion_form($url, array('criterion' => $criterion, 'addcourse' => $addcourse));
            $content = $PAGE->get_renderer('local_obf')->render($criterionform);
        }

        break;

    // Updating an existing criterion.
    case 'update':
        require_capability('local/obf:editcriterion', $context);
        require_once(__DIR__ . '/form/criterion.php');

        $criterion = obf_criterion::get_instance($id);

        if (!empty($addcourse)) {
            $url = new moodle_url('/local/obf/criterion.php',
                    array('badgeid' => $badge->get_id(), 'id' => $id, 'action' => 'update'));
        } else {
            $url = $FULLME;
        }

        $criterionform = new obf_criterion_form($url, array('criterion' => $criterion, 'addcourse' => $addcourse));

        // Form was cancelled or editing is prohibited (criterion has already been met).
        if ($criterionform->is_cancelled() || $criterion->is_met()) {
            redirect(new moodle_url('/local/obf/badge.php',
                    array('id' => $badge->get_id(),
                'action' => 'show', 'show' => 'criteria')));
        } else if (!is_null($data = $criterionform->get_data())) { // Form was successfully submitted, save data.
            // TODO: wrap into a transaction?
            if (!empty($addcourse)) {

                $courseids = array_filter($data->course, function($courseid) {
                    global $DB;
                    // Check course id validity.
                    return ($DB->get_record('course',
                            array('id' => $courseid, 'enablecompletion' => COMPLETION_ENABLED)) !== false);
                });
                $courses = $criterion->get_items();
                if (property_exists($data, 'criteriatype')) {
                    $criteriatype = $data->criteriatype;
                } else if (count($courses) == 1) {
                    $criteriatype = $courses[0]->get_criteriatype();
                } else {
                    $criteriatype = obf_criterion_item::CRITERIA_TYPE_COURSE;
                }

                $criterion->set_items_by_courseids($courseids, $criteriatype);

                $tourl = new moodle_url('/local/obf/criterion.php',
                        array('badgeid' => $badge->get_id(), 'action' => 'edit',
                    'id' => $id));
            } else {
                if (isset($data->completion_method)) {
                    $criterioncompletionmethod = $data->completion_method;
                } else {
                    $criterioncompletionmethod = obf_criterion::CRITERIA_COMPLETION_ALL;
                }

                if ($criterioncompletionmethod != $criterion->get_completion_method()) {
                    $criterion->set_completion_method($criterioncompletionmethod);
                    $criterion->update();
                }

                $courses = $criterion->get_items(true);
                $criterioncourseid = count($courses) > 0 ? $courses[0]->get_id() : -1;

                $pickingtype = property_exists($data, 'picktype') && $data->picktype == 'yes';
                if ($pickingtype) {
                    $criterioncourse = obf_criterion_item::get_instance($criterioncourseid);
                    $criterioncourse->set_criteriatype($data->criteriatype);
                    $criterioncourse->save();

                    $tourl = new moodle_url('/local/obf/criterion.php',
                            array('badgeid' => $badge->get_id(),
                            'action' => 'edit', 'id' => $criterion->get_id()
                            )
                         );
                } else if ($data->criteriatype == obf_criterion_item::CRITERIA_TYPE_ACTIVITY && !$pickingtype) {
                    $criterioncourse = obf_criterion_item::get_instance($criterioncourseid);
                    if (!$criterioncourse->exists()) {
                        $criterioncourse->set_criterionid($criterion->get_id());
                    }
                    $criterioncourse->save_params($data);
                } else if ($data->criteriatype == obf_criterion_item::CRITERIA_TYPE_COURSE && !$pickingtype) {
                    // ... And then add the criterion attributes.
                    foreach ($data->mingrade as $criterioncourseid => $grade) {
                        $criterioncourse = obf_criterion_item::get_instance($criterioncourseid);
                        $criterioncourse->set_grade((int) $grade);
                        $criterioncourse->set_completedby($data->{'completedby_' . $criterioncourseid});
                        $criterioncourse->set_criteriatype($data->criteriatype);
                        $criterioncourse->save();
                    }
                } else if (($data->criteriatype == obf_criterion_item::CRITERIA_TYPE_TOTARA_PROGRAM ||
                        $data->criteriatype == obf_criterion_item::CRITERIA_TYPE_TOTARA_CERTIF) && !$pickingtype) {
                    $criterioncourse = obf_criterion_item::get_instance($criterioncourseid);
                    if (!$criterioncourse->exists()) {
                        $criterioncourse->set_criterionid($criterion->get_id());
                    }
                    $criterioncourse->save_params($data);
                }


                if (empty($tourl)) {
                    $tourl = new moodle_url('/local/obf/badge.php',
                            array('id' => $badge->get_id(), 'action' => 'show',
                        'show' => 'criteria'));
                }

                // If the review-checkbox was selected, let's review the criterion and check whether
                // the badge can be issued right now.
                if (property_exists($data, 'reviewaftersave') && $data->reviewaftersave) {
                    $recipientcount = $criterion->review_previous_completions();
                    $tourl->param('msg', get_string('badgewasautomaticallyissued', 'local_obf', $recipientcount));
                }
            }

            redirect($tourl);
        } else {
            $content .= $PAGE->get_renderer('local_obf')->render($criterionform);
        }
        break;

    // Deleting a criterion.
    case 'delete':
        require_capability('local/obf:editcriterion', $context);
        require_once(__DIR__ . '/form/criteriondeletion.php');

        $criterion = obf_criterion::get_instance($id);
        $deletionform = new obf_criterion_deletion_form($FULLME, array('criterion' => $criterion));
        $url = new moodle_url('/local/obf/badge.php',
                array('action' => 'show', 'show' => 'criteria',
            'id' => $badgeid));

        // Deletion cancelled.
        if ($deletionform->is_cancelled()) {
            redirect($url);
        } else if ($deletionform->is_submitted()) { // Deletion confirmed.
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
