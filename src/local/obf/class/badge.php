<?php

require_once __DIR__ . '/issuer.php';
require_once __DIR__ . '/client.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/criterion/criterion.php';
require_once __DIR__ . '/assertion.php';
require_once __DIR__ . '/assertion_collection.php';

/**
 * Class for a single Open Badge Factory -badge
 */
class obf_badge {

    private static $badgecache = array();

    /**
     * @var obf_client The client instance.
     */
    private $client = null;

    /**
     * @var obf_issuer The issuer of the badge.
     */
    private $issuer = null;

    /**
     * @var obf_email The email template.
     */
    private $email = null;

    /**
     * @var string The id of the badge
     */
    private $id = null;

    /**
     * @var string The name of the badge
     */
    private $name = '';

    /**
     * @var string The badge image in base64
     */
    private $image = null;

    /**
     * @var boolean Whether the badge is a draft only.
     */
    private $isdraft = true;

    /**
     * @var string The badge description
     */
    private $description = '';

    /**
     * @var string The HTML-markup of badge criteria.
     */
    private $criteria_html = '';

    /**
     * @var string The CSS of the badge criteria page.
     */
    private $criteria_css = '';

    /**
     * @var string The URL of the badge criteria.
     */
    private $criteria_url = '';
    private $expiresby = null;

    /**
     * @var string[] The tags of the badge.
     */
    private $tags = array();

    /**
     * @var int The badge creation time as an unix-timestamp
     */
    private $created = null;

    /**
     * @var string[] The categories of the badge.
     */
    private $categories = array();

    /**
     * Returns an instance of the class. If <code>$id</code> isn't set, this
     * will return a new instance.
     *
     * @param string $id The id of the badge.
     * @return obf_badge
     */
    public static function get_instance($id = null, $client = null) {
        $obj = null;

        if (is_null($id)) {
            $obj = new self();
        }
        else if (!isset(self::$badgecache[$id])) {
            $obj = new self();

            if ($obj->set_id($id)->populate() !== false) {
                self::$badgecache[$id] = $obj;
            }
        }
        else {
            $obj = self::$badgecache[$id];
        }

        if (!is_null($client)) {
            $obj->set_client($client);
        }

        return $obj;
    }

    public function equals(obf_badge $another) {
        return (strcmp($this->get_name(), $another->get_name()) === 0 &&
                strcmp($this->get_description(), $another->get_description()) === 0);
    }

    public function image_equals(obf_badge $another) {
        return $this->get_image() == $another->get_image();
    }

    /**
     * Returns the badge categories that are set as issuable in the system.
     *
     * @return string[] The available categories.
     */
    public static function get_available_categories() {
        $categorystr = get_config('local_obf', 'availablecategories');
        $categories = !empty($categorystr) ? explode(',', trim($categorystr)) : array();

        return $categories;
    }

    /**
     * Gets and returns the badges from OBF.
     *
     * @param obf_client $client The client instance.
     * @return obf_badge[] The badges.
     */
    public static function get_badges(obf_client $client = null) {
        $client = is_null($client) ? obf_client::get_instance() : $client;
        $badgearr = $client->get_badges(self::get_available_categories());

        foreach ($badgearr as $badgedata) {
            $badge = self::get_instance_from_array($badgedata);
            self::$badgecache[$badge->get_id()] = $badge;
        }

        return self::$badgecache;
    }

    /**
     * Creates a new instance of the class from an array. The array should have
     * the following keys:
     *
     * - criteria
     * - description
     * - expires
     * - id
     * - draft
     * - tags
     * - image
     * - ctime
     * - name
     *
     * @param array $arr The badge data as an associative array
     * @return obf_badge The badge.
     */
    public static function get_instance_from_array($arr) {
        return obf_badge::get_instance()->populate_from_array($arr);
    }

