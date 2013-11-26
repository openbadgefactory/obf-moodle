<?php

require_once(__DIR__ . '/assertion.php');

class obf_backpack {

    const BACKPACK_URL = 'http://beta.openbadges.org/displayer/';
    const PERSONA_VERIFIER_URL = 'https://verifier.login.persona.org/verify';

    private $id = -1;
    private $user_id = -1;
    private $email = '';
    private $backpack_id = -1;
    private $groups = array();

    /**
     *
     * @param type $user
     */
    public static function get_instance($user) {
        return self::get_instance_by_fields(array('user_id' => $user->id));
    }

    /**
     *
     * @global moodle_database $DB
     * @param array $fields
     * @return \self|boolean
     */
    protected static function get_instance_by_fields(array $fields) {
        global $DB;

        $backpackobj = $DB->get_record('obf_backpack_emails', $fields, '*', IGNORE_MULTIPLE);

        if ($backpackobj === false) {
            return false;
        }

        $obj = new self();
        $obj->set_user_id($backpackobj->user_id);
        $obj->set_backpack_id($backpackobj->backpack_id);
        $obj->set_email($backpackobj->email);
        $obj->set_id($backpackobj->id);
        $obj->set_groups(unserialize($backpackobj->groups));

        return $obj;
    }

    /**
     *
     * @param type $email
     */
    public static function get_instance_by_backpack_email($email) {
        return self::get_instance_by_fields(array('email' => $email));
    }

    /**
     *
     * @global moodle_database $DB
     * @param type $userid
     * @return type
     */
    public static function get_instance_by_userid($userid) {
        global $DB;
        return self::get_instance($DB->get_record('user', array('id' => $userid)));
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

        $records = $DB->get_records_list('obf_backpack_emails', 'user_id', $userids, '',
                'user_id,email');
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
    public static function get_user_ids_with_backpack() {
        global $DB;

        $ret = array();
        $records = $DB->get_records_select('obf_backpack_emails', 'backpack_id > 0');

        foreach ($records as $record) {
            $ret[] = $record->user_id;
        }

        return $ret;
    }

    /**
     *
     * @global type $CFG
     * @param type $email
     * @return boolean
     */
    private static function connect_to_backpack($email) {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $curl = new curl();
        $output = $curl->post(self::BACKPACK_URL . 'convert/email', array('email' => $email));
        $json = json_decode($output);
        $code = $curl->info['http_code'];

        if ($code == 200) {
            return $json->userId;
        }

        return false;
    }

    public function verify($assertion) {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $urlparts = parse_url($CFG->wwwroot);
        $port = isset($urlparts['port']) ? $urlparts['port'] : 80;
        $url = $urlparts['scheme'] . '://' . $urlparts['host'] . ':' . $port; // . $urlparts['path'];
        $params = array('assertion' => $assertion, 'audience' => $url);
        $curl = new curl();

        $curl->setHeader('Content-Type: application/json');
        $output = $curl->post(self::PERSONA_VERIFIER_URL, json_encode($params));
        $ret = json_decode($output);

        if ($ret->status == 'failure') {
            return false;
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
        $backpackid = self::connect_to_backpack($email);

        if ($backpackid !== false) {
            $this->set_backpack_id($backpackid);
            $this->save();
        } else {
            $this->disconnect();
            throw new Exception(get_string('backpackemailnotfound', 'local_obf', s($email)));
        }
    }

    public function get_groups() {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $curl = new curl();
        $output = $curl->get(self::BACKPACK_URL . $this->get_backpack_id() . '/groups.json');
        $json = json_decode($output);

        return $json->groups;
    }

    public function get_group_assertions($groupid, $limit = -1) {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        if ($this->backpack_id < 0) {
            throw new Exception('Backpack connection isn\'t set.');
        }

        $curl = new curl();
        $output = $curl->get(self::BACKPACK_URL . $this->get_backpack_id() . '/group/' . $groupid . '.json');
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
        $obj->groups = serialize($this->groups);

        if ($this->id > 0) {
            $obj->id = $this->id;
            $DB->update_record('obf_backpack_emails', $obj);
        } else {
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

}
