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
 * @copyright  2013-2020, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/assertion.php');
require_once(__DIR__ . '/class/blacklist.php');
require_once(__DIR__ . '/class/assertion_collection.php');

require_login(); // TODO: Handle login requirement more gracefully for more useful error messages?

$userid = required_param('userid', PARAM_INT);
$context = context_user::instance($userid);

$client = obf_client::get_instance();
$blacklist = new obf_blacklist($userid);
$assertions = new obf_assertion_collection();
$assertions->add_collection(obf_assertion::get_assertions_all($client, $DB->get_record('user', array('id' => $userid))->email));
$assertions->apply_blacklist($blacklist);

if ((int)$USER->id === $userid) {
    require_capability('local/obf:viewownbackpack', $context);
} else {
    // TODO: more specific capabilities?
    require_capability('local/obf:viewbackpack', $context);
}

echo json_encode($assertions->toArray());