    /**
     * Populates the object's properties from an array.
     *
     * @param array $arr The badge's data as an associative array
     * @see get_instance_from_array()
     * @return obf_badge
     */
    public function populate_from_array($arr) {
        global $DB;

        // These should always exist.
        $this->set_description($arr['description'])
                ->set_id($arr['id'])
                ->set_isdraft((bool) $arr['draft'])
                ->set_image($arr['image'])
                ->set_created($arr['ctime'])
                ->set_name($arr['name']);

        $expires = (int) $arr['expires'];

        if ($expires > 0) {
            $this->set_expires(strtotime('+ ' . $expires . ' months'));
        }

        isset($arr['criteria_url']) and $this->set_criteria_url($arr['criteria_url']);
        isset($arr['category']) and $this->set_categories($arr['category']);
        isset($arr['tags']) and $this->set_tags($arr['tags']);
        isset($arr['css']) and $this->set_criteria_css($arr['css']);
        isset($arr['criteria_html']) and $this->set_criteria_html($arr['criteria_html']);

        // Try to get the email template from the local database first.
        $email = obf_email::get_by_badge($this, $DB);

        // No email template in the local database yet, try to get from the array.
        $hasemail = isset($arr['email_subject']) || isset($arr['email_footer']) || isset($arr['email_body']);

        if (is_null($email) && $hasemail) {
            $email = new obf_email();
            $email->set_badge_id($this->get_id());

            isset($arr['email_subject']) and $email->set_subject($arr['email_subject']);
            isset($arr['email_footer']) and $email->set_footer($arr['email_footer']);
            isset($arr['email_body']) and $email->set_body($arr['email_body']);

            $email->save($DB);
        }
        !is_null($email) and $this->set_email($email);

        return $this;
    }

