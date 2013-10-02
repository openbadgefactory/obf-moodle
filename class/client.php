<?php

define('OBF_API_CONSUMER_ID', 'Moodle');

class obf_client {
    
    public static function get_client_id() {
        global $CFG;
        return $CFG->obf_client_id;
    }
    
    /**
     * 
     * @return obf_client
     */
    public static function get_instance() {
        return new self();
    }
    
    /**
     * 
     * @param type $badgeid
     * @throws Exception If the request fails
     * @return type
     */
    public function get_badge_json($badgeid) {
        return $this->curl('/badge/' . self::get_client_id() . '/' . $badgeid);
    }

    /**
     * 
     * @throws Exception If the request fails
     * @return type
     */
    public function get_issuer_json() {
        return $this->curl('/client/' . self::get_client_id());
    }

    /**
     * 
     * @throws Exception If the request fails
     * @return type
     */
    public function get_badges() {
        return $this->curl('/tree/' . self::get_client_id() . '/badge');
    }

    public function issue_badge(obf_badge $badge, $recipients, $issuedon, $emailsubject, $emailbody, $emailfooter) {
        $params = array(
            'recipient' => $recipients,
            'expires' => $badge->get_expires(),
            'issued_on' => $issuedon,
            'email_subject' => $emailsubject,
            'email_body' => $emailbody,
            'email_footer' => $emailfooter,
            'api_consumer_id' => OBF_API_CONSUMER_ID,
            'log_entry' => array('foo' => 'Just testing')
        );

        $this->curl('/badge/' . self::get_client_id() . '/' . $badge->get_id(), 'post', $params);
    }

    public function curl($path, $method = 'get', array $params = array()) {
        global $CFG;

        include_once $CFG->libdir . '/filelib.php';

        $curl = new curl();
        $options = $this->get_curl_options();
        $url = $CFG->obf_url . $path;
        $output = $method == 'get' ? $curl->get($url, array(), $options) : $curl->post($url, json_encode($params), $options);
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
    public function get_curl_options() {
        return array(
            'RETURNTRANSFER' => true,
            'FOLLOWLOCATION' => false,
            'SSL_VERIFYHOST' => false, // for testing
            'SSL_VERIFYPEER' => false, // for testing
            'SSLCERT' => '/home/olli/Projects/OBF-Moodle/test.pem',
            'SSLKEY' => '/home/olli/Projects/OBF-Moodle/test.key'
        );
    }

}

?>
