<?php

require_once(__DIR__ . '/issuance.php');
require_once(__DIR__ . '/client.php');
require_once(__DIR__ . '/badge.php');
require_once(__DIR__ . '/collection.php');

/**
 * Description of assertion
 *
 * @author olli
 */
class obf_assertion extends obf_issuance {

    private $expires = null;
    private $id = null;

    public function badge_has_expired() {
        return (!empty($this->expires) && $this->expires < time());
    }

    public function has_expiration_date() {
        return !empty($this->expires);
    }

    public static function get_instance_by_id($id) {
        $client = obf_client::get_instance();
        $arr = $client->get_event($id);
        $obj = self::get_instance()
                ->set_emailbody($arr['email_body'])
                ->set_emailfooter($arr['email_footer'])
                ->set_emailsubject($arr['email_subject'])
                ->set_issuedon($arr['issued_on'])
                ->set_id($arr['id'])
                ->set_name($arr['name'])
                ->set_recipients($arr['recipient'])
                ->set_badge(obf_badge::get_instance($arr['badge_id'], $client));

        return $obj;
    }

    public function toArray() {
        return array(
            'badge' => $this->badge->toArray(),
            'issued_on' => $this->get_issuedon() == '' ? '-' : $this->get_issuedon());
    }

    /**
     *
     * @param obf_badge $badge
     * @return \obf_assertion_collection
     */
    public static function get_assertions(obf_badge $badge = null, $email = null, $limit = -1) {
        $badgeid = is_null($badge) ? null : $badge->get_id();
        $arr = obf_client::get_instance()->get_assertions($badgeid, $email);
        $assertions = array();
        $collection = new obf_badge_collection();

        foreach ($arr as $item) {
            $b = is_null($badge) ? $collection->get_badge($item['badge_id']) : $badge;
            $assertions[] = self::get_instance()
                    ->set_badge($b)
                    ->set_id($item['id'])
                    ->set_recipients($item['recipient'])
                    ->set_expires($item['expires'])
                    ->set_name($item['name'])
                    ->set_issuedon($item['issued_on']);
        }

        usort($assertions,
                function (obf_assertion $a1, obf_assertion $a2) {
            return $a1->get_issuedon() <= $a2->get_issuedon();
        });

        if ($limit > 0) {
            $assertions = array_slice($assertions, 0, $limit);
        }

        return new obf_assertion_collection($assertions);
    }

    public function equals(obf_assertion $another) {
        // PENDING: Is this comparison enough?
        return ($this->get_badge()->get_image() == $another->get_badge()->get_image());
    }

    /**
     *
     * @param obf_badge $badge
     * @return obf_assertion_collection
     */
    public static function get_badge_assertions(obf_badge $badge) {
        return self::get_assertions($badge);
    }

    public function get_expires() {
        return $this->expires;
    }

    public function set_expires($expires) {
        $this->expires = $expires;
        return $this;
    }

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

}

class obf_assertion_collection implements Countable, IteratorAggregate {

    /**
     * @var obf_assertion[]
     */
    private $assertions = array();

    /**
     * Assertion recipients mapped as Moodle users
     *
     * @var array
     */
    private $users = array();

    public function __construct(array $assertions = array()) {
        $this->assertions = $assertions;
    }

    public function add_assertion(obf_assertion $assertion) {
        $this->assertions[] = $assertion;
    }

    public function toArray() {
        $ret = array();

        foreach ($this->assertions as $assertion) {
            $ret[] = $assertion->toArray();
        }

        return $ret;
    }

    /**
     *
     * @param obf_assertion_collection $collection
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

    public function has_assertion(obf_assertion $assertion) {
        for ($i = 0; $i < count($this->assertions); $i++) {
            if ($this->get_assertion($i)->equals($assertion)) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * @param int $index
     * @return obf_assertion
     */
    public function get_assertion($index) {
        return $this->assertions[$index];
    }

    /**
     * Returns an array of Moodle-users that are related to selected assertion.
     *
     * @global type $DB
     * @param obf_assertion $assertion
     * @return type
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
