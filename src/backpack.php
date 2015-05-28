<?php
/**
 * Script for fetching user's badges via Ajax.
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/backpack.php');

require_login(); // TODO: Handle login requirement more gracefully for more useful error messages?

$userid = required_param('userid', PARAM_INT);
$context = context_user::instance($userid);

if ((int)$USER->id === $userid) {
    require_capability('local/obf:viewownbackpack', $context);
} else {
    //TODO: more specific capabilities?
    require_capability('local/obf:viewbackpack', $context);
}

$backpack = obf_backpack::get_instance_by_userid($userid, $DB);

if ($backpack === false || count($backpack->get_group_ids()) == 0) {
    die(json_encode(array('error' => 'nogroups')));
}

echo json_encode($backpack->get_assertions_as_array());
