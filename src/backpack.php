<?php
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/backpack.php');

$userid = required_param('userid', PARAM_INT);
$backpack = obf_backpack::get_instance_by_userid($userid);

if ($backpack === false || count($backpack->get_group_ids()) == 0) {
    die(json_encode(array('error' => 'nogroups')));
}

echo json_encode($backpack->get_assertions_as_array());