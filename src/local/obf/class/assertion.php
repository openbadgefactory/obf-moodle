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
     * @var int When the badge was issued, Unix timestamp.
     */
    private $issuedon = null;

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
     * @var Assertion source is unknown.
     */
    const ASSERTION_SOURCE_UNKNOWN = 0;
    /**
     * @var Assertion source is Open Badge Factory.
     */
    const ASSERTION_SOURCE_OBF = 1;
    /**
     * @var Assertion source is Open Badge Passport.
     */
    const ASSERTION_SOURCE_OBP = 2;
    /**
     * @var Assertion source is Mozilla Backpack.
     */
    const ASSERTION_SOURCE_MOZILLA = 3;

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
                    $this->emailsubject, $this->emailbody, $this->emailfooter);
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
            obf_badge $badge = null, $email = null, $limit = -1) {
        $badgeid = is_null($badge) ? null : $badge->get_id();
        $arr = $client->get_assertions($badgeid, $email);
        $assertions = array();
        $collection = new obf_badge_collection($client);
        $collection->populate();

        foreach ($arr as $item) {
            $b = is_null($badge) ? $collection->get_badge($item['badge_id']) : $badge;
            if (!is_null($badge)) {
                $assertion = self::get_instance();
                $assertion->set_badge($b)->set_id($item['id'])->set_recipients($item['recipient']);
                $assertion->set_expires($item['expires'])->set_name($item['name']);
                $assertion->set_issuedon($item['issued_on'])->set_source(self::ASSERTION_SOURCE_OBF);
                if (array_key_exists('revoked', $item)) {
                    $assertion->set_revoked($item['revoked']);
                }
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
     * Get email subject.
     * @return string Email subject
     */
    public function get_emailsubject() {
        return $this->emailsubject;
    }

    /**
     * Set email subject.
     * @param string $emailsubject
     */
    public function set_emailsubject($emailsubject) {
        $this->emailsubject = $emailsubject;
        return $this;
    }

    /**
     * Get emailfooter.
     * @return string Email footer
     */
    public function get_emailfooter() {
        return $this->emailfooter;
    }

    /**
     * Set email footer.
     * @param string $emailfooter Email footer
     */
    public function set_emailfooter($emailfooter) {
        $this->emailfooter = $emailfooter;
        return $this;
    }

    /**
     * Get email body.
     * @return string Email message body
     */
    public function get_emailbody() {
        return $this->emailbody;
    }

    /**
     * Set email body.
     * @param string $emailbody Email message body
     */
    public function set_emailbody($emailbody) {
        $this->emailbody = $emailbody;
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

}
