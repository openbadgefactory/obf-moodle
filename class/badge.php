<?php

require_once __DIR__ . '/tree.php';
require_once __DIR__ . '/issuer.php';
require_once __DIR__ . '/issuance.php';
require_once __DIR__ . '/client.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/criterion/criterionbase.php';
require_once __DIR__ . '/assertion.php';

/**
 * Class for a single Open Badge Factory -badge
 */
class obf_badge implements cacheable_object {

    /**
     *
     * @var obf_client
     */
    private $client = null;

    /**
     * @var obf_issuer
     */
    private $issuer = null;

    /**
     * @var obf_email
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
     * @var string The name of badge's folder
     */
    private $folder = '';
    private $isdraft = true;

    /**
     * @var string The badge description
     */
    private $description = '';
    private $criteria_html = '';
    private $criteria_css = '';
    private $expiresby = null;
    private $tags = array();

    /**
     * @var int The badge creation time as an unix-timestamp
     */
    private $created = null;

    /**
     * Returns an instance of the class. If <code>$id</code> isn't set, this
     * will return a new instance.
     *
     * @param string $id The id of the badge.
     * @return obf_badge
     */
    public static function get_instance($id = null, $client = null) {
        $obj = new self();

        if (!is_null($client)) {
            $obj->set_client($client);
        }

        if (!is_null($id)) {
            $obj->set_id($id)->populate();
        }

        return $obj;
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
        $this->set_description($arr['description'])
                ->set_expires($arr['expires'])
                ->set_id($arr['id'])
                ->set_isdraft((bool) $arr['draft'])
                ->set_image($arr['image'])
                ->set_created($arr['ctime'])
                ->set_name($arr['name']);

        if (isset($arr['tags'])) {
            $this->set_tags($arr['tags']);
        }

        if (isset($arr['css'])) {
            $this->set_criteria_css($arr['css']);
        }

        if (isset($arr['criteria_html'])) {
            $this->set_criteria_html($arr['criteria_html']);
        }

        $email = obf_email::get_by_badge($this);

        // No email template in the local database yet
        if (is_null($email)) {
            $email = new obf_email();
            $email->set_badge_id($this->get_id());

            if (isset($arr['email_subject'])) {
                $email->set_subject($arr['email_subject']);
            }

            if (isset($arr['email_footer'])) {
                $email->set_footer($arr['email_footer']);
            }

            if (isset($arr['email_body'])) {
                $email->set_body($arr['email_body']);
            }

            $email->save();
        }

        $this->set_email($email);

        return $this;
    }

    public function export() {
        try {
            obf_client::get_instance()->export_badge($this);
        } catch (Exception $e) {
            debugging('Exporting badge ' . $this->get_name() . ' failed: ' . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     *
     * @return type
     */
    public function get_issuer() {
        if (is_null($this->issuer)) {
            $this->issuer = obf_issuer::get_instance_from_arr($this->get_client()->get_issuer());
        }

        return $this->issuer;
    }

    /**
     *
     * @param array $recipients
     * @param type $issuedon
     * @param type $emailsubject
     * @param type $emailbody
     * @param type $emailfooter
     */
    public function issue(array $recipients, $issuedon, $emailsubject, $emailbody, $emailfooter) {
        if (empty($this->id))
            throw new Exception('Invalid or missing badge id');

        $this->get_client()->issue_badge($this, $recipients, $issuedon, $emailsubject, $emailbody,
                $emailfooter);
    }

    public static function get_by_user($user) {
        $email = $user->email;
        $assertions = obf_assertion::get_assertions(null, $email);
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
     *
     * @return obf_assertion_collection
     */
    public function get_assertions() {
        return obf_assertion::get_badge_assertions($this);
    }

    /**
     *
     * @return obf_issuance
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
     * @return obf_badge
     */
    public function populate() {
        return $this->populate_from_array($this->get_client()->get_badge($this->id));
    }

    public function has_expiration_date() {
        return !empty($this->expiresby);
    }

    public function get_default_expiration_date() {
        return (strtotime('+ ' . $this->expiresby . ' months'));
    }

    public function get_completion_criteria() {
        return obf_criterion_base::get_badge_criteria($this);
    }

    public function get_email() {
        if (is_null($this->email)) {
            $this->email = obf_email::get_by_badge($this);
        }

        return $this->email;
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

    public function get_folder() {
        return $this->folder;
    }

    public function set_folder($folder) {
        $this->folder = $folder;
        return $this;
    }

    public function get_isdraft() {
        return $this->isdraft;
    }

    public function set_isdraft($isdraft) {
        $this->isdraft = $isdraft;
        return $this;
    }

    public function get_description() {
        if (is_null($this->description)) {
            $this->populate();
        }
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
        if (is_null($this->client)) {
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

    /**
     * Prepares the object to cache.
     *
     * @return type
     */
    public function prepare_to_cache() {
        return get_object_vars($this);
    }

    /**
     * Called when woken up from the cache.
     *
     * @param type $data
     * @return type
     */
    public static function wake_from_cache($data) {
        $badge = self::get_instance();

        foreach ($data as $name => $value) {
            if (property_exists($badge, $name)) {
                call_user_func(array($badge, 'set_' . $name), $value);
            }
        }

        return $badge;
    }

}

?>
