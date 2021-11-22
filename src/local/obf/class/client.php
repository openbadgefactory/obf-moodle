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
 * @copyright  2013-2021, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../lib.php');

require_once($CFG->libdir . '/filelib.php');

/**
 * Class for handling the communication to Open Badge Factory API using legacy authentication.
 *
 * @copyright  2013-2021, Open Badge Factory Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_client {
    /**
     * @var $client Static obf_client singleton
     */
    private static $client = null;

    /**
     * @var string Static current client id
     */
    private static $client_id = null;

    /**
     * @var curl|null Transport. Curl.
     */
    private $transport = null;

    /**
     * @var object local_obf_oauth2 table row
     */
    private $oauth2 = null;

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
     * Returns the client instance.
     *
     * @param curl|null $transport
     * @return obf_client The client.
     */
    public static function get_instance($transport=null) {
        global $DB;
        if (is_null(self::$client)) {

            self::$client = new self();

            $oauth2 = $DB->get_records('local_obf_oauth2', null, 'client_name');
            if (count($oauth2) > 0) {
                if (self::$client_id) {
                    foreach ($oauth2 as $o2) {
                        if ($o2->client_id === self::$client_id) {
                            self::$client->set_oauth2($o2);
                            break;
                        }
                    }
                } else {
                    // use the first one
                    $first = array_shift($oauth2);
                    self::$client->set_oauth2($first);
                }
            }

            if (!is_null($transport)) {
                self::$client->set_transport($transport);
            }
        }

        return self::$client;
    }

    /**
     * Set current active OAuth2 connection. Returns the client instance.
     *
     * @param string client_id in local_obf_oauth2 table row
     * @return obf_client The client.
     */
    public static function connect($id, $user=null, $transport=null) {
        self::$client    = null;
        self::$client_id = $id;

        $ok = true;

        $legacy_id = get_config('local_obf', 'obfclientid');
        $available = self::get_available_clients($user);

        if (empty($legacy_id) && empty($available)) {
            // No connection available
            $ok = false;
        }
        else if ($legacy_id) {
            // Legacy connection
            if ($id) {
                $ok = $id === $legacy_id;
            }
        }
        else if (!is_null($user)) {
            // OAuth2 connections
            $ok = is_null($id) ? !empty(self::get_available_clients($user)) : isset($available[$id]);
        }

        if (!$ok) {
            throw new Exception(get_string('apierror0', 'local_obf'), 0);
        }

        return self::get_instance($transport);
    }

    /**
     * Get configured OAuth2 clients
     *
     * @return array id and name pairs
     */
    public static function get_available_clients($user=null) {
        global $CFG, $DB, $USER;

        if (is_null($user)) {
            $user = $USER;
        }

        if (in_array($user->id, explode(',', $CFG->siteadmins))) {
            // Can see all connected clients
            return $DB->get_records_menu('local_obf_oauth2', null, 'client_name', 'client_id, client_name');
        }

        // Get connected clients based on user role access (role can be in any context)
        $sql =
           "SELECT o.client_id, o.client_name FROM {local_obf_oauth2} o
            INNER JOIN {local_obf_oauth2_role} r ON o.id = r.oauth2_id
            INNER JOIN {role_assignments} ra ON r.role_id = ra.roleid
            WHERE ra.userid = ?
            ORDER BY o.client_name";

        return $DB->get_records_sql_menu($sql, array($user->id));
    }

    public static function has_client_id() {
        global $DB;
        return $DB->count_records('local_obf_oauth2') > 0 || !empty(get_config('local_obf', 'obfclientid'));
    }

    /**
     * Checks that the OBF client id is stored to plugin settings.
     *
     * @throws Exception If the client id is missing.
     */
    public function require_client_id() {
        if (empty($this->oauth2->client_id) && empty(get_config('local_obf', 'obfclientid'))) {
            throw new Exception(get_string('apierror0', 'local_obf'), 0);
        }
    }

    /**
     * Get OBF api url
     *
     * @return string
     */
    private function obf_url() {
        if (isset($this->oauth2->obf_url)) {
            return $this->oauth2->obf_url;
        }
        return get_config('local_obf', 'apiurl');
    }

    /**
     * Get current active client id
     *
     * @return string
     */
    public function client_id() {
        if (isset($this->oauth2->client_id)) {
            return $this->oauth2->client_id;
        }
        return get_config('local_obf', 'obfclientid');
    }

    /**
     * Get current active client id
     *
     * @return string
     */
    public function local_events() {
        return get_config('local_obf', 'apidataretrieve') == self::RETRIEVE_LOCAL;
    }


    /**
     * Set current active API client credentials
     *
     * @param object $input Input row from local_obf_oauth2 table
     * @return null
     */
    public function set_oauth2($input) {

        if (!preg_match('/^https?:\/\/.+/', $input->obf_url)) {
            throw new Exception('Invalid parameter $obf_url');
        }
        if (!preg_match('/^\w+$/', $input->client_id)) {
            throw new Exception('Invalid parameter $client_id');
        }
        if (!preg_match('/^\w+$/', $input->client_secret)) {
            throw new Exception('Invalid parameter $client_secret');
        }

        self::$client_id = $input->client_id;

        $input->obf_url = preg_replace('/\/+$/', '', $input->obf_url);

        $this->oauth2 = $input;
    }

    /**
     * Get access token. Request a new access token using client credentials if needed.
     *
     * @return array access token and expiration timestamp
     */
    public function oauth2_access_token() {
        global $DB;

        $this->require_client_id();

        if ( !isset($this->oauth2->access_token) || $this->oauth2->token_expires < time()) {

            $url = $this->obf_url() . '/v1/client/oauth2/token';

            $params = array(
                   'grant_type' => 'client_credentials',
                    'client_id' => $this->oauth2->client_id,
                'client_secret' => $this->oauth2->client_secret
            );

            $curl = $this->get_transport();
            $options = $this->get_curl_options(false);

            $res = $curl->post($url, http_build_query($params), $options);

            $res = json_decode($res);

            $this->oauth2->access_token = $res->access_token;
            $this->oauth2->token_expires = time() + $res->expires_in;

            $sql = "UPDATE {local_obf_oauth2} SET access_token = ?, token_expires = ? WHERE client_id = ?";
            $DB->execute($sql, array($this->oauth2->access_token, $this->oauth2->token_expires, $this->oauth2->client_id));
        }

        return array(
            'access_token'  => $this->oauth2->access_token,
            'token_expires' => $this->oauth2->token_expires
        );
    }

    /**
     * Returns a new curl-instance.
     *
     * @return \curl
     */
    public function get_transport() {
        if (!is_null($this->transport)) {
            return $this->transport;
        }

        // Use Moodle's curl-object if no transport is defined.
        return new curl();
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
     * Returns the default CURL-settings for a request.
     *
     * @return array
     */
    private function get_curl_options($auth=true) {

        // don't verify localhost dev server
        $url = $this->obf_url();
        $secure = strpos($url, 'https://localhost/') !== 0;

        $opt = array(
            'RETURNTRANSFER'    => true,
            'FOLLOWLOCATION'    => false,
            'SSL_VERIFYHOST'    => $secure ? 2 : 0,
            'SSL_VERIFYPEER'    => $secure ? 1 : 0
        );

        if ($auth) {
            if (isset($this->oauth2)) {
                $token = $this->oauth2_access_token();
                $opt['HTTPHEADER'] = array("Authorization: Bearer " . $token['access_token']);
            }
            else {
                $opt['SSLCERT'] = $this->get_cert_filename();
                $opt['SSLKEY']  = $this->get_pkey_filename();
            }
        }

        return $opt;
    }


    /**
     * Decode line-delimited json
     *
     * @param string $input response string
     * @return array The json-decoded response.
     */
    private function decode_ldjson($input) {
        $out = array();
        foreach (explode("\r\n", $input) AS $chunk) {
            if ($chunk) {
                $out[] = json_decode($chunk, true);
            }
        }
        return $out;
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
     * Makes a CURL-request to OBF API (new style).
     *
     * @param string $method The HTTP method.
     * @param string $url The API path.
     * @param array $params The params of the request.
     * @return string The response string.
     * @throws Exception In case something goes wrong.
     */
    private function _request($method, $url, $params=array(), $retry=true, $other_oauth2=null) {
        $curl = $this->get_transport();
        $options = $this->get_curl_options();

        if ($method === 'get') {
            $response = $curl->get($url, $params, $options);
        } else if ($method === 'post') {
            $response = $curl->post($url, json_encode($params), $options);
        } else if ($method === 'put') {
            $response = $curl->put($url, json_encode($params), $options);
        } else if ($method === 'delete') {
            $response = $curl->delete($url, $params, $options);
        } else {
            throw new Exception('unknown method ' . $method);
        }

        $this->rawresponse = null;
        if ($this->enablerawresponse) {
            $this->rawresponse = $curl->get_raw_response();
        }

        $info = $curl->get_info();

        if ($info['http_code'] === 403 && empty(get_config('local_obf', 'obfclientid'))) {
            if ($retry) {
                // try again one time
                return $this->_request($method, $url, $params, false, $other_oauth2);
            }
            // try with all other available connections
            if (is_null($other_oauth2)) {
                $other_oauth2 = $DB->get_records_select('local_obf_oauth2', 'client_id != ?', array($this->oauth2->client_id));
            }
            if (!empty($other_oauth2)) {
                $this->set_oauth2(array_shift($other_oauth2));
                return $this->_request($method, $url, $params, true, $other_oauth2);
            }
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
     * Makes a CURL-request to OBF API (legacy style).
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
    public function request($url, $method = 'get', array $params = array(), Closure $preformatter = null) {

        $output = $this->_request($method, $url, $params);

        if ($output !== false) {
            if (!is_null($preformatter)) {
                $output = $preformatter($output);
            }
            $response = json_decode($output, true);
        }

        return $response;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Tests the connection to OBF API.
     *
     * @return int Returns the error code on failure and -1 on success.
     */
    public function test_connection() {
        try {
            $url = $this->obf_url() . '/v1/ping/' . $this->client_id();
            $this->_request('get', $url);
            return -1;
        } catch (Exception $exc) {
            return $exc->getCode();
        }
    }


    /**
     * Get all the badges from the API.
     *
     * @param string[] $categories Filter badges by these categories.
     * @return array The badges data.
     */
    public function get_badges(array $categories = array(), $query = '') {
        $params = array('draft' => 0, 'external' => 1);

        if (count($categories) > 0) {
            $params['category'] = implode('|', $categories);
        }
        if (!empty($query)) {
            $params['query'] = $query;
        }

        $url = $this->obf_url() . '/v1/badge/' . $this->client_id();
        $res = $this->_request('get', $url, $params);

        return $this->decode_ldjson($res);
    }

    /**
     * Get all the badges from the API for all configured OAuth2 clients.
     *
     * @param string[] $categories Filter badges by these categories.
     * @return array The badges data.
     */
    public function get_badges_all(array $categories = array(), $query = '') {
        global $DB;

        $oauth2 = $DB->get_records('local_obf_oauth2', null, 'client_name');

        if (empty($oauth2)) {
            return $this->get_badges($categories, $query);
        }

        $prev_oauth2 = $this->oauth2;

        $out = [];
        foreach ($oauth2 as $o2) {
            $this->set_oauth2($o2);
            $out = array_merge($out, $this->get_badges($categories, $query));
        }
        $this->set_oauth2($prev_oauth2);

        return $out;
    }

    /**
     * Get a single badge from the API.
     *
     * @param string $badgeid
     * @throws Exception If the request fails
     * @return array The badge data.
     */
    public function get_badge($badgeid) {
        $url = $this->obf_url() . '/v1/badge/' . $this->client_id() . '/' . $badgeid;
        $res = $this->_request('get', $url);

        return json_decode($res, true);
    }

    /**
     * Get issuer data from the API.
     *
     * @throws Exception If the request fails
     * @return array The issuer data.
     */
    public function get_issuer() {
        $url = $this->obf_url() . '/v1/client/' . $this->client_id();
        $res = $this->_request('get', $url);

        return json_decode($res, true);
    }

    public function get_client_info() {
        return $this->get_issuer();
    }

    /**
     * Get badge issuing events from the API.
     *
     * @param string $badgeid The id of the badge.
     * @param string $email The email address of the recipient.
     * @param array $params Optional extra params for the query.
     * @return array The event data.
     */
    public function get_assertions($badgeid = null, $email = null, $params = array()) {

        if (is_null($badgeid) && !is_null($email)) {
            return array();
        }
        if ($this->local_events()) {
            $params['api_consumer_id'] = OBF_API_CONSUMER_ID;
        }
        if (!is_null($badgeid)) {
            $params['badge_id'] = $badgeid;
        }
        if (!is_null($email) && $email != "") {
            $params['email'] = $email;
        }

        $url = $this->obf_url() . '/v1/event/' . $this->client_id();
        $res = $this->_request('get', $url, $params);

        return $this->decode_ldjson($res);
    }

    /**
     * Get single recipient's all badge issuing events from the API for all connections.
     *
     * @param string $badgeid The id of the badge.
     * @param string $email The email address of the recipient.
     * @param array $params Optional extra params for the query.
     * @return array The event data.
     */
    public function get_assertions_all($email, $params = array()) {
        global $DB;

        if ($this->local_events()) {
            $params['api_consumer_id'] = OBF_API_CONSUMER_ID;
        }
        $params['email'] = $email;

        if (get_config('local_obf', 'obfclientid')) {
            // legacy connection, only one client
            return $this->get_assertions(null, $email, $params);
        }

        $prev_o2 = $this->oauth2;

        $oauth2 = $DB->get_records('local_obf_oauth2');

        $out = [];
        if (!empty($oauth2)) {
            foreach ($oauth2 as $o2) {
                $this->set_oauth2($o2);

                $url = $this->obf_url() . '/v1/event/' . $this->client_id();
                $res = $this->_request('get', $url, $params);

                $out = array_merge($out, $this->decode_ldjson($res));
            }
        }
        $this->set_oauth2($prev_o2);

        return $out;
    }

    /**
     * Get single issuing event from the API.
     *
     * @param string $eventid The id of the event.
     * @return array The event data.
     */
    public function get_event($eventid) {
        $url = $this->obf_url() . '/v1/event/' . $this->client_id() . '/' . $eventid;
        $res = $this->_request('get', $url);

        return json_decode($res, true);
    }

    /**
     * Get event's revoked assertions from the API.
     *
     * @param string $eventid The id of the event.
     * @return array The revoked data.
     */
    public function get_revoked($eventid) {
        $url = $this->obf_url() . '/v1/event/' . $this->client_id() . '/' . $eventid . '/revoked';
        $res = $this->_request('get', $url);

        return json_decode($res, true);
    }


    /**
     * Get badge categories from the API.
     *
     * @return array The category data.
     */
    public function get_categories() {
        $url = $this->obf_url() . '/v1/badge/' . $this->client_id() . '/_/categorylist';
        $res = $this->_request('get', $url);

        return json_decode($res, true);
    }

    /**
     * Delete a badge. Use with caution.
     */
    public function delete_badge($badgeid) {
        $url = $this->obf_url() . '/v1/badge/' . $this->client_id() . '/' . $badgeid;
        $this->_request('delete', $url);
    }

    /**
     * Deletes all client badges. Use with caution.
     */
    public function delete_badges() {
        $url = $this->obf_url() . '/v1/badge/' . $this->client_id();
        $this->_request('delete', $url);
    }

    /**
     * Exports a badge to Open Badge Factory
     *
     * @param obf_badge $badge The badge.
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
            'email_link_text' => $badge->get_email()->get_link_text(),
            'email_footer' => $badge->get_email()->get_footer(),
            'expires' => '',
            'tags' => array(),
            'draft' => $badge->is_draft()
        );

        $url = $this->obf_url() . '/v1/badge/' . $this->client_id();
        $this->_request('post', $url, $params);
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

    public function issue_badge(obf_badge $badge, $recipients, $issuedon, $email, $criteriaaddendum, $course, $activity) {
        global $CFG, $DB;

        $users = $DB->get_records_list('user', 'email', $recipients, '', 'id, email');
        $now = time();
        $sql = "INSERT IGNORE INTO {local_obf_history_emails} (user_id, email, timestamp) VALUES (?,?,?)";
        foreach ($users as $user) {
            $DB->execute($sql, array($user->id, $user->email, $now));
        }

        $course_name = $badge->get_course_name($course);

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
            $params['badge_override'] = array('criteria_add' => $criteriaaddendum);
        }

        if (!is_null($badge->get_expires()) && $badge->get_expires() > 0) {
            $params['expires'] = $badge->get_expires();
        }

        $url = $this->obf_url() . '/v1/badge/' . $this->client_id() . '/' . $badge->get_id();
        $this->_request('post', $url, $params);
    }

    /**
     * Revoke an issued event.
     *
     * @param string $eventid
     * @param string[] $emails Array of emails to revoke the event for.
     */
    public function revoke_event($eventid, $emails) {
        $emails = array_map('urlencode', $emails);
        $url = $this->obf_url() . '/v1/event/' . $this->client_id() . '/' . $eventid . '/?email=' . implode('|', $emails);
        $this->_request('delete', $url);
    }

    public function pub_get_badge($badgeid, $eventid) {
        $url = $this->obf_url() . '/v1/badge/_/' . $badgeid . '.json';
        $params = array('v' => '1.1', 'event' => $eventid);
        $res = $this->_request('get', $url, $param);

        return json_decode($res, true);
    }

    // LEGACY api auth

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
    private function api_url_maker($url) {
        $version = "v1";
        return $url . $version;
    }

    public function get_branding_image_url($imagename = 'issued_by') {
        return $this->obf_url() . '/v1/badge/_/' . $imagename . '.png';
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
        $curlopts = $this->get_curl_options(false);

        $url = $this->url_checker($url);

        $apiurl = $this->api_url_maker($url);

        // For localhost test server
        if (strpos($apiurl, 'https://localhost/') === 0) {
            $curlopts['SSL_VERIFYHOST'] = 0;
            $curlopts['SSL_VERIFYPEER'] = 0;
        }

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
}
