<?php
/**
 * Class for a single Open Badge Factory -badge
 */
class obf_badge implements cacheable_object {

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
    private $expires = null;
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
    public static function get_instance($id = null) {
        $obj = new self();

        if (!is_null($id)) {
            $obj->set_id($id)->populate();
        }

        return $obj;
    }

    /**
     * Creates a new instance of the class from a JSON-string.
     * 
     * @param string $json The badge data in JSON
     * @return obf_badge The badge.
     */
    public static function get_instance_from_json($json) {
        return obf_badge::get_instance()->populate_from_json($json);
    }

    /**
     * Populates the object's properties from JSON
     * 
     * @param string $json The badge's data in JSON
     * @return obf_badge
     */
    public function populate_from_json($json) {
        return $this->set_criteria($json['criteria'])
                        ->set_description($json['description'])
                        ->set_expires($json['expires'])
                        ->set_id($json['id'])
                        ->set_isdraft((bool) $json['draft'])
                        ->set_tags($json['tags'])
                        ->set_image($json['image'])
                        ->set_created($json['ctime'])
                        ->set_name($json['name']);
    }

    public function get_issuer() {
        if (is_null($this->issuer)) {
            $this->issuer = obf_issuer::get_instance_from_json(obf_get_issuer_json());
        }
        
        return $this->issuer;
    }

    /**
     * Gets the object's data from the OBF API and populates the properties
     * from the returned JSON-data.
     * 
     * @return obf_badge
     */
    public function populate() {
        return $this->populate_from_json(obf_get_badge_json($this->id));
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
        return $this->expires;
    }

    public function set_expires($expires) {
        $this->expires = $expires;
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
