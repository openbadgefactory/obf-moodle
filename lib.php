<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/class/badge.php');
require_once(__DIR__ . '/class/issuer.php');
require_once(__DIR__ . '/class/folder.php');
require_once(__DIR__ . '/class/tree.php');

function obf_client_id() {
    global $CFG;
    return $CFG->obf_client_id;
}

function obf_get_badge_json($badgeid) {
    return obf_curl('/badge/' . obf_client_id() . '/' . $badgeid);
}

function obf_get_issuer_json() {
    return obf_curl('/client/' . obf_client_id());
}

function obf_get_badges() {
    return obf_curl('/tree/' . obf_client_id() . '/badge');
}

function obf_curl($path, $method = 'get') {
    global $CFG;

    include_once $CFG->libdir . '/filelib.php';

    $curl = new curl();
    $options = obf_get_curl_options();
    $url = $CFG->obf_url . $path;
    $output = $method == 'get' ? $curl->get($url, array(), $options) : $curl->post($url, array(), $options);
    $code = $curl->info['http_code'];
    $json = json_decode($output, true);

    // Codes 2xx should be ok
    if ($code < 200 || $code >= 300) {
        throw new Exception(get_string('apierror' . $code, 'local_obf', array('error' => $json['error'])));
    }

    return $json;
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
        'SSLCERT' => '/home/olli/Projects/OBF-Moodle/test.pem',
        'SSLKEY' => '/home/olli/Projects/OBF-Moodle/test.key'
    );
}

//function local_obf_extends_settings_navigation(settings_navigation $nav) {
//    global $PAGE;
//    
//    if (($branch = $nav->get('courseadmin', navigation_node::TYPE_COURSE)) !== false) {
//        $url = new moodle_url('/local/obf/issue.php', array('id' => $PAGE->course->id));
//        $node = navigation_node::create(get_string('issuecoursebadge', 'local_obf'), $url);
//        $branch->add_node($node);
//        
//        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
//            $node->make_active();
//        }
//    }
//}

?>
