<?php
require_once __DIR__ . '/assertion.php';
require_once __DIR__ . '/assertion_collection.php';

/**
 * Class for handling the communication between the plugin and Mozilla Backpack.
 */
class obf_backpack {

    const BACKPACK_URL = 'http://beta.openbadges.org/displayer/';
    const OPB_URL = 'https://openbadgepassport.com/displayer/';
    const PERSONA_VERIFIER_URL = 'https://verifier.login.persona.org/verify';

    const BACKPACK_PROVIDER_MOZILLA = 0;
    const BACKPACK_PROVIDER_OBP = 1;

    private $id = -1;
    private $user_id = -1;
    private $email = '';
    private $backpack_id = -1;
    private $groups = array();
    private $transport = null;
    private $provider = 0;

    private static $providers = array(self::BACKPACK_PROVIDER_MOZILLA,
            self::BACKPACK_PROVIDER_OBP);
    private static $providershortnames = array(
        self::BACKPACK_PROVIDER_MOZILLA => 'moz',
        self::BACKPACK_PROVIDER_OBP => 'obp'
    );
    private static $apiurls = array(
        self::BACKPACK_PROVIDER_MOZILLA => self::BACKPACK_URL,
        self::BACKPACK_PROVIDER_OBP => self::OPB_URL
    );

    public function __construct($transport = null, $provider = self::BACKPACK_PROVIDER_MOZILLA) {
        $this->set_provider($provider);
        if (!is_null($transport)) {
            $this->set_transport($transport);
        }
    }

    private function get_apiurl() {
        if (!empty($this->provider)) {
            return self::$apiurls[$this->provider];
        }
        return self::BACKPACK_URL;
    }

    /**
     *
     * @param type $user
     */
    public static function get_instance($user, $provider = self::BACKPACK_PROVIDER_MOZILLA) {
        return self::get_instance_by_fields(array('user_id' => $user->id), $provider);
    }

