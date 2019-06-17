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
 * OBF Client.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../lib.php');

/**
 * Class for handling the communication to Open Badge Factory API.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_client {
    /**
     * @var $client Static client
     */
    private static $client = null;
    /**
     * @var curl|null Transport. Curl.
     */
    private $transport = null;

    /**
     * @var int HTTP code for handling errors, such as deleted badges.
     */
    private $httpcode = null;
    /**
     * @var string Last error message.
     */
    private $error = '';
    /**
     * @var array Raw response.
     */
    private $rawresponse = null;
    /**
     * @var bool Store raw response?
     */
    private $enablerawresponse = false;
    
    const RETRIEVE_ALL = 'all';
    const RETRIEVE_LOCAL = 'local';

    /**
     * Returns the id of the client stored in Moodle's config.
     *
     * @return string The client id.
     */
    public static function get_client_id() {
        return get_config('local_obf', 'obfclientid');
    }
    
    public function get_client_info() {
        $this->require_client_id();
        return $this->api_request('/client/' . self::get_client_id());
    }
    
    public static function has_client_id() {
        $clientid = self::get_client_id();
        return !empty($clientid);
    }

    /**
     * Returns the url of the OBF API.
     *
     * @return string The url.
     */
    public static function get_api_url() {
        $url = get_config('local_obf', 'apiurl');
        return self::api_url_maker($url);
    }
    
    /**
     * Returns the url of the OBF site.
     *
     * @return string The url.
     */
    public static function get_site_url()
    {
        $siteurl = get_config('local_obf', 'obf_site_url');
        return !empty($siteurl) ? $siteurl : substr(self::get_api_url(), 0, strrpos(self::get_api_url(), '/'));
    }

    /**
     * Returns default url.
     *
     * @return string The url.
     */
    public static function default_url() {
        return OBF_DEFAULT_ADDRESS;
    }

    /**
     * Returns the client instance.
     *
     * @param curl|null $transport
     * @return obf_client The client.
     */
    public static function get_instance($transport = null) {
        if (is_null(self::$client)) {
            self::$client = new self();

            if (!is_null($transport)) {
                self::$client->set_transport($transport);
            }
        }

        return self::$client;
    }

    /**
     * Set object transport.
     *
     * @param curl $transport
     */
    public function set_transport($transport) {
        $this->transport = $transport;
    }

    /**
     * Checks that the OBF client id is stored to plugin settings.
     *
     * @throws Exception If the client id is missing.
     */
    public function require_client_id() {
        $clientid = self::get_client_id();

        if (empty($clientid)) {
            throw new Exception(get_string('apierror0', 'local_obf'), 0);
        }
    }

    /**
     * Tests the connection to OBF API.
     *
     * @return int Returns the error code on failure and -1 on success.
     */
    public function test_connection() {
        try {
            $this->require_client_id();

            // TODO: does ping check certificate validity?
            $this->api_request('/ping/' . self::get_client_id());
            return -1;
        } catch (Exception $exc) {
            return $exc->getCode();
        }
    }

    /**
     * Deauthenticates the plugin.
     */
    public function deauthenticate() {
        @unlink($this->get_cert_filename());
        @unlink($this->get_pkey_filename());

        unset_config('obfclientid', 'local_obf');
        unset_config('apiurl', 'local_obf');    
    }

    /**
     * creates apiurl
     *
     * @return url
     */
    private function url_checker($url) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }
        if (!preg_match("/\/$/", $url)) {
            $url = $url . "/";
        }

        return $url;
    }

    /**
    * set v1 to end of url.
    * example: https://openbadgefactory.com/v1
    *
    * @param  $url
    * @return url/v1
    */
    private static function api_url_maker($url) {
        $version = "v1";
        return $url . $version;
    }
    
    public function get_branding_image_url($imagename = 'issued_by') {
        return $this->get_api_url() . '/badge/_/' . $imagename . '.png';
    }
    
    public function get_branding_image($imagename = 'issued_by') {
        $curl = $this->get_transport();
        $curlopts = $this->get_curl_options();
        $curlopts['FOLLOWLOCATION'] = true;
        $image = $curl->get( $this->get_branding_image_url($imagename), array(), $curlopts);
        if ($curl->info['http_code'] !== 200) {
            return null;
        }
        return $image;
    }
    /**
     * Tries to authenticate the plugin against OBF API.
     *
     * @param string $signature The request token from OBF.
     * @return boolean Returns true on success.
     * @throws Exception If something goes wrong.
     */
    public function authenticate($signature, $url) {
        $pkidir = realpath($this->get_pki_dir());

        // Certificate directory not writable.
        if (!is_writable($pkidir)) {
            throw new Exception(get_string('pkidirnotwritable', 'local_obf',
                    $pkidir));
        }

        $signature = trim($signature);
        $token = base64_decode($signature);
        $curl = $this->get_transport();
        $curlopts = $this->get_curl_options();
        $url = $this->url_checker($url);
        
        
        $apiurl = $this->api_url_maker($url);


        // We don't need these now, we haven't authenticated yet.
        unset($curlopts['SSLCERT']);
        unset($curlopts['SSLKEY']);

        $pubkey = $curl->get($apiurl . '/client/OBF.rsa.pub', array(), $curlopts);

        // CURL-request failed.
        if ($pubkey === false) {
            throw new Exception(get_string('pubkeyrequestfailed', 'local_obf') .
            ': ' . $curl->error);
        }

        // Server gave us an error.
        if ($curl->info['http_code'] !== 200) {
            throw new Exception(get_string('pubkeyrequestfailed', 'local_obf') . ': ' .
            get_string('apierror' . $curl->info['http_code'], 'local_obf'));
        }

        $decrypted = '';

        // Get the public key...
        $key = openssl_pkey_get_public($pubkey);

        // ... That didn't go too well.
        if ($key === false) {
            throw new Exception(get_string('pubkeyextractionfailed', 'local_obf') .
            ': ' . openssl_error_string());
        }

        // Couldn't decrypt data with provided key.
        if (openssl_public_decrypt($token, $decrypted, $key,
                        OPENSSL_PKCS1_PADDING) === false) {
            throw new Exception(get_string('tokendecryptionfailed', 'local_obf') .
            ': ' . openssl_error_string());
        }

        $json = json_decode($decrypted);

        // Yay, we have the client-id. Let's store it somewhere.
        set_config('obfclientid', $json->id, 'local_obf');
        set_config('apiurl', $url, 'local_obf');

        // Create a new private key.
        $config = array('private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA);
        $privkey = openssl_pkey_new($config);

        // Export the new private key to a file for later use.
        openssl_pkey_export_to_file($privkey, $this->get_pkey_filename());

        $csrout = '';
        $dn = array('commonName' => $json->id);

        // Create a new CSR with the private key we just created.
        $csr = openssl_csr_new($dn, $privkey);

        // Export the CSR into string.
        if (openssl_csr_export($csr, $csrout) === false) {
            throw new Exception(get_string('csrexportfailed', 'local_obf'));
        }
        
        if (empty($csrout)) {
            $opensslerrors = 'CSR output empty.';
            while (($opensslerror = openssl_error_string()) !== false) {
                $opensslerrors .= $opensslerror . " \n ";
            }
            throw new Exception($opensslerrors);
        }

        $postdata = json_encode(array('signature' => $signature, 'request' => $csrout));
        $cert = $curl->post($apiurl . '/client/' . $json->id . '/sign_request',
                $postdata, $curlopts);

        // Fetching certificate failed.
        if ($cert === false) {
            throw new Exception(get_string('certrequestfailed', 'local_obf') . ': ' . $curl->error);
        }

        $httpcode = $curl->info['http_code'];

        // Server gave us an error.
        if ($httpcode !== 200) {
            $jsonresp = json_decode($cert);
            $extrainfo = is_null($jsonresp) ? get_string('apierror' . $httpcode,
                            'local_obf') : $jsonresp->error;

            throw new Exception(get_string('certrequestfailed', 'local_obf') . ': ' . $extrainfo);
        }

        // Everything's ok, store the certificate into a file for later use.
        file_put_contents($this->get_cert_filename(), $cert);

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

    /**
     * Get absolute filename of certificate key-file.
     * @return string
     */
    public function get_pkey_filename() {
        return $this->get_pki_dir() . 'obf.key';
    }
    /**
     * Get absolute filename of certificate pem-file.
     * @return string
     */
    public function get_cert_filename() {
        return $this->get_pki_dir() . 'obf.pem';
    }
    /**
     * Get absolute path of certificate directory.
     * @return string
     */
    public function get_pki_dir() {
        global $CFG;
        return $CFG->dataroot . '/local_obf/pki/';
    }

    /**
     * Get a single badge from the API.
     *
     * @param string $badgeid
     * @throws Exception If the request fails
     * @return array The badge data.
     */
    public function get_badge($badgeid) {
        $this->require_client_id();
        return $this->api_request('/badge/' . self::get_client_id() . '/' . $badgeid);
    }

    /**
     * Get issuer data from the API.
     *
     * @throws Exception If the request fails
     * @return array The issuer data.
     */
    public function get_issuer() {
        $this->require_client_id();
        return $this->api_request('/client/' . self::get_client_id());
    }

    /**
     * Get badge categories from the API.
     *
     * @return array The category data.
     */
    public function get_categories() {
        $this->require_client_id();
        return $this->api_request('/badge/' . self::get_client_id() . '/_/categorylist');
    }

    /**
     * Get all the badges from the API.
     *
     * @param string[] $categories Filter badges by these categories.
     * @return array The badges data.
     */
    public function get_badges(array $categories = array(), $query = '') {
        $params = array('draft' => 0);

        $this->require_client_id();

        if (count($categories) > 0) {
            $params['category'] = implode('|', $categories);
        }
        if (!empty($query)) {
            $params['query'] = $query;
        }

        return $this->api_request('/badge/' . self::get_client_id(), 'get',
                        $params,
                        function ($output) {
                    return '[' . implode(',',
                                    array_filter(explode("\n", $output))) . ']';
                        });
    }
    public function is_only_local_events_enabled() {
        return get_config('local_obf', 'apidataretrieve') == self::RETRIEVE_LOCAL;
    }

    /**
     * Get badge assertions from the API.
     *
     * @param string $badgeid The id of the badge.
     * @param string $email The email address of the recipient.
     * @param array $params Optional extra params for the query.
     * @return array The event data.
     */
    public function get_assertions($badgeid = null, $email = null, $params = array()) {
        if ($this->is_only_local_events_enabled()) {
            $params = array_merge($params, array('api_consumer_id' => OBF_API_CONSUMER_ID));
        }

        $this->require_client_id();

        if (!is_null($badgeid)) {
            $params['badge_id'] = $badgeid;
        }

        if (!is_null($email) && $email != "") {
            $params['email'] = $email;
        } elseif(is_null($badgeid) && !is_null($email)) {
            return "";
        }

        // When getting assertions via OBF API the returned JSON isn't valid.
        // Let's use a closure that converts the returned string into valid JSON
        // before calling json_decode in $this->curl.
        return $this->api_request('/event/' . self::get_client_id(), 'get',
                        $params,
                        function ($output) {
                    return '[' . implode(',',
                                    array_filter(explode("\n", $output))) . ']';
                        });
    }

    /**
     * Get single assertion from the API.
     *
     * @param string $eventid The id of the event.
     * @return array The event data.
     */
    public function get_event($eventid) {
        $this->require_client_id();
        return $this->api_request('/event/' . self::get_client_id() . '/' . $eventid,
                        'get');
    }

    /**
     * Get revoked for assertion from the API.
     *
     * @param string $eventid The id of the event.
     * @return array The revoked data.
     */
    public function get_revoked($eventid) {
        $this->require_client_id();
        return $this->api_request('/event/' . self::get_client_id() . '/' . $eventid . '/revoked',
                        'get');
    }

    /**
     * Deletes all client badges. Use with caution.
     */
    public function delete_badge($badgeid) {
        $this->require_client_id();
        return $this->api_request('/badge/' . self::get_client_id() . '/' . $badgeid, 'delete');
    }
    
    /**
     * Deletes all client badges. Use with caution.
     */
    public function delete_badges() {
        $this->require_client_id();
        return $this->api_request('/badge/' . self::get_client_id(), 'delete');
    }

    /**
     * Exports a badge to Open Badge Factory
     *
     * @param obf_badge $badge The badge.
     */
    public function export_badge(obf_badge $badge) {
        $this->require_client_id();

        $params = array(
            'name' => $badge->get_name(),
            'description' => $badge->get_description(),
            'image' => $badge->get_image(),
            'css' => $badge->get_criteria_css(),
            'criteria_html' => $badge->get_criteria_html(),
            'email_subject' => $badge->get_email()->get_subject(),
            'email_body' => $badge->get_email()->get_body(),
            'email_link_text' => $badge->get_email()->get_link_text(),
            'email_footer' => $badge->get_email()->get_footer(),
            'expires' => '',
            'tags' => array(),
            'draft' => $badge->is_draft()
        );

        $this->api_request('/badge/' . self::get_client_id(), 'post', $params);
    }
    /**
     * Issues a badge.
     *
     * @param obf_badge $badge The badge to be issued.
     * @param string[] $recipients The recipient list, array of emails.
     * @param int $issuedon The issuance date as a Unix timestamp
     * @param string $email The email to send (template).
     * @param string $criteriaaddendum The criteria addendum.
     */

    public function issue_badge(obf_badge $badge, $recipients, $issuedon,
                                $email, $criteriaaddendum = '', $course, $activity) {
        global $CFG;
        $course_name = $badge->get_course_name($course);

        $this->require_client_id();
        $params = array(
            'recipient' => $recipients,
            'issued_on' => $issuedon,
            'api_consumer_id' => OBF_API_CONSUMER_ID,
            'log_entry' => array('course_id' => $course,
                                'course_name' => $course_name,
                                'activity_name' => $activity,
                                'wwwroot' => $CFG->wwwroot),
            'show_report' => 1
        );
        if (!is_null($email)) {
            $params['email_subject'] = $email->get_subject();
            $params['email_body'] = $email->get_body();
            $params['email_footer'] = $email->get_footer();
            $params['email_link_text'] = $email->get_link_text();
        }
        if (!empty($criteriaaddendum)) {
            $badge_override_params = array();
            $badge_override_params['criteria_add'] = $criteriaaddendum;
            $params['badge_override'] = $badge_override_params;
        }

        if (!is_null($badge->get_expires()) && $badge->get_expires() > 0) {
            $params['expires'] = $badge->get_expires();
        }

        $this->api_request('/badge/' . self::get_client_id() . '/' . $badge->get_id(),
                'post', $params);
    }
    /**
     * Revoke an issued event.
     *
     * @param string $eventid
     * @param string[] $emails Array of emails to revoke the event for.
     */
    public function revoke_event($eventid, $emails) {
        $this->require_client_id();
        $this->api_request('/event/' . self::get_client_id() . '/' . $eventid . '/?email=' . implode('|', $emails),
                'delete');
    }

    /**
     * A wrapper for obf_client::request, prefixing $path with the API url.
     *
     * @param string $path
     * @param string $method Supported methods are: 'get', 'post' and 'delete'
     * @param array $params
     * @param Closure $preformatter
     * @return mixed Response from request.
     * @see self::request
     */
    protected function api_request($path, $method = 'get',
                                   array $params = array(),
                                   Closure $preformatter = null) {
        return $this->request(self::get_api_url() . $path, $method, $params,
                        $preformatter);
    }

    /**
     * Makes a CURL-request to OBF API.
     *
     * @param string $url The API path.
     * @param string $method The HTTP method.
     * @param array $params The params of the request.
     * @param Closure $preformatter In some cases the returned string isn't
     *      valid JSON. In those situations one has to manually preformat the
     *      returned data before decoding the JSON.
     * @return array The json-decoded response.
     * @throws Exception In case something goes wrong.
     */
    public function request($url, $method = 'get', array $params = array(),
                            Closure $preformatter = null) {
        $curl = $this->get_transport();
        $options = $this->get_curl_options();
        if ($method == 'get') {
            $output = $curl->get($url, $params, $options);
        } else if ($method == 'delete') {
            $output = $curl->delete($url, $params, $options);
        } else {
            $output = $curl->post($url, json_encode($params), $options);
        }

        if ($output !== false) {
            if (!is_null($preformatter)) {
                $output = $preformatter($output);
            }

            $response = json_decode($output, true);
        }

        $info = $curl->get_info();

        if ($this->enablerawresponse) {
            $this->rawresponse = $curl->get_raw_response();
        }
        $this->httpcode = $info['http_code'];
        $this->error = '';

        // Codes 2xx should be ok.
        if (is_numeric($this->httpcode) && ($this->httpcode < 200 || $this->httpcode >= 300)) {
            $this->error = isset($response['error']) ? $response['error'] : '';
            $appendtoerror = defined('PHPUNIT_TEST') && PHPUNIT_TEST ? ' ' . $method . ' ' . $url : '';
            throw new Exception(get_string('apierror' . $this->httpcode, 'local_obf',
                    $this->error) . $appendtoerror, $this->httpcode);
        }

        return $response;
    }

    /**
     * Returns a new curl-instance.
     *
     * @return \curl
     */
    protected function get_transport() {
        if (!is_null($this->transport)) {
            return $this->transport;
        }

        // Use Moodle's curl-object if no transport is defined.
        global $CFG;

        include_once($CFG->libdir . '/filelib.php');

        return new curl();
    }
    /**
     * Get HTTP error code of the last request.
     * @return integer HTTP code, 200-299 should be good, 404 means item was not found.
     */
    public function get_http_code() {
        return $this->httpcode;
    }
    /**
     * Get error message of the last request.
     * @return string Last error message or an empty string if last request was a success.
     */
    public function get_error() {
        return $this->error;
    }

    /**
     * Get raw response.
     * @return string[] Raw response.
     */
    public function get_raw_response() {
        return $this->rawresponse;
    }
    /**
     * Enable/disable storing raw response.
     * @param bool $enable
     * @return obf_client This object.
     */
    public function set_enable_raw_response($enable) {
        $this->enablerawresponse = $enable;
        $this->rawresponse = null;
        return $this;
    }

    /**
     * Returns the default CURL-settings for a request.
     *
     * @return array
     */
    public function get_curl_options() {
        return array(
            'RETURNTRANSFER'    => true,
            'FOLLOWLOCATION'    => false,
            'SSL_VERIFYHOST'    => 2,
            'SSL_VERIFYPEER'    => 1,
            'SSLCERT'           => $this->get_cert_filename(),
            'SSLKEY'            => $this->get_pkey_filename()
        );
    }

    public function pub_get_badge($badgeid, $eventid) {
        $params = array('v' => '1.1', 'event' => $eventid);
        $badge = $this->api_request('/badge/_/' . $badgeid . '.json', 'get', $params);
        return $badge;
    }
}
