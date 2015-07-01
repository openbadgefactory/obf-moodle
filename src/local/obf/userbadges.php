<?php
/**
 * Script for fetching user's badges via Ajax.
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
$assertions->add_collection(obf_assertion::get_assertions($client, null, $DB->get_record('user', array('id' => $userid))->email ));
$assertions->apply_blacklist($blacklist);

if ((int)$USER->id === $userid) {
    require_capability('local/obf:viewownbackpack', $context);
} else {
    //TODO: more specific capabilities?
    require_capability('local/obf:viewbackpack', $context);
}

echo json_encode($assertions->toArray());
