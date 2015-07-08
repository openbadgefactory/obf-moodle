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
 * Blacklist.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Blacklist of badges user wishes to hide.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_blacklist {
    /**
     * @var User id
     */
    private $userid;

    /**
     * @var Blacklist saved to the database?
     */
    private $indb = true;
    /**
     * @var array of badges user has blacklisted.
     */
    private $blacklistedbadges = null;

    /**
     * Constructor
     * @param int $userid
     */
    public function __construct($userid) {
        $this->userid = $userid;
        $this->get_blacklist();
    }
    /**
     * Get the user id of the user the backpack belongs to.
     * @return int
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * Is the blacklist saved to the database?
     */
    public function exists() {
        return $this->indb;
    }
    /**
     * Get badge ids the user has blacklisted.
     *
     * @param bool $force
     * @return string[]
     */
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
        $records = $DB->get_records('local_obf_badge_blacklists', array('user_id' => $this->userid));
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
    /**
     * Add a badge id to blacklisted badges.
     * @param string $badgeid
     * @return $this
     */
    public function add_to_blacklist($badgeid) {
        if (!in_array($badgeid, $this->blacklistedbadges)) {
            $this->blacklistedbadges[] = $badgeid;
        }
        return $this;
    }
    /**
     * Remove a badge id from blacklisted badges.
     * @param string $badgeid
     * @return $this
     */
    public function remove_from_blacklist($badgeid) {
        $key = array_search($badgeid, $this->blacklistedbadges);
        if ($key !== false) {
            unset($this->blacklistedbadges[$key]);
        }
        return $this;
    }
    /**
     * Save blacklist
     * @param stdClass|array $newblacklist
     */
    public function save($newblacklist = null) {
        global $DB;
        if (is_null($newblacklist)) {
            $newblacklist = $this->get_blacklist();
        }
        $newblacklist = (array)$newblacklist;
        // Filter out empty params.
        $requiredkeys = array_values($newblacklist);

        $preftable = 'local_obf_badge_blacklists';

        $existing = $DB->get_fieldset_select($preftable, 'badge_id', 'user_id = ?', array($this->userid));
        $todelete = array_diff($existing, $requiredkeys);
        $todelete = array_unique($todelete);
        if (!empty($todelete)) {
            list($insql, $inparams) = $DB->get_in_or_equal($todelete, SQL_PARAMS_NAMED, 'cname', true);
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
