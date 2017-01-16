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
 * User preferences.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * User preferences -class.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_user_preferences {
    /**
     * @var $userid User id of the user preferences belong to.
     */
    private $userid;

    /**
     * @var $indb True if the preferences exist in the database.
     */
    private $indb = true;
    /**
     * @var $defaults Some defaults for when nothing in the database for user.
     */
    private static $defaults = array('badgesonprofile' => 1);
    /**
     * @var $preferences The preferences
     */
    private $preferences = null;
    /**
     * @var $requiredpreferences What must be defined in preferences.
     */
    private $requiredpreferences = array('badgesonprofile');
    /**
     * @var $optionalpreferences What may be defined in preferences.
     */
    private $optionalpreferences = array('openbadgepassport');
    
    const USERS_CAN_MANAGE_DISPLAY_OF_BADGES = 0;
    const USERS_FORCED_TO_DISPLAY_BADGES = 1;
    const USERS_NOT_ALLOWED_TO_DISPLAY_BADGES = 2;

    /**
     * Constructor.
     * @param int $userid
     */
    public function __construct($userid) {
        $this->userid = $userid;
        $this->get_preferences();
    }

    /**
     * Get a single preference for $userid
     * @param int $userid
     * @param string $preference
     * @return mixed Preference to be used.
     */
    public static function get_user_preference($userid, $preference) {
        global $DB;
        $record = $DB->get_record('local_obf_user_preferences', array('user_id' => $userid,
                'name' => $preference));
        if ($record) {
            return $record->value;
        }
        return self::get_default($preference);
    }
    /**
     * Get a single preference.
     * @param string $preference
     * @return mixed Preference to be used.
     */
    public function get_preference($preference) {
        $this->get_preferences();
        if (!is_null($this->preferences) && array_key_exists($preference, $this->preferences)) {
            return $this->preferences[$preference];
        }
        return self::get_default($preference);
    }
    /**
     * Get the default preference.
     * @param string $preference
     * @return mixed Preference to be used.
     */
    public static function get_default($preference) {
        if (array_key_exists($preference, self::$defaults)) {
            return self::$defaults[$preference];
        }
        return null;
    }

    /**
     * Get user id.
     *
     * @return int
     */
    public function get_userid() {
        return $this->userid;
    }
    /**
     * Does the object exist in the database?
     * @return bool True if saved to the database.
     */
    public function exists() {
        return $this->indb;
    }
    /**
     * Get all preferences.
     * @param bool $force Force loading of preferences from the database.
     * @return array The preferences.
     */
    public function get_preferences($force = false) {
        global $DB;
        $preferences = array();
        if (!is_null($this->preferences) && !$force) {
            return $this->preferences;
        }
        if (!$this->exists()) {
            $this->preferences = array();
            return $this->preferences;
        }
        $records = $DB->get_records('local_obf_user_preferences', array('user_id' => $this->userid));
        if ($records) {
            $this->indb = true;
            foreach ($records as $record) {
                $preferences[$record->name] = $record->value;
            }
        } else {
            $this->indb = false;
        }
        $this->preferences = $preferences;
        return $this->preferences;
    }
    /**
     * Set a user pref.
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function set_preference($name, $value) {
        $this->preferences[$name] = $value;
        return $this;
    }
    /**
     * Add an array of preferences to existing preferences.
     *
     * @param array $data
     */
    public function add_preferences($data) {
        $prefs = $this->get_preferences();
        $prefs = array_merge($prefs, (array)$data);
        return $this->save_preferences($prefs);
    }
    /**
     * Save.
     * @see self::save_preferences
     */
    public function save() {
        $prefs = $this->get_preferences();
        $this->save_preferences($prefs);
    }
    /**
     * Save params. (activity selections and completedby dates)
     * @param array|stdClass $data
     */
    public function save_preferences($data) {
        global $DB;
        $preferences = (array)$data;

        $match = array_merge($this->optionalpreferences, $this->requiredpreferences);
        $regex = implode('|', array_map(
                function($a) {
                    return $a;
                }, $match));
        $requiredkeys = preg_grep('/^('.$regex.')$/', array_keys($preferences));

        $preftable = 'local_obf_user_preferences';

        $existing = $DB->get_fieldset_select($preftable, 'name', 'user_id = ?', array($this->userid));
        $todelete = array_diff($existing, $requiredkeys);
        $todelete = array_unique($todelete);
        if (!empty($todelete)) {
            list($insql, $inparams) = $DB->get_in_or_equal($todelete, SQL_PARAMS_NAMED, 'cname', true);
            $inparams = array_merge($inparams, array('userid' => $this->userid));
            $DB->delete_records_select($preftable, 'user_id = :userid AND name '.$insql, $inparams );
        }
        foreach ($requiredkeys as $key) {
            if (in_array($key, $existing)) {
                $toupdate = $DB->get_record($preftable,
                        array('user_id' => $this->userid,
                                'name' => $key) );
                $toupdate->value = $preferences[$key];
                $DB->update_record($preftable, $toupdate, true);
                $this->set_preference($key, $preferences[$key]);
            } else {
                $obj = new stdClass();
                $obj->user_id = $this->userid;
                $obj->name = $key;
                $obj->value = $preferences[$key];
                $DB->insert_record($preftable, $obj);
                $this->set_preference($key, $preferences[$key]);
            }
        }
    }

}
