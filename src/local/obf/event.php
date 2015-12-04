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
 * Displays the details of a single event from OBF.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/assertion.php');
require_once(__DIR__ . '/class/assertion_collection.php');
require_once(__DIR__ . '/class/criterion/criterion.php');
require_once(__DIR__ . '/class/event.php');
require_once(__DIR__ . '/form/revoke.php');


require_login();

$eventid = required_param('id', PARAM_ALPHANUM);

$courseid = optional_param('course_id', '', PARAM_INT);
$action = optional_param('action', 'view', PARAM_ALPHANUM);
$show = optional_param('show', '', PARAM_ALPHANUM);
if ($action == 'revoke') {
    $emailar = required_param_array('email', PARAM_TEXT);
} else {
    $emailar = optional_param_array('email', array(), PARAM_TEXT);
}
$msg = optional_param('msg', '', PARAM_TEXT);

$eventdata = new obf_issue_event($eventid, $DB);
$syscontext = context_system::instance();
$hasviewpermission = false;
$hasrevokepermission = false;
if ($eventdata) {
    // Check user capabilities for different event and criteria types.
    if ($eventdata->has_userid() && $USER->id == $eventdata->get_userid()) {
        $hasviewpermission = true;
        $hasrevokepermission = true;
    } else if ($eventdata->has_userid()) {
        $context = context_user::instance($eventdata->get_userid());
        require_capability('local/obf:viewallevents', $context);
        if ($action == 'revoke') {
            require_capability('local/obf:revokeallevents', $context);
        }
        $hasrevokepermission = has_capability('local/obf:revokeallevents', $context);
        $hasviewpermission = true;
    } else if ($eventdata->has_criterionid()) {
        $criterion = obf_criterion::get_instance($eventdata->get_criterionid());
        $criterionitems = !empty($criterion) ? $criterion->get_items() : array();
        $lastindex = count($criterionitems) - 1;
        foreach ($criterionitems as $key => $item) {
            if ($item->has_courseid()) {
                $context = context_course::instance($item->get_courseid());
                if ($key == $lastindex) {
                    if (!$hasrevokepermission && $action == 'revoke') {
                        require_capability('local/obf:revokecourseevents', $context);
                        $hasrevokepermission = true;
                    } if (!$hasviewpermission) {
                        require_capability('local/obf:viewcourseevents', $context);
                        $hasviewpermission = true;
                    }
                    if (!$hasrevokepermission) {
                        $hasrevokepermission = has_capability('local/obf:revokecourseevents', $context);
                    }
                } else {
                    if (has_capability('local/obf:viewcourseevents', $context)) {
                        $hasviewpermission = true;
                    }
                    if (has_capability('local/obf:revokecourseevents', $context)) {
                        $hasrevokepermission = true;
                    }
                }
            }
        }
    }
}
if (!$hasviewpermission) {
    require_capability('local/obf:viewallevents', $syscontext);
}
if (!$hasrevokepermission) {
    $hasrevokepermission = has_capability('local/obf:revokeallevents', $syscontext);
}
if (!$hasrevokepermission && $action == 'revoke') {
    require_capability('local/obf:revokeallevents', $syscontext);
}
$client = obf_client::get_instance();

$assertion = obf_assertion::get_instance_by_id($eventid, $client);
$assertion->get_revoked($client);
$badge = $assertion->get_badge();

$PAGE->set_url(new moodle_url('/local/obf/event.php', array('id' => $eventid)));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('obf', 'local_obf'));

navigation_node::override_active_url(new moodle_url('/local/obf/badge.php',
        array('action' => 'history')));

$content = $OUTPUT->header();

// Filter out nulls.
$emailar = array_filter($emailar);

switch ($action) {
    case 'view':
        $PAGE->navbar->add(get_string('issuancedetails', 'local_obf'));
        if (!empty($msg)) {
            $content .= $OUTPUT->notification($msg);
        }
        $formurl = new moodle_url('/local/obf/event.php',
                array('id' => $eventid,
                    'action' => 'revoke',
                    'course_id' => $courseid));
        $collection = new obf_assertion_collection(array($assertion));
        $users = $collection->get_assertion_users($assertion);
        $showformurl = new moodle_url('/local/obf/event.php',
                array('id' => $eventid, 'action' => 'view', 'course_id' => $courseid,
                'show' => 'revoke'));
        $revokeform = new obf_revoke_form($formurl,
                array('assertion' => $assertion,
                      'users' => $users,
                      'showurl' => $showformurl,
                      'showrevoke' => $show == 'revoke'));

        $showrevokeform = $hasrevokepermission;
        if ($showrevokeform) {
            $content .= $PAGE->get_renderer('local_obf')->render_assertion($assertion, true, $revokeform);
            $params = array(array('class' => 'revokebutton',
                    'question' => get_string('confirmrevokation', 'local_obf')));
            $PAGE->requires->yui_module('moodle-local_obf-submitconfirm',
                    'M.local_obf.init_submitconfirm', $params);
        } else {
            $content .= $PAGE->get_renderer('local_obf')->render_assertion($assertion);
        }

        break;
    case 'revoke':
        if ($assertion) {
            $redirecturl = new moodle_url('/local/obf/event.php',
                    array('id' => $eventid,
                        'action' => 'view'));
            if (count($emailar) > 0) {
                try {
                    $assertion->revoke($client, $emailar);
                    $redirecturl->param('msg', get_string('eventrevoked', 'local_obf', implode(', ', $emailar)));
                } catch (Exception $e) {
                    $redirecturl->param('msg', $e->getMessage());
                }
                $tousers = $assertion->get_users($emailar);
                $assertion->send_revoke_message($tousers, $USER);
            }
            redirect($redirecturl);
        }
        break;
}


$content .= $OUTPUT->footer();

echo $content;
