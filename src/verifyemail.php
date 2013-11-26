<?php
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/backpack.php');

$assertion = required_param('assertion', PARAM_TEXT);
$backpack = obf_backpack::get_instance_by_userid($USER->id);

if ($backpack === false) {
    $backpack = new obf_backpack();
    $backpack->set_user_id($USER->id);
}

$email = $backpack->verify($assertion);

if ($email === false) {
    die(json_encode(array('error' => get_string('verification_failed', 'local_obf'))));
}

try {
    $backpack->connect($email);
}
catch (Exception $e) {
    die(json_encode(array('error' => $e->getMessage())));
}

echo json_encode(array('error' => ''));

//$backpack = obf_backpack::get_instance_by_userid($userid);
//
//if (count($backpack->get_group_ids()) == 0) {
//    die(json_encode(array('error' => 'nogroups')));
//}
//
//echo json_encode($backpack->get_assertions_as_array());