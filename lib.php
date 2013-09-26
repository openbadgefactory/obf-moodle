<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/class/badge.php');
require_once(__DIR__ . '/class/issuer.php');
require_once(__DIR__ . '/class/folder.php');

/**
 * 
 * @global type $CFG
 * @param type $badgeid
 * @return type
 */
function obf_get_badge_json($badgeid) {
    global $CFG;

    include_once $CFG->libdir . '/filelib.php';

    $curl = new curl();
    $options = obf_get_curl_options();
    $output = $curl->get($CFG->obf_url . '/badge/' . $CFG->obf_client_id . '/' . $badgeid, array(), $options);
    $json = json_decode($output);

    return $json;
}

function obf_get_issuer_json() {
    global $CFG;
    
    include_once $CFG->libdir . '/filelib.php';
    
    $curl = new curl();
    $options = obf_get_curl_options();
    $output = $curl->get($CFG->obf_url . '/client/' . $CFG->obf_client_id, array(), $options);
    $json = json_decode($output);
    
    return $json;
}

/**
 * 
 * @param type $badgeid
 * @return type
 */
function obf_get_badge($badgeid) {
    return obf_badge::get_instance_from_json(obf_get_badge_json($badgeid));
}

/**
 * 
 * @return type
 */
function obf_get_curl_options() {
    return array(
        'RETURNTRANSFER' => true,
        'FOLLOWLOCATION' => false,
        'SSL_VERIFYHOST' => false, // for testing
        'SSL_VERIFYPEER' => false, // for testing
        'SSLCERT' => '/tmp/test.pem',
        'SSLKEY' => '/tmp/test.key'
    );
}

function obf_get_badge_tree($reload = false) {
    global $CFG;

    $badges = false;
    $obfcache = cache::make('local_obf', 'obfcache');

    if (!$reload) {
        $badges = $obfcache->get($CFG->obf_client_id);
    }

    if ($badges === false) {
        $badges = new obf_badge_tree(obf_get_badges());
        $obfcache->set($CFG->obf_client_id, $badges);
    }

    return $badges;
}

/**
 * 
 * @return type
 */
function obf_get_badges() {
    global $CFG;

    include_once $CFG->libdir . '/filelib.php';

//    if (empty($CFG->obf_client_id))
//        throw new Exception(get_string('missingclientid', 'local_obf'));

    $curl = new curl();
    $options = obf_get_curl_options();
    $output = $curl->get($CFG->obf_url . '/tree/' . $CFG->obf_client_id . '/badge', array(), $options);
    $code = $curl->info['http_code'];
    $json = json_decode($output, true);

    if ($code !== 200) {
//        debugging('Curl request failed: ' . $curl->error);
        throw new Exception(get_string('apierror' . $code, 'local_obf', array("error" => $json['error'])));
    }

    return $json;
}
?>
