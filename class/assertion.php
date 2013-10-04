<?php

/**
 * Description of assertion
 *
 * @author olli
 */
class obf_assertion extends obf_issuance {

    private $expires = null;
    
    public function badge_has_expired() {
        return (!empty($this->expires) && $this->expires < time());
    }

    public function has_expiration_date() {
        return !empty($this->expires);
    }
    
    /**
     * 
     * @param obf_badge $badge
     * @return \obf_assertion_collection
     */
    public static function get_assertions(obf_badge $badge = null) {
        $badgeid = is_null($badge) ? null : $badge->get_id();
        $arr = obf_client::get_instance()->get_assertions($badgeid);
        $assertions = array();

        foreach ($arr as $item) {
            $b = is_null($badge) ? obf_badge::get_instance($item['badge_id']) : $badge;
            $assertions[] = self::get_instance()
                    ->set_badge($b)
                    ->set_recipients($item['recipient'])
                    ->set_expires($item['expires'])
                    ->set_issuedon($item['issued_on']);
        }

        return new obf_assertion_collection($assertions);
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


    
}

class obf_assertion_collection implements Countable {

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

    /**
     * 
     * @param int $index
     * @return obf_assertion
     */
    public function get_assertion($index) {
        return $this->assertions[$index];
    }

    public function get_assertion_users(obf_assertion $assertion) {
        if (count($this->users) === 0) {
            global $DB;
            $emails = array();

            foreach ($this->assertions as $a) {
                $emails = array_merge($emails, $a->get_recipients());
            }

            $this->users = $DB->get_records_list('user', 'email', $emails);
        }

        $ret = array();

        foreach ($this->users as $user) {
            if (in_array($user->email, $assertion->get_recipients())) {
                $ret[] = $user;
            }
        }

        return $ret;
    }

    public function count() {
        return count($this->assertions);
    }

}

?>
