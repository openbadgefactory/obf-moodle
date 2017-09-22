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
 * Mandatory public API of earnablebadge module
 *
 * @package    mod_earnablebadge
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @copyright  2017 Discendum Oy {@link http://www.discendum.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in earnablebadge module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function earnablebadge_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function earnablebadge_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function earnablebadge_reset_userdata($data) {
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function earnablebadge_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function earnablebadge_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add earnablebadge instance.
 * @param object $data
 * @param object $mform
 * @return int new earnablebadge instance id
 */
function earnablebadge_add_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/earnablebadge/locallib.php');

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->externalearnablebadge = earnablebadge_fix_submitted_earnablebadge($data->externalearnablebadge);

    $data->timemodified = time();
    $data->id = $DB->insert_record('earnablebadge', $data);

    return $data->id;
}

/**
 * Update earnablebadge instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function earnablebadge_update_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/earnablebadge/locallib.php');

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(RESOURCELIB_DISPLAY_AUTO, RESOURCELIB_DISPLAY_EMBED, RESOURCELIB_DISPLAY_FRAME))) {
        $displayoptions['printintro']   = (int)!empty($data->printintro);
    }
    $data->displayoptions = serialize($displayoptions);

    $data->externalearnablebadge = earnablebadge_fix_submitted_earnablebadge($data->externalearnablebadge);

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('earnablebadge', $data);

    return true;
}

/**
 * Delete earnablebadge instance.
 * @param int $id
 * @return bool true
 */
function earnablebadge_delete_instance($id) {
    global $DB;

    if (!$earnablebadge = $DB->get_record('earnablebadge', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('earnablebadge', array('id'=>$earnablebadge->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function earnablebadge_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/earnablebadge/locallib.php");

    if (!$earnablebadge = $DB->get_record('earnablebadge', array('id'=>$coursemodule->instance),
            'id, name, display, displayoptions, externalearnablebadge, parameters, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $earnablebadge->name;

    //note: there should be a way to differentiate links from normal resources
    $info->icon = earnablebadge_guess_icon($earnablebadge->externalearnablebadge, 24);

    $display = earnablebadge_get_final_display_type($earnablebadge);

    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $fullearnablebadge = "$CFG->wwwroot/mod/earnablebadge/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($earnablebadge->displayoptions) ? array() : unserialize($earnablebadge->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullearnablebadge', '', '$wh'); return false;";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $fullearnablebadge = "$CFG->wwwroot/mod/earnablebadge/view.php?id=$coursemodule->id&amp;redirect=1";
        $info->onclick = "window.open('$fullearnablebadge'); return false;";

    }

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('earnablebadge', $earnablebadge, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function earnablebadge_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-earnablebadge-*'=>get_string('page-mod-earnablebadge-x', 'earnablebadge'));
    return $module_pagetype;
}

/**
 * Export earnablebadge resource contents
 *
 * @return array of file content
 */
function earnablebadge_export_contents($cm, $baseearnablebadge) {
    global $CFG, $DB;
    require_once("$CFG->dirroot/mod/earnablebadge/locallib.php");
    $contents = array();
    $context = context_module::instance($cm->id);

    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $earnablebadgerecord = $DB->get_record('earnablebadge', array('id'=>$cm->instance), '*', MUST_EXIST);

    $fullearnablebadge = str_replace('&amp;', '&', earnablebadge_get_full_earnablebadge($earnablebadgerecord, $cm, $course));
    $isearnablebadge = clean_param($fullearnablebadge, PARAM_earnablebadge);
    if (empty($isearnablebadge)) {
        return null;
    }

    $earnablebadge = array();
    $earnablebadge['type'] = 'earnablebadge';
    $earnablebadge['filename']     = clean_param(format_string($earnablebadgerecord->name), PARAM_FILE);
    $earnablebadge['filepath']     = null;
    $earnablebadge['filesize']     = 0;
    $earnablebadge['fileearnablebadge']      = $fullearnablebadge;
    $earnablebadge['timecreated']  = null;
    $earnablebadge['timemodified'] = $earnablebadgerecord->timemodified;
    $earnablebadge['sortorder']    = null;
    $earnablebadge['userid']       = null;
    $earnablebadge['author']       = null;
    $earnablebadge['license']      = null;
    $contents[] = $earnablebadge;

    return $contents;
}
