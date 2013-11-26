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
    protected $badge = null;
    protected $emailsubject = '';
    protected $emailfooter = '';
    protected $emailbody = '';
    protected $issuedon = null;
    protected $recipients = array();
    protected $error = '';
    protected $name = '';

    /**
     *
     * @return obf_issuance
     */
    public static function get_instance() {
        return new static();
    }

    public function process() {
        try {
            $this->badge->issue($this->recipients, $this->issuedon, $this->emailsubject,
                    $this->emailbody, $this->emailfooter);
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
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

    public function get_name() {
        return $this->name;
    }

    public function set_name($name) {
        $this->name = $name;
        return $this;
    }


}