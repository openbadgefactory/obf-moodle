<?php

require_once(__DIR__ . '/assertion.php');

class obf_backpack {

    const BACKPACK_URL = 'http://beta.openbadges.org/displayer/';

    private $id = -1;
    private $user_id = -1;
    private $email = '';
    private $backpack_id = -1;
    private $groups = array();

    /**
     *
     * @global moodle_database $DB
     * @param type $user
     */
    public static function get_instance($user) {
        global $DB;

        $backpackobj = $DB->get_record('obf_backpack_emails', array('user_id' => $user->id));
        $obj = new self();
        $obj->set_user_id($user->id);

        // No backpack data found from the database, let's try connection with the default email
        // address of the user
        if ($backpackobj === false) {
            $backpackid = self::connect_to_backpack($user->email);

            // Matching email found from the backpack service
            if ($backpackid !== false) {
                $obj->set_backpack_id($backpackid);
                $obj->set_email($user->email);
                $obj->save();
            }
        } else {
            $obj->set_backpack_id($backpackobj->backpack_id);
            $obj->set_email($backpackobj->email);
            $obj->set_id($backpackobj->id);
            $obj->set_groups(unserialize($backpackobj->groups));
        }

        return $obj;
    }

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

    public function disconnect() {
        $this->set_backpack_id(-1);
        $this->set_groups(array());
        $this->save();
    }

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
            $assertion->set_badge($badge);

            $assertions->add_assertion($assertion);

            if ($limit > 0 && count($assertions) == $limit) {
                break;
            }
        }

        return $assertions;
    }

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
