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
 * Assertion.
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/client.php');
require_once(__DIR__ . '/badge.php');
require_once(__DIR__ . '/collection.php');
require_once(__DIR__ . '/assertion_collection.php');

/**
 * Represents a single event in OBF.
 *
 * @author olli
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_assertion {

    /**
     * @var obf_badge The related badge.
     */
    private $badge = null;

    /**
     * @var string The email subject.
     */
    private $emailsubject = '';

    /**
     * @var string The bottom part of the email message.
     */
    private $emailfooter = '';

    /**
     * @var string The top part of the email message.
     */
    private $emailbody = '';
    
    /**
     *
     * @var obf_email The email
     */
    private $emailtemplate = null;

    /**
     * @var int When the badge was issued, Unix timestamp.
     */
    private $issuedon = null;

    /**
     * @var array
     */
    private $log_entry = array();

    /**
     * @var string[] An array of recipient emails.
     */
    private $recipients = array();

    /**
     * @var array[] An array of recipient emails and revokation timestamps.
     */
    private $revoked = array();

    /**
     * @var string Possible error message.
     */
    private $error = '';

    /**
     * @var string The name of the event.
     */
    private $name = '';

    /**
     * @var int The expiration date as an Unix timestamp.
     */
    private $expires = null;

    /**
     * @var string The id of the event.
     */
    private $id = null;
    
    /**
     * @var string The criteria addendum
     */
    private $criteriaaddendum = '';


    /**
     * @var Assertion source is unknown.
     */
    const ASSERTION_SOURCE_UNKNOWN = 0;
    /**
     * @var Assertion source is Open Badge Factory.
     */
    const ASSERTION_SOURCE_OBF = -1;
    /**
     * @var Assertion source is Open Badge Passport.
     */
    const ASSERTION_SOURCE_OBP = 2;
    /**
     * @var Assertion source is Mozilla Backpack.
     */
    const ASSERTION_SOURCE_MOZILLA = 3;
    /**
     * @var Assertion source is Moodle Badges issued before installing OBF plugin
     */
    const ASSERTION_SOURCE_MOODLE = -2;

    /**
     * @var int Source where assertion came was retrieved (OBF, OPB, Backpack, other? or unknown)
     */
    private $source = self::ASSERTION_SOURCE_UNKNOWN;

    /**
     * Returns an empty instance of this class.
     *
     * @return obf_assertion
     */
    public static function get_instance() {
        return new self();
    }

    /**
     * Issues the badge.
     *
     * @return mixed Eventid(string) when event id was successfully parsed from response,
     *         true on otherwise successful operation, false otherwise.
     */
    public function process() {
        try {
            $eventid = $this->badge->issue($this->recipients, $this->issuedon,
                    $this->get_email_template(), $this->get_criteria_addendum());
            return $eventid;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
    /**
     * Revoke this assertion for a list of emails.
     *
     * @param obf_client $client
     * @param string[] $emails
     * @return True on success, false otherwise.
     */
    public function revoke(obf_client $client, $emails = array()) {
        try {
            $client->revoke_event($this->get_id(), $emails);
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return false;
    }
    /**
     * Send a message to users, that their badge assertion has been revoked.
     * @param stdClass[] $users
     * @param stdclass $revoker
     */
    public function send_revoke_message($users, $revoker) {
        global $CFG;
        require_once($CFG->dirroot . '/message/lib.php');
        foreach ($users as $userto) {
            if (preg_match('/^2.9/', $CFG->release)) {
                $message = new \core\message\message();
            } else {
                $message = new stdClass();
            }
            $badge = $this->get_badge();
            $messageparams = new stdClass();
            $messageparams->revokername = fullname($revoker);
            $messageparams->revokedbadgename = $badge->get_name();

            $message->component = 'local_obf';
            $message->name = 'revoked';
            $message->userfrom = $revoker;
            $message->userto = $userto;
            $message->subject = get_string('emailbadgerevokedsubject', 'local_obf', $messageparams);
            $message->fullmessage = get_string('emailbadgerevokedbody', 'local_obf', $messageparams);
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = '';
            $message->smallmessage = '';
            message_send($message);
        }
    }

    /**
     * Gets and returns the assertion instance from OBF.
     *
     * @param string $id The id of the event.
     * @param obf_client $client The client instance.
     * @return obf_assertion The assertion instance.
     */
    public static function get_instance_by_id($id, obf_client $client) {
        $arr = $client->get_event($id);
        $obj = self::get_instance()->set_emailbody($arr['email_body']);
        $obj->set_emailfooter($arr['email_footer'])->set_emailsubject($arr['email_subject']);
        if (isset($arr['email_link_text'])) {
            $obj->get_email_template()->set_link_text($arr['email_link_text']);
        }
        
        $obj->set_issuedon($arr['issued_on'])->set_id($arr['id'])->set_name($arr['name']);
        $obj->set_recipients($arr['recipient'])->set_badge(obf_badge::get_instance($arr['badge_id'], $client));
        $obj->set_source(self::ASSERTION_SOURCE_OBF);
        if (array_key_exists('revoked', $arr)) {
            $obj->set_revoked($arr['revoked']);
        }

        return $obj;
    }

    /**
     * Returns this instance as an associative array.
     *
     * @return array The array.
     */
    public function toArray() {
        $badgearr = $this->badge instanceof obf_badge ? $this->badge->toArray() : array();

        return array(
            'badge' => $badgearr,
            'issued_on' => $this->get_issuedon() == '' ? '-' : $this->get_issuedon(),
            'expires' => $this->get_expires() == '' ? '-' : $this->get_expires(),
            'source' => $this->get_source());
    }

    /**
     * Returns all assertions matching the search criteria.
     *
     * @param obf_client $client The client instance.
     * @param obf_badge $badge Get only the assertions containing this badge.
     * @param string $email Get only the assertions related to this email.
     * @param int $limit Limit the amount of results.
     * @return \obf_assertion_collection The assertions.
     */
    public static function get_assertions(obf_client $client,
            obf_badge $badge = null, $email = null, $limit = -1, $geteachseparately = false) {
        $badgeid = is_null($badge) ? null : $badge->get_id();
        $arr = $client->get_assertions($badgeid, $email);
        $assertions = array();
        if (!$geteachseparately) {
            /**
             * Using populated collection for assertions would result in getting
             * the latest badge data, might not match issued data. 
             * (issued badge data with addendums and changes)
             * 
             */
            $collection = new obf_badge_collection($client);
            $collection->populate(); 
        }

        if (is_array($arr)) {
            foreach ($arr as $item) {
                if (!is_null($badge)) {
                    $b = $badge;
                } else if ($geteachseparately && is_null($badge)) {
                    $b = self::get_assertion_badge($client, $item['badge_id'], $item['id']);
                } else {
                    $b = $collection->get_badge($item['badge_id']);
                    if (is_null($b)) { // Required for deleted and draft badges
                        $b = self::get_assertion_badge($client, $item['badge_id'], $item['id']);
                    }
                }

                if (!is_null($b)) {
                    $assertion = self::get_instance();
                    $assertion->set_badge($b)->set_id($item['id'])->set_recipients($item['recipient']);
                    if (isset($item['log_entry'])){
                        $assertion->set_log_entry($item['log_entry']);
                    }
                    $assertion->set_expires($item['expires'])->set_name($item['name']);
                    $assertion->set_issuedon($item['issued_on'])->set_source(self::ASSERTION_SOURCE_OBF);
                    if (array_key_exists('revoked', $item)) {
                        $assertion->set_revoked($item['revoked']);
                    }
                    $assertions[] = $assertion;
                }

            }
        }

        // Sort the assertions by date...
        usort($assertions,
                function (obf_assertion $a1, obf_assertion $a2) {
                    return $a1->get_issuedon() <= $a2->get_issuedon();
                });

        // ... And limit the result set if that's what we want.
        if ($limit > 0) {
            $assertions = array_slice($assertions, 0, $limit);
        }

        return new obf_assertion_collection($assertions);
    }
    
    /**
     * Get badge details for an issued badge.
     * 
     * @param type $client The client instance.
     * @param type $badgeid The badge id.
     * @param type $eventid The event id.
     * @return obf_badge
     */
    public static function get_assertion_badge($client, $badgeid, $eventid) {
            $cache = cache::make('local_obf', 'obf_pub_badge');
            $cacheid = $badgeid .'/'. $eventid;
            $arr = $cache->get($cacheid);
            if (!$arr) {
                $arr = $client->pub_get_badge($badgeid, $eventid);
                $cache->set($cacheid, $arr);
            }
            if ($arr) {
                $badge = obf_badge::get_instance_from_array($arr);
                $badge->set_id($badgeid);
                // We can at the moment assume issuer is the same as the defined client.
                // Because we only get assertions for api_consumer_id
                // otherwise issuer could also be a suborganisation
                //$badge->set_issuer_url($arr['issuer']);
                
                return $badge;
            }
            return null;
    }
    
    public static function get_user_moodle_badge_assertions($user_id = 0, $limit = -1) {
        global $CFG;
        $badgeslib_file = $CFG->libdir.'/badgeslib.php';
        $assertions = array();
        if (file_exists($badgeslib_file)) {
            require_once($badgeslib_file);
            $moodle_badges = badges_get_user_badges($user_id, 0, 0, 0, '', false);
            foreach ($moodle_badges as $moodle_badge) {
                $assertion = self::get_instance();
                $obf_badge = obf_badge::get_instance_from_moodle_badge($moodle_badge);
                $assertion->set_badge($obf_badge);
                $assertion->set_issuedon($moodle_badge->dateissued)->set_source(self::ASSERTION_SOURCE_MOODLE);
                if (!empty($moodle_badge->dateexpire)) {
                    $assertion->set_expires($moodle_badge->dateexpire);
                }
                $assertion->set_recipients(array($moodle_badge->email));
                $assertions[] = $assertion;
            }
        }
        // Sort the assertions by date...
        usort($assertions,
                function (obf_assertion $a1, obf_assertion $a2) {
                    return $a1->get_issuedon() <= $a2->get_issuedon();
                });

        // ... And limit the result set if that's what we want.
        if ($limit > 0) {
            $assertions = array_slice($assertions, 0, $limit);
        }

        return new obf_assertion_collection($assertions);
    }

    /**
     * Checks whether two assertions are equal.
     *
     * @param obf_assertion $another
     * @return boolean True on success, false otherwise.
     */
    public function equals(obf_assertion $another) {
        $recipients = $this->get_valid_recipients();
        $recipientsforanother = $another->get_valid_recipients();
        // When getting assertions from OBF for signle user, the recipientlist only has 1 recipient,
        // so checking valid recipient count matches makes sure revokated badges do not duplicate,
        // valid badges.
        // PENDING: Is this comparison enough?
        return ($this->get_badge()->equals($another->get_badge()) && count($recipients) == count($recipientsforanother));
    }

    /**
     * Returns all assertions related to $badge.
     *
     * @param obf_badge $badge The badge.
     * @param obf_client $client
     * @return obf_assertion_collection The related assertions.
     */
    public static function get_badge_assertions(obf_badge $badge,
            obf_client $client) {
        return self::get_assertions($client, $badge);
    }

    /**
     * Checks whether the badge has expired.
     *
     * @return boolean True, if the badge has expired and false otherwise.
     */
    public function badge_has_expired() {
        return ($this->has_expiration_date() && $this->expires < time());
    }

    /**
     * Has expiration date?
     * @return boolean True if expiration date is set
     */
    public function has_expiration_date() {
        return !empty($this->expires) && $this->expires != 0;
    }

    /**
     * Get expiration date.
     * @return int Expiration date as a unix-timestamp
     */
    public function get_expires() {
        return $this->expires;
    }

    /**
     * Set expiraiton date.
     * @param int $expires Expiration date as a unix-timestamp
     * @return $this
     */
    public function set_expires($expires) {
        $this->expires = $expires;
        return $this;
    }

    /**
     * Get id.
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Set id
     * @param int $id
     */
    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Get error
     * @return string Error message.
     */
    public function get_error() {
        return $this->error;
    }

    /**
     * Get badge.
     * @return obf_badge
     */
    public function get_badge() {
        return $this->badge;
    }

    /**
     * Set badge.
     * @param obf_badge $badge
     * @return $this
     */
    public function set_badge($badge) {
        $this->badge = $badge;
        return $this;
    }

    /**
     * Get criteria addendum.
     * @return string Criteria addendum
     */
    public function get_criteria_addendum() {
        return $this->criteriaaddendum;
    }

    /**
     * Set criteria addendum.
     * @param string $criteriaaddendum
     * @return \obf_assertion
     */
    public function set_criteria_addendum($criteriaaddendum) {
        $this->criteriaaddendum = $criteriaaddendum;
        return $this;
    }

    /**
     * Get email subject.
     * @return string Email subject
     */
    public function get_emailsubject() {
        return $this->get_email_template()->get_subject();
    }

    /**
     * Set email subject.
     * @param string $emailsubject
     */
    public function set_emailsubject($emailsubject) {
        $this->get_email_template()->set_subject($emailsubject);
        return $this;
    }

    /**
     * Get emailfooter.
     * @return string Email footer
     */
    public function get_emailfooter() {
        return $this->get_email_template()->get_footer();
    }

    /**
     * Set email footer.
     * @param string $emailfooter Email footer
     */
    public function set_emailfooter($emailfooter) {
        $this->get_email_template()->set_footer($emailfooter);
        return $this;
    }

    /**
     * Get email body.
     * @return string Email message body
     */
    public function get_emailbody() {
        return $this->get_email_template()->get_body();
    }

    /**
     * Set email body.
     * @param string $emailbody Email message body
     */
    public function set_emailbody($emailbody) {
        $this->get_email_template()->set_body($emailbody);
        return $this;
    }

    /**
     * Get issued on.
     * @return int Issue time as a unix-timestamp
     */
    public function get_issuedon() {
        return $this->issuedon;
    }

    /**
     * Set issued on timestamp.
     * @param int $issuedon Issue time as a unix-timestamp
     */
    public function set_issuedon($issuedon) {
        $this->issuedon = $issuedon;
        return $this;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get_log_entry($key) {
        return $this->log_entry[$key];
    }

    /**
     * @param $log_entry
     * @return $this
     */
    public function set_log_entry($log_entry) {
        $this->log_entry = $log_entry;
        return $this;
    }

    /**
     * Get assertion event recipients.
     * @return string[] Array of email addresses who received the assertion
     */
    public function get_recipients() {
        return $this->recipients;
    }
    /**
     * Get recipients, whose badge has not been revoked.
     *
     * @return string[] Email-addresses of recipients.
     */
    public function get_valid_recipients() {
        $recipients = $this->recipients;
        foreach ($this->recipients as $recipient) {
            $email = $recipient;
            if ($this->is_revoked_for_email($email)) {
                $key = array_search($email, $recipients);
                if ($key !== false) {
                    unset($recipients[$key]);
                }
            }
        }
        return $recipients;
    }

    /**
     * Set recipients.
     * @param string[] $recipients Array of recipient email addresses
     */
    public function set_recipients($recipients) {
        $this->recipients = $recipients;
        return $this;
    }

    /**
     * Get source of assertion. (Where moodle retrieved the assertion from)
     * @return int Assertion source as self::ASSERTION_SOURCE_*
     */
    public function get_source() {
        return $this->source;
    }

    /**
     * Set assertion source. (Where moodle retrieved the assertion from)
     * @param int $source Assertion source as self::ASSERTION_SOURCE_*
     */
    public function set_source($source) {
        $this->source = $source;
        return $this;
    }

    /**
     * Get list addresses, for which the assertion event is revoked for.
     * @param obf_client $client
     * @return array Array of revocation details as array(array(email-address => unix-timestamp),...)
     */
    public function get_revoked(obf_client $client = null) {
        if (!is_null($client) && count($this->revoked < 1)) {
            try {
                $arr = $client->get_revoked($this->id);
                if (array_key_exists('revoked', $arr)) {
                    $this->revoked = $arr['revoked'];
                }
            } catch (Exception $e) {
                // API method for revoked may not be published yet.
                $this->revoked = array();
            }
        }
        return $this->revoked;
    }

    /**
     * Set revocation details.
     * @param array $revoked Array of revocation details as array(array(email-address => unix-timestamp),...)
     * @return $this
     */
    public function set_revoked($revoked) {
        $this->revoked = $revoked;
        return $this;
    }
    /**
     * Check if this assertion is revoked for user.
     * @param stdClass $user
     * @return bool True if revoked for user.
     * @todo Should users backpack emails be checked also?
     */
    public function is_revoked_for_user(stdClass $user) {
        return (in_array($user->email, array_keys($this->revoked)));
    }
    /**
     * Check if this assertion is revoked for an email address.
     * @param string $email
     * @return bool True if revoked for address.
     * @todo Should users backpack emails be checked also?
     */
    public function is_revoked_for_email($email) {
        return (in_array($email, array_keys($this->revoked)));
    }

    /**
     * Get the name of the assertion.
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Set the name of the assertion.
     * @param string $name
     * @return $this
     */
    public function set_name($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get users matching recipient email-addresses.
     * @param array $emails Limit users to specified email addresses
     * @return stdClass[]
     */
    public function get_users($emails = null) {
        global $DB;
        if (is_null($emails)) {
            $emails = $this->get_recipients();
        }
        $users = $DB->get_records_list('user', 'email', $emails);
        if (count($users) < count($emails)) {
            foreach ($users as $user) {
                $key = array_search($user->email, $emails);
                if ($key !== false) {
                    unset($emails[$key]);
                }
            }
            foreach ($emails as $email) {
                $backpack = obf_backpack::get_instance_by_backpack_email($email);
                if ($backpack !== false) {
                    $users[] = $DB->get_record('user',
                                    array('id' => $backpack->get_user_id()));
                }
            }
        }
        return $users;
    }
    
    public function get_email_template() {
        if (is_null($this->emailtemplate)) {
            $this->emailtemplate = new obf_email();
        }
        return $this->emailtemplate;
    }

    public function set_email_template(obf_email $emailtemplate) {
        $this->emailtemplate = $emailtemplate;
        return $this;
    }



}
