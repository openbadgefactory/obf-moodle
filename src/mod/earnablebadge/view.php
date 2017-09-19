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
 * earnablebadge module main user interface
 *
 * @package    mod_earnablebadge
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @copyright  2017 Discendum Oy {@link http://www.discendum.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once("$CFG->dirroot/mod/earnablebadge/locallib.php");
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/obf/class/client.php');


$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$u        = optional_param('u', 0, PARAM_INT);         // earnablebadge instance id
$redirect = optional_param('redirect', 0, PARAM_BOOL);

if ($u) {  // Two ways to specify the module
    $earnablebadge = $DB->get_record('earnablebadge', array('id'=>$u), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('earnablebadge', $earnablebadge->id, $earnablebadge->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('earnablebadge', $id, 0, false, MUST_EXIST);
    $earnablebadge = $DB->get_record('earnablebadge', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/earnablebadge:view', $context);

$params = array(
    'context' => $context,
    'objectid' => $earnablebadge->id
);
$event = \mod_earnablebadge\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('earnablebadge', $earnablebadge);
$event->trigger();

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/earnablebadge/view.php', array('id' => $cm->id));

// Make sure earnablebadge exists before generating output - some older sites may contain empty earnablebadges
// Do not use PARAM_earnablebadge here, it is too strict and does not support general URIs!
$extearnablebadge = trim($earnablebadge->externalearnablebadge);
if (empty($extearnablebadge) or $extearnablebadge === 'http://') {
    earnablebadge_print_header($earnablebadge, $cm, $course);
    earnablebadge_print_heading($earnablebadge, $cm, $course);
    earnablebadge_print_intro($earnablebadge, $cm, $course);
    notice(get_string('invalidstoredearnablebadge', 'earnablebadge'), new moodle_url('/course/view.php', array('id'=>$cm->course)));
    die;
}
unset($extearnablebadge);

$displaytype = earnablebadge_get_final_display_type($earnablebadge);
if ($displaytype == RESOURCELIB_DISPLAY_OPEN) {
    // For 'open' links, we always redirect to the content - except if the user
    // just chose 'save and display' from the form then that would be confusing
    if (strpos(get_local_referer(false), 'modedit.php') === false) {
        $redirect = true;
    }
}

if ($redirect) {
    // coming from course page or earnablebadge index page,
    // the redirection is needed for completion tracking and logging
    $fullearnablebadge = str_replace('&amp;', '&', earnablebadge_get_full_earnablebadge($earnablebadge, $cm, $course));

    if (!course_get_format($course)->has_view_page()) {
        // If course format does not have a view page, add redirection delay with a link to the edit page.
        // Otherwise teacher is redirected to the external earnablebadge without any possibility to edit activity or course settings.
        $editearnablebadge = null;
        if (has_capability('moodle/course:manageactivities', $context)) {
            $editearnablebadge = new moodle_url('/course/modedit.php', array('update' => $cm->id));
            $edittext = get_string('editthisactivity');
        } else if (has_capability('moodle/course:update', $context->get_course_context())) {
            $editearnablebadge = new moodle_url('/course/edit.php', array('id' => $course->id));
            $edittext = get_string('editcoursesettings');
        }
        if ($editearnablebadge) {
            redirect($fullearnablebadge, html_writer::link($editearnablebadge, $edittext)."<br/>".
                    get_string('pageshouldredirect'), 10);
        }
    }
    redirect($fullearnablebadge);
}
if (!empty($_POST)) {
  require_sesskey();
  $obfclient = obf_client::get_instance();
  try {
    $result = $obfclient->earnable_badge_apply($earnablebadge->externalearnablebadge, $_POST, $_FILES);
  } catch(Exception $ex) {
    earnablebadge_print_header($earnablebadge, $cm, $course);
    earnablebadge_print_heading($earnablebadge, $cm, $course);
    earnablebadge_print_intro($earnablebadge, $cm, $course);
    notice('Error validating input. TODO: Fix' . $ex->getMessage(), new moodle_url('/course/view.php', array('id'=>$cm->course)));
    die;
  }
  earnablebadge_print_header($earnablebadge, $cm, $course);
  earnablebadge_print_heading($earnablebadge, $cm, $course);
  earnablebadge_print_intro($earnablebadge, $cm, $course);
  notice('Application received!', new moodle_url('/course/view.php', array('id'=>$cm->course)));
  die;
}

switch ($displaytype) {
    case RESOURCELIB_DISPLAY_EMBED:
        earnablebadge_display_embed($earnablebadge, $cm, $course);
        break;
    case RESOURCELIB_DISPLAY_FRAME:
        earnablebadge_display_frame($earnablebadge, $cm, $course);
        break;
    default:
        earnablebadge_print_workaround($earnablebadge, $cm, $course);
        break;
}
