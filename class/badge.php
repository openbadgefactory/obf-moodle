<?php

require_once __DIR__ . '/tree.php';
require_once __DIR__ . '/issuer.php';
require_once __DIR__ . '/issuance.php';
require_once __DIR__ . '/client.php';
require_once __DIR__ . '/criterion/criterionbase.php';

/**
 * Class for a single Open Badge Factory -badge
 */
class obf_badge implements cacheable_object {

    /**
     *
     * @var obf_client
     */
    private $client = null;
    
    private $issuer = null;

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
    private $criteria = '';
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
            // Try from badge_tree first, because it uses Moodle cache
            $badge = static::get_badge_from_tree($id);

            if ($badge !== false) {
                return $badge;
            }

            $obj->set_id($id)->populate();
        }

        return $obj;
    }

    public static function get_badge_from_tree($id) {
        return obf_badge_tree::get_instance()->get_badge($id);
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
        return $this->set_criteria($arr['criteria'])
                        ->set_description($arr['description'])
                        ->set_expires($arr['expires'])
                        ->set_id($arr['id'])
                        ->set_isdraft((bool) $arr['draft'])
                        ->set_tags($arr['tags'])
                        ->set_image($arr['image'])
                        ->set_created($arr['ctime'])
                        ->set_name($arr['name']);
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

        $this->get_client()->issue_badge($this, $recipients, $issuedon, $emailsubject, $emailbody, $emailfooter);
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
        if (empty($this->image))
            $this->populate();

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
        if (is_null($this->description))
            $this->populate();
        return $this->description;
    }

    public function set_description($description) {
        $this->description = $description;
        return $this;
    }

    public function get_criteria() {
        return $this->criteria;
    }

    public function set_criteria($criteria) {
        $this->criteria = $criteria;
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
        if (is_null($this->client))
            $this->client = obf_client::get_instance();
        
        return $this->client;
    }
    
    public function set_client(obf_client $client) {
        $this->client = $client;
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
