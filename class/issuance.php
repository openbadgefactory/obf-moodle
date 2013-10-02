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
    
    public static function get_badge_assertions(obf_badge $badge) {
        $arr = obf_client::get_instance()->get_assertions($badge->get_id());
        $assertions = array();
        
        foreach ($arr as $item) {
            // Clone the original badge, because the same badge can have
            // different expiration dates.
            $b = clone $badge;
            $b->set_expires($item['expires']);
            $assertions[] = self::get_instance()
                    ->set_badge($b)
                    ->set_recipients($item['recipient'])
                    ->set_issuedon($item['issued_on']);
            
        }
        
        return $assertions;
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

?>