    /**
     * Exports this badge to OBF.
     *
     * @param obf_client $client The client instance.
     * @return boolean Returns true on success, false otherwise.
     */
    public function export(obf_client $client) {
        try {
            $client->export_badge($this);
        } catch (Exception $e) {
            debugging('Exporting badge ' . $this->get_name() . ' failed: ' . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Returns the issuer of the badge.
     *
     * @return obf_issuer The issuer.
     */
    public function get_issuer() {
        if (is_null($this->issuer)) {
            $this->issuer = obf_issuer::get_instance_from_arr($this->get_client()->get_issuer());
        }

        return $this->issuer;
    }

    /**
     * Issues this badge to $recipients.
     *
     * @param string[] $recipients The email addresses of the recipients.
     * @param int $issuedon When the badge was issued, Unix timestamp.
     * @param string $emailsubject The subject of the email sent to user.
     * @param string $emailbody The body of the email sent to user.
     * @param string $emailfooter The footer of the email sent to user.
     */
    public function issue(array $recipients, $issuedon, $emailsubject,
                          $emailbody, $emailfooter) {
        if (empty($this->id)) {
            throw new Exception('Invalid or missing badge id');
        }

        $this->get_client()->issue_badge($this, $recipients, $issuedon,
                $emailsubject, $emailbody, $emailfooter);

        $raw = $this->get_client()->get_raw_response();
        foreach ($raw as $key => $value) {
            if ($match = preg_match('/^Location: .*event\/[\w]+\/(.*)$/i', $value, $matches)) {
                $eventid = trim($matches[$match]);
                if (!empty($eventid)) {
                    return $eventid;
                }
            }
        }
        return true;
    }

    /**
     * Returns the badges issued to $user.
     *
     * @param type $user The Moodle's user object.
     * @param obf_client $client The client instance.
     * @return obf_badge[] The badges.
     */
    public static function get_by_user($user, obf_client $client) {
        $email = $user->email;
        $assertions = obf_assertion::get_assertions($client, null, $email);
        $badges = array();

        for ($i = 0; $i < $assertions->count(); $i++) {
            $assertion = $assertions->get_assertion($i);
            $badge = $assertion->get_badge();

            if (!isset($badges[$badge->get_id()])) {
                $badges[$badge->get_id()] = $badge;
            }
        }

        return $badges;
    }

    /**
     * Get assertions related to this badge.
     *
     * @return obf_assertion_collection The assertions.
     */
    public function get_assertions() {
        return obf_assertion::get_badge_assertions($this, $this->get_client());
    }

    /**
     * Get assertions related to this badge that haven't been expired.
     *
     * @return obf_assertion[] The non-expired assertions.
     */
    public function get_non_expired_assertions() {
        $assertions = $this->get_assertions();
        $ret = array();

        foreach ($assertions as $assertion) {
            if (!$assertion->badge_has_expired()) {
                $ret[] = $assertion;
            }
        }

        return $ret;
    }

    /**
     * Gets the object's data from the OBF API and populates the properties
     * from the returned array.
     *
     * @return obf_badge|boolean Returns the badge on success, false otherwise.
     */
    public function populate() {
        try {
            $arr = $this->get_client()->get_badge($this->id);
            return $this->populate_from_array($arr);
        } catch (Exception $exc) {
            return false;
        }
    }

    /**
     * Checks whether the badge has an expiration date set or not.
     *
     * @return boolean True if there's an expiration date, false otherwise.
     */
    public function has_expiration_date() {
        return !empty($this->expiresby) && $this->expiresby != 0;
    }

    /**
     * Returns the default (set in OBF) expiration date of the badge.
     *
     * @return int The expiration date as a Unix timestamp.
     */
    public function get_default_expiration_date() {
        return (strtotime('+ ' . $this->expiresby . ' months'));
    }

    /**
     * Returns all the completion criteria related to this badge.
     *
     * @return obf_criterion[] The criteria.
     */
    public function get_completion_criteria() {
        return obf_criterion::get_badge_criteria($this);
    }

    /**
     * Checks whether there is completion criteria related to this badge and
     * $course.
     *
     * @param stdClass $course The Moodle's course object.
     * @return boolean True, if criteria is found, false otherwise.
     */
    public function has_completion_criteria_with_course(stdClass $course) {
        $criteria = $this->get_completion_criteria();

        foreach ($criteria as $criterion) {
            $courses = $criterion->get_items();

            foreach ($courses as $criterioncourse) {
                if ($criterioncourse->get_courseid() == $course->id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the email template associated with this badge.
     *
     * @global type $DB
     * @return obf_email The email template.
     */
    public function get_email() {
        global $DB;

        if (is_null($this->email)) {
            $this->email = obf_email::get_by_badge($this, $DB);
        }

        return $this->email;
    }

    /**
     * Returns the badges associated with course identified by $courseid.
     *
     * @param int $courseid
     * @return obf_badge[] The badges.
     */
    public static function get_badges_in_course($courseid) {
        $criteria = obf_criterion::get_course_criterion($courseid);
        $badges = array();

        foreach ($criteria as $criterion) {
            $badges[] = $criterion->get_badge();
        }

        return $badges;
    }

    /**
     * Returns this badge as an associative array.
     *
     * @return array The badge data as an array.
     */
    public function toArray() {
        return array(
            'issuer' => $this->get_issuer()->toArray(),
            'name' => $this->get_name(),
            'image' => $this->get_image(),
            'description' => $this->get_description(),
            'criteria_url' => $this->get_criteria_url());
    }

    public function set_issuer(obf_issuer $issuer) {
        $this->issuer = $issuer;
        return $this;
    }

    public function set_email(obf_email $email) {
        $this->email = $email;
        return $this;
    }

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function get_name() {
        return $this->name;
    }

    public function set_name($name) {
        $this->name = $name;
        return $this;
    }

    public function get_image() {
        if (empty($this->image)) {
            $this->populate();
        }

        return $this->image;
    }

    public function set_image($ımage) {
        $this->image = $ımage;
        return $this;
    }

    public function is_draft() {
        return $this->isdraft;
    }

    public function set_isdraft($isdraft) {
        $this->isdraft = $isdraft;
        return $this;
    }

    public function get_description() {
//        if (is_null($this->description)) {
//            $this->populate();
//        }
        return $this->description;
    }

    public function set_description($description) {
        $this->description = $description;
        return $this;
    }

    public function get_criteria_html() {
        return $this->criteria_html;
    }

    public function set_criteria_html($criteria) {
        $this->criteria_html = $criteria;
        return $this;
    }

    public function get_expires() {
        return $this->expiresby;
    }

    public function set_expires($expires) {
        $this->expiresby = $expires;
        return $this;
    }

    public function get_tags() {
        return $this->tags;
    }

    public function set_tags($tags) {
        $this->tags = $tags;
        return $this;
    }

    public function get_created() {
        return $this->created;
    }

    public function set_created($created) {
        $this->created = $created;
        return $this;
    }

    public function get_client() {
        if (!$this->client instanceof obf_client) {
            $this->client = obf_client::get_instance();
        }

        return $this->client;
    }

    public function set_client(obf_client $client) {
        $this->client = $client;
    }

    public function get_criteria_css() {
        return $this->criteria_css;
    }

    public function set_criteria_css($criteria_css) {
        $this->criteria_css = $criteria_css;
        return $this;
    }

    public function get_criteria_url() {
        return $this->criteria_url;
    }

    public function set_criteria_url($criteria_url) {
        $this->criteria_url = $criteria_url;
        return $this;
    }

    public function has_criteria_url() {
        return !empty($this->criteria_url);
    }

    public function has_name() {
        return !empty($this->name);
    }

    public function get_categories() {
        return $this->categories;
    }

    public function set_categories($categories) {
        $this->categories = $categories;
        return $this;
    }

}
