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
 * Plugin configuration page.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/class/backpack.php');
require_once(__DIR__ . '/form/criteriaimport.php');
require_once(__DIR__ . '/class/criterion/course.php');
require_once(__DIR__ . '/class/criterion/criterion.php');
require_once(__DIR__ . '/class/criterion/activity.php');


$context = context_system::instance();
$msg = optional_param('msg', '', PARAM_TEXT);
$action = optional_param('action', 'list', PARAM_TEXT);
$providerid = optional_param('id', 0, PARAM_NUMBER);
$urlparams = $action == 'list' ? array() : array('action' => $action);
$url = new moodle_url('/local/obf/criteriaimport.php', $urlparams);

require_login();
require_capability('local/obf:configure', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$content = '';
$backpacks = array();
if (!empty($msg)) {
    $content .= $OUTPUT->notification($msg);
}

/**
 * @param $form
 * @param $backpack
 * @param $content
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_obf_insert_criteria_from_form($form, &$content) {
    global $DB, $OUTPUT;
    if (!$form->is_cancelled()) {
        if ($data = $form->get_data()) {
            try {
                $courses = $DB->get_records('local_obf_criterion_courses', array('courseid' => $data->fromcourse));
                if ($courses) {
                    $badge = new obf_criterion();
                    foreach ($courses as $crit) {
                        $course = obf_criterion_course::get_instance($crit->id);
                        $criteria = obf_criterion::get_instance($course->get_criterionid());
                        $act = $DB->get_records('local_obf_criterion_params', array('obf_criterion_id' => $course->get_criterionid()));
                        $badge = $criteria;
                        $badge->save();

                        if(!empty($act)) {
                            $activity = new stdClass();
                            foreach ($act as $a) {
                                $activity->obf_criterion_id = $badge->get_id();
                                $activity->name = $a->name;
                                $activity->value = $a->value;
                                $DB->insert_record('local_obf_criterion_params', $activity);
                            }
                        }
                        $course->set_id(0);
                        $course->set_courseid($data->tocourse);
                        $course->set_criterionid($badge->get_id());
                        $course->save();
                    }
                }
                $content .= $OUTPUT->notification(get_string('addedcriteria', 'local_obf'), \core\output\notification::NOTIFY_SUCCESS);

            } catch (Exception $e) {
                $content .= $OUTPUT->notification($e->getMessage());
            }
        } else {
            $form->set_data($data);
        }
        $content .= $form->render();
    } else {
        $redirecturl = new moodle_url('/admin/search.php');
        redirect($redirecturl);
    }
}

switch ($action) {
    case 'list':
        $formurl = new moodle_url('/local/obf/criteriaimport.php', array('action' => 'create'));
        $form = new obf_criteria_import($formurl);
        local_obf_insert_criteria_from_form($form,$content);
        break;

    case 'create':
        //$backpack = new stdClass();
        $formurl = new moodle_url('/local/obf/criteriaimport.php', array('action' => 'create'));
        $form = new obf_criteria_import($formurl);
        local_obf_insert_criteria_from_form($form, $content);
        break;
}

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();