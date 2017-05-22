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
 * Script for verifying user's backpack email.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/backpack.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/class/user_email.php');


require_login();
$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);

$assertion = required_param('assertion', PARAM_TEXT);
$action = optional_param('action', 'persona', PARAM_TEXT);
$provider = optional_param('provider', obf_backpack::get_default_provider(), PARAM_INT);
$backpack = obf_backpack::get_instance_by_userid($USER->id, $DB, $provider);

if ($backpack === false) {
    $backpack = new obf_backpack();
    $backpack->set_user_id($USER->id);
    $backpack->set_provider($provider);
}

$return = array('error' => '');

function parse_json_assertion($assertion) {
    global $USER;
    try {
        $object = json_decode($assertion);
        $email = property_exists($object, 'email') ? $object->email : '';
        $userid = $USER->id;
        // TODO: OBF defined capabilities ?
        if (!empty($object->userid)) {
            $context = context_user::instance($object->userid);
            if (has_capability('moodle/user:editprofile', $context)) {
                $userid = $object->userid;
            }
        }
        $token = property_exists($object, 'token') ? $object->token : null;
        return array($userid, $email, $token);
    } catch (Exception $e) {
        throw $e;
    }
}
switch($action) {
    case 'persona':
        try {
            $email = $backpack->verify($assertion);
            $backpack->connect($email);
        } catch (Exception $e) {
            $return['error'] = $e->getMessage();
            //die(json_encode(array('error' => $e->getMessage())));
        }

        break;
    case 'check_status':
        try {
            list($userid, $email) = parse_json_assertion($assertion);
            $status = obf_user_email::is_user_email_verified($userid, $email);
            $return['status'] = $status;
        } catch (Exception $e) {
            $return['error'] = $e->getMessage();
        }

        break;
    case 'verify_token':
        // TODO: Capability check, is user allowed to verify emails
        try {
            list($userid, $email, $token) = parse_json_assertion($assertion);
            $status = obf_user_email::is_user_email_verified($userid, $email, $token);
            $return['status'] = $status;
        } catch (Exception $e) {
            $return['error'] = $e->getMessage();
        }
        break;
    case 'create_token':
        try {
            list($userid, $email) = parse_json_assertion($assertion);
            if (!obf_user_email::is_user_email_verified($userid, $email)) {
                $status = obf_user_email::create_user_email_token($userid, $email, true);
                $return['verified'] = false;
            } else {
                $status = true;
                $return['verified'] = true;
            }
            $return['status'] = (bool)$status;
        } catch (Exception $e) {
            $return['error'] = $e->getMessage();
        }
        break;
    case 'connect_email':
        try {
            list($userid, $email) = parse_json_assertion($assertion);
            if (obf_user_email::is_user_email_verified($userid, $email)) {
                $backpack->connect($email);
                $status = true;
            } else {
                $status = false;
            }
            $return['status'] = (bool)$status;
        } catch (Exception $e) {
            $return['message'] = $e->getMessage();
        }
        break;
    default:
}
echo json_encode($return);
