<?php

define('OBF_API_CONSUMER_ID', 'Moodle');

class obf_client {

    public static function get_client_id() {
        global $CFG;
        return isset($CFG->obf_client_id) ? $CFG->obf_client_id : -1;
    }

    public static function get_api_url() {
        global $CFG;        
        return isset($CFG->obf_url) ? $CFG->obf_url : '';
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
    public function get_badge($badgeid) {
        return $this->curl('/badge/' . self::get_client_id() . '/' . $badgeid);
    }

    /**
     * 
     * @throws Exception If the request fails
     * @return type
     */
    public function get_issuer() {
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

    /**
     * 
     * @param type $badgeid
     * @return type
     */
    public function get_assertions($badgeid = null) {
        $params = array('api_consumer_id' => OBF_API_CONSUMER_ID);

        if (!is_null($badgeid)) {
            $params['badge_id'] = $badgeid;
        }

        // When getting assertions via OBF API the returned JSON isn't valid.
        // Let's use a closure that converts the returned string into valid JSON
        // before calling json_decode in $this->curl.
        return $this->curl('/event/' . self::get_client_id(), 'get', $params, function ($output) {
                            return '[' . implode(',', array_filter(explode("\n", $output))) . ']';
                        });
    }

    /**
     * 
     * @param obf_badge $badge
     * @param type $recipients
     * @param type $issuedon
     * @param type $emailsubject
     * @param type $emailbody
     * @param type $emailfooter
     */
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

    /**
     * 
     * @global type $CFG
     * @param type $path
     * @param type $method
     * @param array $params
     * @param Closure $preformatter
     * @return type
     * @throws Exception
     */
    public function curl($path, $method = 'get', array $params = array(), Closure $preformatter = null) {
        global $CFG;

        include_once $CFG->libdir . '/filelib.php';

        $apiurl = self::get_api_url();

        if (!isset($apiurl))
            throw new Exception(get_string('missingapiurl', 'local_obf'));

        $curl = $this->get_curl();
        $options = $this->get_curl_options();
        $url = $apiurl . $path;
        $output = $method == 'get' ? $curl->get($url, $params, $options) : $curl->post($url, json_encode($params), $options);
        $code = $curl->info['http_code'];

        if (!is_null($preformatter)) {
            $output = $preformatter($output);
        }

        $response = json_decode($output, true);

        // Codes 2xx should be ok
        if ($code < 200 || $code >= 300) {
            throw new Exception(get_string('apierror' . $code, 'local_obf', array('error' => $response['error'])));
        }

        return $response;
    }

    protected function get_curl() {
        return new curl();
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
