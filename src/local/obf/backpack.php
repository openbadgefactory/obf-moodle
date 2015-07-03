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
 * Script for fetching user's badges via Ajax.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/backpack.php');

require_login(); // TODO: Handle login requirement more gracefully for more useful error messages?

$userid = required_param('userid', PARAM_INT);
$provider = optional_param('provider', 0, PARAM_INT);
$context = context_user::instance($userid);

if ((int)$USER->id === $userid) {
    require_capability('local/obf:viewownbackpack', $context);
} else {
    // TODO: more specific capabilities?
    require_capability('local/obf:viewbackpack', $context);
}

$backpack = obf_backpack::get_instance_by_userid($userid, $DB, $provider);

if ($backpack === false || count($backpack->get_group_ids()) == 0) {
    die(json_encode(array('error' => 'nogroups')));
}

echo json_encode($backpack->get_assertions_as_array());
