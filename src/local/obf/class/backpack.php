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
 * Backpack. Supports Open Badge Passport and Mozilla Backpack.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/assertion.php');
require_once(__DIR__ . '/assertion_collection.php');

/**
 * Class for handling the communication between the plugin and Mozilla Backpack.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_backpack {

    /**
     * @var Mozilla Backpack displayer API url.
     */
    const BACKPACK_URL = 'http://beta.openbadges.org/displayer/';
    /**
     * @var Open Badge Passport displayer API url.
     */
    const OPB_URL = 'https://openbadgepassport.com/displayer/';
    /**
     * @var Mozilla Persona verifier url.
     */
    const PERSONA_VERIFIER_URL = 'https://verifier.login.persona.org/verify';

    /**
     * @var Mozilla Backpack provider id in the database.
     */
    const BACKPACK_PROVIDER_MOZILLA = 0;
    /**
     * @var Open Badge Passport provider id in the database.
     */
    const BACKPACK_PROVIDER_OBP = 1;

    /**
     * @var Id in database
     */
    private $id = -1;
    /**
     * @var Userid.
     */
    private $userid = -1;
    /**
     * @var Email address in backpack.
     */
    private $email = '';
    /**
     * @var Backpack id.
     */
    private $backpackid = -1;
    /**
     * @var List or groups to display.
     */
    private $groups = array();
    /**
     * @var Transport.
     */
    private $transport = null;
    /**
     * @var Provider id self::BACKPACK_PROVIDER_*
     */
    private $provider = 0;


    /**
     * @var Array of provider ids.
     */
    private static $providers = array(self::BACKPACK_PROVIDER_MOZILLA,
            self::BACKPACK_PROVIDER_OBP);
    /**
     * @var Array of provider name shortened for use as pre-/postfixes on forms and localisations.
     */
    private static $providershortnames = array(
        self::BACKPACK_PROVIDER_MOZILLA => 'moz',
        self::BACKPACK_PROVIDER_OBP => 'obp'
    );
    /**
     * @var API urls as array.
     */
    private static $apiurls = array(
        self::BACKPACK_PROVIDER_MOZILLA => self::BACKPACK_URL,
        self::BACKPACK_PROVIDER_OBP => self::OPB_URL
    );
    /**
     * @var Array of settings on should email address verification be used,
     *      or should we assume moodle email is configured at the backpack provider.
     */
    private static $providerrequiresemailverification = array(
        self::BACKPACK_PROVIDER_MOZILLA => true,
        self::BACKPACK_PROVIDER_OBP => false
    );
    /**
     * @var Associative array to match provider ids to assertion source ids.
     */
    private static $backpackprovidersources = array(
        self::BACKPACK_PROVIDER_MOZILLA => obf_assertion::ASSERTION_SOURCE_MOZILLA,
        self::BACKPACK_PROVIDER_OBP => obf_assertion::ASSERTION_SOURCE_OBP
    );
    /**
     * Constructor.
     * @param curl|null $transport
     * @param int $provider
     */
    public function __construct($transport = null, $provider = self::BACKPACK_PROVIDER_MOZILLA) {
        $this->set_provider($provider);
        if (!is_null($transport)) {
            $this->set_transport($transport);
        }
    }
    /**
     * Get API URL.
     * @return string API URL.
     */
    private function get_apiurl() {
        if (!empty($this->provider)) {
            return self::$apiurls[$this->provider];
        }
        return self::BACKPACK_URL;
    }

    /**
     * Get backpack instance.
     *
     * @param stdClass $user
     * @param int $provider
     * @return self
     */
    public static function get_instance($user, $provider = self::BACKPACK_PROVIDER_MOZILLA) {
        return self::get_instance_by_fields(array('user_id' => $user->id), $provider);
    }

    /**
     * Get instance matching fields in the database.
     *
     * @param array $fields
     * @param int $provider
     * @return \self|boolean
     */
    protected static function get_instance_by_fields(array $fields, $provider = self::BACKPACK_PROVIDER_MOZILLA) {
        global $DB;
        $fields = array_merge($fields, array('backpack_provider' => $provider));
        $backpackobj = $DB->get_record('local_obf_backpack_emails', $fields, '*',
                IGNORE_MULTIPLE);

        if ($backpackobj === false) {
            if (!self::does_provider_require_email_verification($provider) && array_key_exists('user_id', $fields)) {
                $user = $DB->get_record('user', array('id' => $fields['user_id']));
                if ($user) {
                    $obj = new self();
                    $obj->set_user_id($user->id);
                    $obj->set_provider($provider);
                    try {
                        $obj->connect($user->email);
                    } catch (Exception $e) {
                        return false; // User does not have a backpack at the provider.
                    }
                    return $obj;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        $obj = new self();
        $obj->set_user_id($backpackobj->user_id);
        $obj->set_backpack_id($backpackobj->backpack_id);
        $obj->set_email($backpackobj->email);
        $obj->set_id($backpackobj->id);
        $obj->set_groups(unserialize($backpackobj->groups));
        $obj->set_provider($provider);

        return $obj;
    }

    /**
     * Get instance matching email
     *
     * @param string $email
     * @param int $provider
     */
    public static function get_instance_by_backpack_email($email, $provider = self::BACKPACK_PROVIDER_MOZILLA) {
        return self::get_instance_by_fields(array('email' => $email), $provider);
    }

    /**
     * Get instance matching user id.
     *
     * @param int $userid
     * @param moodle_database $db
     * @param int $provider
     * @return self
     */
    public static function get_instance_by_userid($userid, moodle_database $db, $provider = self::BACKPACK_PROVIDER_MOZILLA) {
        return self::get_instance($db->get_record('user', array('id' => $userid)), $provider);
    }

    /**
     * Returns an array of backpack email addresses matching the user ids found from $userids
     *
     * @param array $userids
     * @return String[] An array of backpack emails
     */
    public static function get_emails_by_userids(array $userids) {
        global $DB;

        $records = $DB->get_records_list('local_obf_backpack_emails', 'user_id',
                $userids, '', 'id,user_id,email');

        $ret = array();

        foreach ($records as $record) {
            $ret[$record->user_id] = $record->email;
        }

        return $ret;
    }

    /**
     * Get user ids who have backpack connections saved.
     *
     * @param int $provider
     * @return int[]
     */
    public static function get_user_ids_with_backpack($provider = null) {
        global $DB;

        $ret = array();
        if (is_null($provider)) {
            $records = $DB->get_records_select('local_obf_backpack_emails',
                    'backpack_id > 0');
        } else {
            $records = $DB->get_records_sql('SELECT * FROM {local_obf_backpack_emails} ' .
                    'WHERE backpack_id > 0 AND backpack_provider = :provider', array('provider' => $provider));
        }

        foreach ($records as $record) {
            $ret[] = $record->user_id;
        }

        return $ret;
    }

    /**
     * Connect backpack to email-address.
     *
     * @param string $email
     * @return boolean
     */
    protected function connect_to_backpack($email) {
        $curl = $this->get_transport();
        $output = $curl->post($this->get_apiurl() . 'convert/email',
                array('email' => $email));
        $json = json_decode($output);
        $code = $curl->info['http_code'];

        if ($code == 200) {
            return $json->userId;
        }

        return false;
    }

    /**
     * Get transport.
     * @return curl
     */
    protected function get_transport() {
        if (!is_null($this->transport)) {
            return $this->transport;
        }

        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new curl();

        return $curl;
    }

    /**
     * Tries to verify the assertion and returns the associated email address
     * if verification was successful. Return false otherwise.
     *
     * @param string $assertion The assertion from Mozilla Persona.
     * @return string Returns the users email or false if verification
     *      fails.
     */
    public function verify($assertion) {
        global $CFG;

        $urlparts = parse_url($CFG->wwwroot);
        $port = isset($urlparts['port']) ? ':' . $urlparts['port'] : '';
        $url = $urlparts['scheme'] . '://' . $urlparts['host'] . $port;
        $params = array('assertion' => $assertion, 'audience' => $url);

        $curl = $this->get_transport();
        $curlopts = array(
            'RETURNTRANSFER' => true,
            'SSL_VERIFYPEER' => 0,
            'SSL_VERIFYHOST' => 2
        );

        $curl->setHeader('Content-Type: application/json');
        $output = $curl->post(self::PERSONA_VERIFIER_URL, json_encode($params),
                $curlopts);

        $ret = json_decode($output);

        if ($ret->status == 'failure') {
            $error = get_string('verification_failed', 'local_obf', $ret->reason);

            // No need for debug messages when running tests.
            if (!PHPUNIT_TEST) {
                debugging($error . '. Assertion: ' . var_export($assertion, true),
                        DEBUG_DEVELOPER);
            }

            throw new Exception($error);
        }

        return $ret->email;
    }

    /**
     * Disconnect from backpack.
     */
    public function disconnect() {
        $this->delete();
    }

    /**
     * Connect to backpack.
     *
     * @param string $email
     * @throws Exception
     */
    public function connect($email) {
        $this->set_email($email);
        $backpackid = $this->connect_to_backpack($email);

        if ($backpackid !== false) {
            $this->set_backpack_id($backpackid);
            $this->save();
        } else {
            $this->disconnect();
            throw new Exception(get_string('backpackemailnotfound', 'local_obf',
                    s($email)));
        }
    }
    /**
     * Get groups for backpack.
     * @return array groups
     */
    public function get_groups() {
        $curl = $this->get_transport();
        $output = $curl->get($this->get_apiurl() . $this->get_backpack_id() . '/groups.json');
        $json = json_decode($output);

        return $json->groups;
    }
    /**
     * Get assertions in a group.
     * @param mixed $groupid
     * @param int $limit
     * @return obf_assertion_collection Assertions in the group
     */
    public function get_group_assertions($groupid, $limit = -1) {
        if ($this->backpackid < 0) {
            throw new Exception('Backpack connection isn\'t set.');
        }

        $curl = $this->get_transport();
        $output = $curl->get($this->get_apiurl() . $this->get_backpack_id() . '/group/' . $groupid . '.json');
        $json = json_decode($output);
        $assertions = new obf_assertion_collection();

        foreach ($json->badges as $item) {
            $assertion = new obf_assertion();
            $assertion->set_source($this->get_source());
            $badge = new obf_badge();
            $badge->set_name($item->assertion->badge->name);
            $badge->set_image($item->imageUrl);
            $badge->set_description($item->assertion->badge->description);
            $badge->set_criteria_url($item->assertion->badge->criteria);
            $badge->set_issuer(obf_issuer::get_instance_from_backpack_data($item->assertion->badge->issuer));

            $assertion->set_badge($badge);

            if (isset($item->assertion->issued_on)) {
                $assertion->set_issuedon($item->assertion->issued_on);
            }

            $assertions->add_assertion($assertion);

            if ($limit > 0 && count($assertions) == $limit) {
                break;
            }
        }

        return $assertions;
    }

    /**
     * Get backpack assertions for all allowed groups.
     *
     * @param int $limit
     * @return \obf_assertion_collection
     * @throws Exception
     */
    public function get_assertions($limit = -1) {
        if (count($this->groups) == 0) {
            throw new Exception('No badge groups selected.');
        }

        $assertions = new obf_assertion_collection();

        foreach ($this->groups as $groupid) {
            $assertions->add_collection($this->get_group_assertions($groupid));
        }

        return $assertions;
    }

    /**
     * Get assertions and return them as an arrays in array.
     * @param int $limit
     * @return array[]
     */
    public function get_assertions_as_array($limit = -1) {
        $assertions = $this->get_assertions($limit);
        $ret = array();

        foreach ($assertions as $assertion) {
            $ret[] = $assertion->toArray();
        }

        return $ret;
    }

    /**
     * Save backpack connection details.
     */
    public function save() {
        global $DB;

        $obj = new stdClass();
        $obj->user_id = $this->userid;
        $obj->email = $this->email;
        $obj->backpack_id = $this->backpackid;
        $obj->backpack_provider = $this->provider;
        $obj->groups = serialize($this->groups);

        if ($this->id > 0) {
            $obj->id = $this->id;
            $DB->update_record('local_obf_backpack_emails', $obj);
        } else {
            $id = $DB->insert_record('local_obf_backpack_emails', $obj);
            $this->set_id($id);
        }
    }
    /**
     * Delete record. (Disconnect backpack)
     */
    public function delete() {
        global $DB;

        if ($this->id > 0) {
            $DB->delete_records('local_obf_backpack_emails', array('id' => $this->id));
        }
    }
    /**
     * Is backpack connected?
     */
    public function is_connected() {
        return $this->backpackid > 0;
    }

    /**
     * Get id.
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get user id.
     * @return int
     */
    public function get_user_id() {
        return $this->userid;
    }

    /**
     * Get email address.
     * @return string
     */
    public function get_email() {
        return $this->email;
    }

    /**
     * Get backpack id.
     * @return mixed
     */
    public function get_backpack_id() {
        return $this->backpackid;
    }

    /**
     * Set id.
     * @param int $id
     * @return $this
     */
    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Set user id.
     * @param int $userid
     */
    public function set_user_id($userid) {
        $this->userid = $userid;
        return $this;
    }

    /**
     * Set email address.
     * @param string $email Email address
     */
    public function set_email($email) {
        $this->email = $email;
        return $this;
    }

    /**
     * Set backpack id.
     * @param mixed $backpackid Backpack id on the backpack provider
     */
    public function set_backpack_id($backpackid) {
        $this->backpackid = $backpackid;
        return $this;
    }

    /**
     * Get group ids.
     * @return array Groups set to be displayed.
     */
    public function get_group_ids() {
        return $this->groups;
    }

    /**
     * Set groups.
     * @param array $groups
     */
    public function set_groups($groups) {
        $this->groups = is_array($groups) ? $groups : array();
        return $this;
    }

    /**
     * Set transport.
     * @param curl $transport
     */
    public function set_transport($transport) {
        $this->transport = $transport;
    }
    /**
     * Backpack connection saved to database?
     * @return bool True if saved.
     */
    public function exists() {
        return !empty($this->id) && $this->id > 0;
    }
    /**
     * Check if backpack requires email verification.
     * @return bool True if email verification is required.
     */
    public function requires_email_verification() {
        return self::does_provider_require_email_verification($this->provider);
    }
    /**
     * Check if backpacks with provider of $provider require email verification.
     * @param int $provider
     * @return bool True if email verification is required.
     */
    public static function does_provider_require_email_verification($provider) {
        return self::$providerrequiresemailverification[$provider];
    }
    /**
     * Get list or provider ids.
     * @return int[]
     */
    public static function get_providers() {
        return self::$providers;
    }
    /**
     * Set backpacks provider.
     * @param int $provider
     * @return $this
     */
    public function set_provider($provider = self::BACKPACK_PROVIDER_MOZILLA) {
        if (in_array($provider, self::$providers)) {
            $this->provider = $provider;
        } else {
            throw new Exception("Invalid backpack provider.", $provider);
        }
        return $this;
    }
    /**
     * Get provider on the backpack.
     * @return int Provider as self::BACKPACK_PROVIDER_*
     */
    public function get_provider() {
        return !empty($this->provider) ? $this->provider : self::BACKPACK_PROVIDER_MOZILLA;
    }
    /**
     * Get assertion source
     * @return int
     */
    public function get_source() {
        return self::$backpackprovidersources[$this->provider];
    }
    /**
     * Get providers short name.
     * @see self::$providershortnames
     */
    public function get_providershortname() {
        $provider = $this->get_provider();
        return self::$providershortnames[$provider];
    }
    /**
     * Get short name matching provider id.
     * @param int $provider
     * @see self::$providershortnames
     */
    public static function get_providershortname_by_providerid($provider) {
        return self::$providershortnames[$provider];
    }
}
