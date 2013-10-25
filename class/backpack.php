<?php

class obf_backpack {

    const BACKPACK_URL = 'http://beta.openbadges.org/displayer/';

    private $id = -1;
    private $user_id = -1;
    private $email = '';
    private $backpack_id = -1;
    private $group_id = -1;

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
                $obj->set_backpack_id($json->userId);
                $obj->set_email($user->email);
                $obj->save();
            }
        } else {
            $obj->set_backpack_id($backpackobj->backpack_id);
            $obj->set_email($backpackobj->email);
            $obj->set_id($backpackobj->id);
            $obj->set_group_id($backpackobj->group_id);
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

    public function connect($email) {
        $this->set_email($email);
        $backpackid = self::connect_to_backpack($email);

        if ($backpackid !== false) {
            $this->set_backpack_id($backpackid);
        }
        else {
            $this->set_backpack_id(-1);
            $this->set_group_id(-1);
        }

        // Save the backpack settings in any case
        $this->save();
    }

    public function get_groups() {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $curl = new curl();
        $output = $curl->get(self::BACKPACK_URL . $this->get_backpack_id() . '/groups.json');
        $json = json_decode($output);

        return $json->groups;
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
        $obj->group_id = $this->group_id;

        if ($this->id > 0) {
            $obj->id = $this->id;
            $DB->update_record('obf_backpack_emails', $obj);
        }
        else {
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
    public function get_group_id() {
        return $this->group_id;
    }

    public function set_group_id($group_id) {
        $this->group_id = $group_id;
        return $this;
    }


}
