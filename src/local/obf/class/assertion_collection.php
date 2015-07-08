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
 * Collection of assertions.
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/assertion.php');
require_once(__DIR__ . '/backpack.php');
require_once(__DIR__ . '/blacklist.php');

/**
 * Represents a collection of events in OBF.
 *
 * @author olli
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_assertion_collection implements Countable, IteratorAggregate {

    /**
     * @var obf_assertion[] The assertions in this collection.
     */
    private $assertions = array();

    /**
     * Assertion recipients mapped as Moodle users
     *
     * @var array
     */
    private $users = array();

    /**
     * Class constructor.
     *
     * @param obf_assertion[] $assertions The assertions.
     */
    public function __construct(array $assertions = array()) {
        $this->assertions = $assertions;
    }

    /**
     * Adds an assertion to this collection.
     *
     * @param obf_assertion $assertion The assertion.
     */
    public function add_assertion(obf_assertion $assertion) {
        $this->assertions[] = $assertion;
    }

    /**
     * Returns an array representing this collection.
     *
     * @return array The array.
     */
    public function toArray() {
        $ret = array();

        foreach ($this->assertions as $assertion) {
            $ret[] = $assertion->toArray();
        }

        return $ret;
    }

    /**
     * Merges two collections.
     *
     * @param obf_assertion_collection $collection The other collection.
     */
    public function add_collection(obf_assertion_collection $collection) {
        for ($i = 0; $i < count($collection); $i++) {
            $assertion = $collection->get_assertion($i);

            // Skip duplicates.
            if (!$this->has_assertion($assertion)) {
                $this->add_assertion($assertion);
            }
        }
    }

    /**
     * Checks whether this collection contains $assertion.
     *
     * @param obf_assertion $assertion The assertion to search for.
     * @return boolean Returns true if found, false otherwise.
     */
    public function has_assertion(obf_assertion $assertion) {
        for ($i = 0; $i < count($this->assertions); $i++) {
            if ($this->get_assertion($i)->equals($assertion)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an assertion from index $index.
     *
     * @param int $index
     * @return obf_assertion
     */
    public function get_assertion($index) {
        if (!isset($this->assertions[$index])) {
            throw new Exception("Invalid array index.");
        }

        return $this->assertions[$index];
    }

    /**
     * Returns an array of Moodle-users that are related to selected assertion.
     *
     * @param obf_assertion $assertion The assertion.
     * @return stdClass[] An array of Moodle's user objects.
     */
    public function get_assertion_users(obf_assertion $assertion) {
        global $DB;

        if (count($this->users) === 0) {
            $emails = array();

            foreach ($this->assertions as $a) {
                $emails = array_merge($emails, $a->get_recipients());
            }

            $this->users = $DB->get_records_list('user', 'email', $emails);
        }

        $ret = array();

        // TODO: check number of SQL-queries performed in this loop.
        foreach ($assertion->get_recipients() as $recipient) {
            // Try to find the user by email.
            if (($user = $this->find_user_by_email($recipient)) !== false) {
                $ret[] = $user;
            } else {
                // ... and then try to find the user by backpack email.
                $backpack = obf_backpack::get_instance_by_backpack_email($recipient);
                $ret[] = $backpack === false ? $recipient : $DB->get_record('user',
                                array('id' => $backpack->get_user_id()));
            }
        }

        return $ret;
    }
    /**
     * Remove badges from collection, that match those defined in users blacklist.
     * @param obf_blacklist $blacklist Blacklist object used for filtering.
     * @return obf_assertion_collection $this
     */
    public function apply_blacklist(obf_blacklist $blacklist) {
        $badgeids = $blacklist->get_blacklist();
        $assertions = array();
        foreach ($this->assertions as $assertion) {
            if (!in_array($assertion->get_badge()->get_id(), $badgeids)) {
                $assertions[] = $assertion;
            }
        }
        $this->assertions = $assertions;
        return $this;
    }

    /**
     * Tries to find the Moodle user by email from collection's cache.
     *
     * @param string $email The email of the user.
     * @return stdClass|boolean Returns the user object if found, false
     *      otherwise.
     */
    private function find_user_by_email($email) {
        foreach ($this->users as $user) {
            if ($user->email == $email) {
                return $user;
            }
        }

        return false;
    }

    /**
     * Get count as assertions.
     * @return int Assertion count
     */
    public function count() {
        return count($this->assertions);
    }

    /**
     * Get iterator for assertions.
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->assertions);
    }
}
