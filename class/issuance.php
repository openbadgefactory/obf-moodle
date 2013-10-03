<?php

/**
 * Description of obf_issuance
 *
 * @author olli
 */
class obf_issuance {

    /**
     *
     * @var obf_badge
     */
    private $badge = null;
    private $emailsubject = '';
    private $emailfooter = '';
    private $emailbody = '';
    private $issuedon = null;
    private $recipients = array();
    private $error = '';

    /**
     * 
     * @return obf_issuance
     */
    public static function get_instance() {
        return new self();
    }

    public function process() {
        try {
            $this->badge->issue($this->recipients, $this->issuedon, $this->emailsubject, $this->emailbody, $this->emailfooter);
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
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
            // Clone the original badge, because the same badge can have
            // different expiration dates.
            $b = is_null($badge) ? obf_badge::get_instance($item['badge_id']) : clone $badge;
            $b->set_expires($item['expires']);
            $assertions[] = self::get_instance()
                    ->set_badge($b)
                    ->set_recipients($item['recipient'])
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

    public function get_error() {
        return $this->error;
    }

    public function get_badge() {
        return $this->badge;
    }

    public function set_badge($badge) {
        $this->badge = $badge;
        return $this;
    }

    public function get_emailsubject() {
        return $this->emailsubject;
    }

    public function set_emailsubject($emailsubject) {
        $this->emailsubject = $emailsubject;
        return $this;
    }

    public function get_emailfooter() {
        return $this->emailfooter;
    }

    public function set_emailfooter($emailfooter) {
        $this->emailfooter = $emailfooter;
        return $this;
    }

    public function get_emailbody() {
        return $this->emailbody;
    }

    public function set_emailbody($emailbody) {
        $this->emailbody = $emailbody;
        return $this;
    }

    public function get_issuedon() {
        return $this->issuedon;
    }

    public function set_issuedon($issuedon) {
        $this->issuedon = $issuedon;
        return $this;
    }

    public function get_recipients() {
        return $this->recipients;
    }

    public function set_recipients($recipients) {
        $this->recipients = $recipients;
        return $this;
    }

}

class obf_assertion_collection implements Countable {
    /**
     * @var obf_issuance[]
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
    
    public function add_assertion(obf_issuance $assertion) {
        $this->assertions[] = $assertion;
    }
    
    /**
     * 
     * @param int $index
     * @return obf_issuance
     */
    public function get_assertion($index) {
        return $this->assertions[$index];
    }
    
    public function get_assertion_users(obf_issuance $assertion) {
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
