<?php

define('OBF_API_CONSUMER_ID', 'Moodle');

class obf_client {

    public static function get_client_id() {
        return get_config('local_obf', 'obfclientid');
    }

    public static function get_api_url() {
        return get_config('local_obf', 'obfurl');
    }

    /**
     *
     * @return obf_client
     */
    public static function get_instance() {
        return new self();
    }

    public function authenticate($signature) {
        $signature = trim($signature);
        $token = base64_decode($signature);
        $curl = $this->get_curl();
        $curlopts = $this->get_curl_options();
        $apiurl = get_config('local_obf', 'obfurl');

        // We don't need these now, we haven't authenticated yet.
        unset($curlopts['SSLCERT']);
        unset($curlopts['SSLKEY']);

        // API url isn't set
        if ($apiurl === false) {
            throw new Exception(get_string('missingapiurl', 'local_obf'));
        }

        $pubkey = $curl->get($apiurl . '/client/OBF.rsa.pub', array(), $curlopts);

        // CURL-request failed
        if ($pubkey === false) {
            throw new Exception(get_string('pubkeyrequestfailed', 'local_obf') . ': ' . $curl->error);
        }

        // Server gave us an error
        if ($curl->info['http_code'] !== 200) {
            throw new Exception(get_string('pubkeyrequestfailed', 'local_obf') . ': ' .
            get_string('apierror' . $curl->info['http_code'], 'local_obf'));
        }

        $decrypted = '';

        // Get the public key
        $key = openssl_pkey_get_public($pubkey);

        // Couldn't decrypt data with provided key
        if (openssl_public_decrypt($token, $decrypted, $key, OPENSSL_PKCS1_PADDING) === false) {
            throw new Exception(get_string('tokendecryptionfailed', 'local_obf'));
        }

        $json = json_decode($decrypted);

        // Yay, we have the client-id. Let's store it somewhere.
        set_config('obfclientid', $json->id, 'local_obf');

        // Create a new private key
        $config = array('private_key_bits' => 2048, 'private_key_type', OPENSSL_KEYTYPE_RSA);
        $privkey = openssl_pkey_new($config);

        // Export the new private key to a file for later use
        openssl_pkey_export_to_file($privkey, $this->get_pkey_filename());

        $csrout = '';
        $dn = array('commonName' => $json->id);

        // Create a new CSR with the private key we just created
        $csr = openssl_csr_new($dn, $privkey);

        // Export the CSR into string
        if (openssl_csr_export($csr, $csrout) === false) {
            throw new Exception(get_string('csrexportfailed', 'local_obf'));
        }

        $postdata = json_encode(array('signature' => $signature, 'request' => $csrout));
        $cert = $curl->post($apiurl . '/client/' . $json->id . '/sign_request', $postdata, $curlopts);

        // Fetching certificate failed
        if ($cert === false) {
            throw new Exception(get_string('certrequestfailed', 'local_obf') . ': ' . $curl->error);
        }

        $httpcode = $curl->info['http_code'];

        // Server gave us an error
        if ($httpcode !== 200) {
            $jsonresp = json_decode($cert);
            $extrainfo = is_null($jsonresp) ? get_string('apierror' . $httpcode, 'local_obf') : $jsonresp->error;

            throw new Exception(get_string('certrequestfailed', 'local_obf') . ': ' . $extrainfo);
        }

        // Everything's ok, store the certificate into a file for later use.
        file_put_contents($this->get_cert_filename(), $cert);
        set_config('connectionestablished', true, 'local_obf');

        return true;
    }

    /**
     * Returns the expiration date of the OBF certificate as a unix timestamp.
     *
     * @return mixed The expiration date or false if the certificate is missing.
     */
    public function get_certificate_expiration_date() {
        $certfile = $this->get_cert_filename();

        if (!file_exists($certfile)) {
            return false;
        }

        $cert = file_get_contents($certfile);
        $ssl = openssl_x509_parse($cert);

        return $ssl['validTo_time_t'];
    }

    private function get_pkey_filename() {
        return __DIR__ . '/../pki/obf.key';
    }

    private function get_cert_filename() {
        return __DIR__ . '/../pki/obf.pem';
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
    public function get_tree() {
        return $this->curl('/tree/' . self::get_client_id() . '/badge');
    }

    /**
     *
     * @return type
     */
    public function get_badges() {
        return $this->curl('/badge/' . self::get_client_id(), 'get', array(),
                        function ($output) {
                    return '[' . implode(',', array_filter(explode("\n", $output))) . ']';
                });
    }

    /**
     *
     * @param type $badgeid
     * @return type
     */
    public function get_assertions($badgeid = null, $email = null) {
        $params = array('api_consumer_id' => OBF_API_CONSUMER_ID);

        if (!is_null($badgeid)) {
            $params['badge_id'] = $badgeid;
        }

        if (!is_null($email)) {
            $params['email'] = $email;
        }

        // When getting assertions via OBF API the returned JSON isn't valid.
        // Let's use a closure that converts the returned string into valid JSON
        // before calling json_decode in $this->curl.
        return $this->curl('/event/' . self::get_client_id(), 'get', $params,
                        function ($output) {
                    return '[' . implode(',', array_filter(explode("\n", $output))) . ']';
                });
    }

    public function get_event($eventid) {
        return $this->curl('/event/' . self::get_client_id() . '/' . $eventid, 'get');
    }

    /**
     * Exports a badge to Open Badge Factory
     *
     * @param obf_badge $badge
     */
    public function export_badge(obf_badge $badge) {
        $params = array(
            'name' => $badge->get_name(),
            'description' => $badge->get_description(),
            'image' => $badge->get_image(),
            'css' => $badge->get_criteria_css(),
            'criteria_html' => $badge->get_criteria_html(),
            'email_subject' => $badge->get_email()->get_subject(),
            'email_body' => $badge->get_email()->get_body(),
            'email_footer' => $badge->get_email()->get_footer(),
            'expires' => '',
            'tags' => array(),
            'draft' => $badge->get_isdraft()
        );

        $this->curl('/badge/' . self::get_client_id(), 'post', $params);
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
    public function issue_badge(obf_badge $badge, $recipients, $issuedon, $emailsubject, $emailbody,
            $emailfooter) {
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
    public function curl($path, $method = 'get', array $params = array(),
            Closure $preformatter = null) {
        global $CFG;

        include_once $CFG->libdir . '/filelib.php';

        $apiurl = self::get_api_url();

        if (!isset($apiurl)) {
            throw new Exception(get_string('missingapiurl', 'local_obf'));
        }

        $curl = $this->get_curl();
        $options = $this->get_curl_options();
        $url = $apiurl . $path;
        $output = $method == 'get' ? $curl->get($url, $params, $options) : $curl->post($url,
                        json_encode($params), $options);
        $code = $curl->info['http_code'];

        if (!is_null($preformatter)) {
            $output = $preformatter($output);
        }

        $response = json_decode($output, true);

        // Codes 2xx should be ok
        if ($code < 200 || $code >= 300) {
            throw new Exception(get_string('apierror' . $code, 'local_obf',
                    array('error' => $response['error'])));
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
            'SSLCERT' => $this->get_cert_filename(),
            'SSLKEY' => $this->get_pkey_filename()
        );
    }

}

?>
