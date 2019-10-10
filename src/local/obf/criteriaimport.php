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
foreach (obf_backpack::get_providers() as $provider) {
    $existing = obf_backpack::get_instance($USER, $provider);
    $backpacks[] = $existing ? $existing : new obf_backpack(null, $provider);
}

/**
 * @param $form
 * @param $backpack
 * @param $content
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_obf_insert_criteria_from_form($form, $backpack, &$content) {
    global $DB;
    $criteriadata = new stdClass();
    $criteriato = new stdClass();

    if (!$form->is_cancelled()) {
        if ($data = $form->get_data()) {
            $result = $DB->get_records('local_obf_criterion_courses', array('courseid' => $data->fromcourse));
            //var_dump($result);
            if ($result) {
                foreach ($result as $key => $criteria) {
                    //var_dump($criteria->obf_criterion_id);
                    //die();
                    $crit = $DB->get_record('local_obf_criterion', array('id' => $criteria->obf_criterion_id));

                    $criteriadata->badge_id = $crit->badge_id;
                    $criteriadata->completion_method = $crit->completion_method;
                    $criteriadata->use_addendum = $crit->use_addendum;
                    $criteriadata->addendum = $crit->addendum;

                    $count = $DB->count_records('local_obf_criterion') + 1;

                    $criteriato->obf_criterion_id = $count;
                    $criteriato->courseid = $data->tocourse;
                    $criteriato->obf_grade = $criteria->grade;
                    $criteriato->completed_by = $criteria->completed_by;
                    $criteriato->criteria_type = $criteria->criteria_type;

                    $DB->insert_record('local_obf_criterion', $criteriadata);
                    $DB->insert_record('local_obf_criterion_courses', $criteriato);
                }
                die();
            }
        } else {
            $form->set_data($backpack);
        }
        $content .= $form->render();
    } else {
        $redirecturl = new moodle_url('/local/obf/criteriaimport.php');
        redirect($redirecturl);
    }
}

switch ($action) {
    case 'list':
        $content .= $PAGE->get_renderer('local_obf')->print_heading('importbadgecriteria');
        $content .= $PAGE->get_renderer('local_obf')->render_backpack_provider_list($backpacks, $url);
        break;

    case 'create':
        $backpack = new stdClass();
        $formurl = new moodle_url('/local/obf/criteriaimport.php', array('action' => 'create'));
        $form = new obf_criteria_import($formurl, array('backpack' => $backpack));
        local_obf_insert_criteria_from_form($form, $backpack, $content);
        break;
}

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();