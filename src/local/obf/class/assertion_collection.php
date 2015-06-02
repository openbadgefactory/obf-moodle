<?php
require_once __DIR__ . '/assertion.php';
require_once __DIR__ . '/backpack.php';

/**
 * Represents a collection of events in OBF.
 *
 * @author olli
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
     * @global type $DB
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

        // TODO: check number of SQL-queries performed in this loop
        foreach ($assertion->get_recipients() as $recipient) {
            // try to find the user by email
            if (($user = $this->find_user_by_email($recipient)) !== false) {
                $ret[] = $user;
            }

            // ... and then try to find the user by backpack email
            else {
                $backpack = obf_backpack::get_instance_by_backpack_email($recipient);
                $ret[] = $backpack === false ? $recipient : $DB->get_record('user',
                                array('id' => $backpack->get_user_id()));
            }
        }

        return $ret;
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

    public function count() {
        return count($this->assertions);
    }

    public function getIterator() {
        return new ArrayIterator($this->assertions);
    }
}