    /**
     *
     * @global moodle_database $DB
     * @param array $fields
     * @return \self|boolean
     */
    protected static function get_instance_by_fields(array $fields, $provider = self::BACKPACK_PROVIDER_MOZILLA) {
        global $DB;
        $fields = array_merge($fields, array('backpack_provider' => $provider));
        $backpackobj = $DB->get_record('obf_backpack_emails', $fields, '*',
                IGNORE_MULTIPLE);

        if ($backpackobj === false) {
            return false;
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
     *
     * @param type $email
     */
    public static function get_instance_by_backpack_email($email, $provider = self::BACKPACK_PROVIDER_MOZILLA) {
        return self::get_instance_by_fields(array('email' => $email), $provider);
    }

    /**
     *
     * @param type $userid
     * @return type
     */
    public static function get_instance_by_userid($userid, moodle_database $db, $provider = self::BACKPACK_PROVIDER_MOZILLA) {
        return self::get_instance($db->get_record('user', array('id' => $userid)), $provider);
    }

    /**
     * Returns an array of backpack email addresses matching the user ids found from $userids
     *
     * @global moodle_database $DB
     * @param array $userids
     * @return String[] An array of backpack emails
     */
    public static function get_emails_by_userids(array $userids) {
        global $DB;

        $records = $DB->get_records_list('obf_backpack_emails', 'user_id',
                $userids, '', 'id,user_id,email');

        $ret = array();

        foreach ($records as $record) {
            $ret[$record->user_id] = $record->email;
        }

        return $ret;
    }

    /**
     *
     * @global moodle_database $DB
     * @return type
     */
    public static function get_user_ids_with_backpack($provider = null) {
        global $DB;

        $ret = array();
        if (is_null($provider)) {
            $records = $DB->get_records_select('obf_backpack_emails',
                    'backpack_id > 0');
        } else {
            $records = $DB->get_records_sql('SELECT * FROM {obf_backpack_emails} ' .
                    'WHERE backpack_id > 0 AND backpack_provider = :provider', array('provider' => $provider));
        }


        foreach ($records as $record) {
            $ret[] = $record->user_id;
        }

        return $ret;
    }

    /**
     *
     * @param type $email
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
     * @global type $CFG
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
     *
     */
    public function disconnect() {
        $this->delete();
    }

    /**
     *
     * @param type $email
     * @throws Exception
     */
    public function connect($email) {
        $this->set_email($email);
        $backpackid = $this->connect_to_backpack($email);

        if ($backpackid !== false) {
            $this->set_backpack_id($backpackid);
            $this->save();
        }
        else {
            $this->disconnect();
            throw new Exception(get_string('backpackemailnotfound', 'local_obf',
                    s($email)));
        }
    }

    public function get_groups() {
        $curl = $this->get_transport();
        $output = $curl->get($this->get_apiurl() . $this->get_backpack_id() . '/groups.json');
        $json = json_decode($output);

        return $json->groups;
    }

    public function get_group_assertions($groupid, $limit = -1) {
        if ($this->backpack_id < 0) {
            throw new Exception('Backpack connection isn\'t set.');
        }

        $curl = $this->get_transport();
        $output = $curl->get($this->get_apiurl() . $this->get_backpack_id() . '/group/' . $groupid . '.json');
        $json = json_decode($output);
        $assertions = new obf_assertion_collection();

        foreach ($json->badges as $item) {
            $assertion = new obf_assertion();
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
     *
     * @param type $limit
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

    public function get_assertions_as_array($limit = -1) {
        $assertions = $this->get_assertions($limit);
        $ret = array();

        foreach ($assertions as $assertion) {
            $ret[] = $assertion->toArray();
        }

        return $ret;
    }

    /**
     *
     * @global moodle_database $DB
     */
    public function save() {
        global $DB;

        $obj = new stdClass();
        $obj->user_id = $this->user_id;
        $obj->email = $this->email;
        $obj->backpack_id = $this->backpack_id;
        $obj->backpack_provider = $this->provider;
        $obj->groups = serialize($this->groups);

        if ($this->id > 0) {
            $obj->id = $this->id;
            $DB->update_record('obf_backpack_emails', $obj);
        }
        else {
            $id = $DB->insert_record('obf_backpack_emails', $obj);
            $this->set_id($id);
        }
    }

    public function delete() {
        global $DB;

        if ($this->id > 0) {
            $DB->delete_records('obf_backpack_emails', array('id' => $this->id));
        }
    }

    public function is_connected() {
        return $this->backpack_id > 0;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_user_id() {
        return $this->user_id;
    }

    public function get_email() {
        return $this->email;
    }

    public function get_backpack_id() {
        return $this->backpack_id;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function set_user_id($user_id) {
        $this->user_id = $user_id;
        return $this;
    }

    public function set_email($email) {
        $this->email = $email;
        return $this;
    }

    public function set_backpack_id($backpack_id) {
        $this->backpack_id = $backpack_id;
        return $this;
    }

    public function get_group_ids() {
        return $this->groups;
    }

    public function set_groups($groups) {
        $this->groups = is_array($groups) ? $groups : array();
        return $this;
    }

    public function set_transport($transport) {
        $this->transport = $transport;
    }
    public function exists() {
        return !empty($this->id) && $this->id > 0;
    }
    public static function get_providers() {
        return self::$providers;
    }
    public function set_provider($provider = self::BACKPACK_PROVIDER_MOZILLA) {
        if (in_array($provider, self::$providers)) {
            $this->provider = $provider;
        } else {
            throw new Exception("Invalid backpack provider.", $provider);
        }
    }
    public function get_provider() {
        return !empty($this->provider) ? $this->provider : self::BACKPACK_PROVIDER_MOZILLA;
    }
    public function get_providershortname() {
        $provider = $this->get_provider();
        return self::$providershortnames[$provider];
    }
    public static function get_providershortname_by_providerid($provider) {
        return self::$providershortnames[$provider];
    }
}
