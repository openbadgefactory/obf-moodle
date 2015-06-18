<?php

class obf_blacklist {
    private $userid;

    private $indb = true;
    private $blacklistedbadges = null;

    public function __construct($userid) {
        $this->userid = $userid;
        $this->get_blacklist();
    }

    public function get_userid() {
        return $this->userid;
    }

    public function exists() {
        return $this->indb;
    }
    public function get_blacklist($force = false) {
        global $DB;
        $blacklistedbadges = array();
        if (!is_null($this->blacklistedbadges) && !$force) {
            return $this->blacklistedbadges;
        }
        if (!$this->exists()) {
            $this->blacklistedbadges = array();
            return $this->blacklistedbadges;
        }
        $records = $DB->get_records('obf_user_badge_blacklist', array('user_id' => $this->userid));
        if ($records) {
            $this->indb = true;
            foreach ($records as $record) {
                $blacklistedbadges[] = $record->badge_id;
            }
        } else {
            $this->indb = false;
        }
        $this->blacklistedbadges = $blacklistedbadges;
        return $this->blacklistedbadges;
    }
    public function add_to_blacklist($badgeid) {
        if (!in_array($badgeid, $this->blacklistedbadges)) {
            $this->blacklistedbadges[] = $badgeid;
        }
        return $this;
    }
    public function remove_from_blacklist($badgeid) {
        $key = array_search($badgeid, $this->blacklistedbadges);
        if ($key !== false) {
            unset($this->blacklistedbadges[$key]);
        }
        return $this;
    }
    /**
     * Save blacklist
     * @param type $data
     */
    public function save($newblacklist = null) {
        global $DB;
        if (is_null($newblacklist)) {
            $newblacklist = $this->get_blacklist();
        }
        $newblacklist = (array)$newblacklist;
        // Filter out empty params
        $requiredkeys = array_values($newblacklist);

        $preftable = 'obf_user_badge_blacklist';

        $existing = $DB->get_fieldset_select($preftable, 'badge_id', 'user_id = ?', array($this->userid));
        $todelete = array_diff($existing, $requiredkeys);
        $todelete = array_unique($todelete);
        if (!empty($todelete)) {
            list($insql,$inparams) = $DB->get_in_or_equal($todelete, SQL_PARAMS_NAMED, 'cname', true);
            $inparams = array_merge($inparams, array('userid' => $this->userid));
            $DB->delete_records_select($preftable, 'user_id = :userid AND badge_id '.$insql, $inparams );
        }
        foreach ($requiredkeys as $key) {
            if (!in_array($key, $existing)) {
                $obj = new stdClass();
                $obj->user_id = $this->userid;
                $obj->badge_id = $key;
                $DB->insert_record($preftable, $obj);
                $this->add_to_blacklist($key);
            }
        }
    }
}
