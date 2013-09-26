<?php

defined('MOODLE_INTERNAL') || die();

/**
 * 
 * @global type $CFG
 * @param type $badgeid
 * @return type
 */
function obf_get_badge_json($badgeid) {
    global $CFG;

    include_once $CFG->libdir . '/filelib.php';

    $curl = new curl();
    $options = obf_get_curl_options();
    $output = $curl->get($CFG->obf_url . '/badge/' . $CFG->obf_client_id . '/' . $badgeid, array(), $options);  
    $json = json_decode($output);

    return $json;
}

/**
 * 
 * @param type $badgeid
 * @return type
 */
function obf_get_badge($badgeid) {
    return obf_badge::get_instance_from_json(obf_get_badge_json($badgeid));
}

/**
 * 
 * @return type
 */
function obf_get_curl_options() {
    return array(
        'RETURNTRANSFER' => true,
        'FOLLOWLOCATION' => false,
        'SSL_VERIFYHOST' => false, // for testing
        'SSL_VERIFYPEER' => false, // for testing
        'SSLCERT' => '/tmp/test.pem',
        'SSLKEY' => '/tmp/test.key'
    );
}

function obf_get_badge_tree($reload = false) {
    global $CFG;

    $badges = false;
    $obfcache = cache::make('local_obf', 'obfcache');
    
    if (!$reload) {        
        $badges = $obfcache->get($CFG->obf_client_id);
    }

    if ($badges === false) {
        $badges = new obf_badge_tree(obf_get_badges());
        $obfcache->set($CFG->obf_client_id, $badges);
    }

    return $badges;
}

/**
 * 
 * @return type
 */
function obf_get_badges() {
    global $CFG;

    include_once $CFG->libdir . '/filelib.php';

//    if (empty($CFG->obf_client_id))
//        throw new Exception(get_string('missingclientid', 'local_obf'));
    
    $curl = new curl();
    $options = obf_get_curl_options();
    $output = $curl->get($CFG->obf_url . '/tree/' . $CFG->obf_client_id . '/badge', array(), $options);
    $code = $curl->info['http_code'];
    $json = json_decode($output, true);
    
    if ($code !== 200)
    {
//        debugging('Curl request failed: ' . $curl->error);
        throw new Exception(get_string('apierror' . $code, 'local_obf', array("error" => $json['error'])));
    }
        
    return $json;
}

/**
 * Class for representing a badge folder in Open Badge Factory
 */
class obf_badge_folder implements cacheable_object {

    /**
     * @var string The name of the badge folder
     */
    private $name = '';

    /**
     * @var obf_badge[] The badges in this folder
     */
    private $badges = array();

    /**
     * Constructs the object
     * 
     * @param string $name The name of the badge folder
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * Adds a badge to this folder
     * 
     * @param obf_badge $badge The badge to be added.
     */
    public function add_badge(obf_badge $badge) {
        $this->badges[] = $badge;
    }

    /**
     * 
     * @return type
     */
    public function get_badges() {
        return $this->badges;
    }

    /**
     * 
     * @param array $badges
     */
    public function set_badges(array $badges) {
        $this->badges = $badges;
    }

    /**
     * Checks whether the folder has a name
     * 
     * @return bool Returns true if the folder has a name and false otherwise
     */
    public function has_name() {
        return !empty($this->name);
    }

    public function get_name() {
        return $this->name;
    }

    /**
     * Prepares the object to cache.
     * 
     * @return type
     */
    public function prepare_to_cache() {
        return array("name" => $this->name, "badges" => new cacheable_object_array($this->badges));
    }

    /**
     * Called when woken up from the cache.
     * 
     * @param type $data
     * @return \obf_badge_folder
     */
    public static function wake_from_cache($data) {
        $folder = new obf_badge_folder($data['name']);
        $folder->set_badges($data['badges']);

        return $folder;
    }

}

/**
 * Class for a single Open Badge Factory -badge
 */
class obf_badge implements cacheable_object {

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
     * Returns a new instance of the class.
     * 
     * @return obf_badge
     */
    public static function get_instance() {
        return new self();
    }

    /**
     * Creates a new instance of the class from a JSON-string.
     * 
     * @param string $json The badge data in JSON
     * @return obf_badge The badge.
     */
    public static function get_instance_from_json($json) {
        $badge = obf_badge::get_instance();
        $badge->populate_from_json($json);

        return $badge;
    }

    /**
     * Populates the object's properties from JSON
     * 
     * @param string $json The badge's data in JSON
     * @return obf_badge
     */
    public function populate_from_json($json) {
        return $this->set_criteria($json->criteria)
                        ->set_description($json->description)
                        ->set_expires($json->expires)
                        ->set_id($json->id)
                        ->set_isdraft((bool) $json->draft)
                        ->set_tags($json->tags)
                        ->set_image($json->image)
                        ->set_created($json->ctime)
                        ->set_name($json->name);
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
