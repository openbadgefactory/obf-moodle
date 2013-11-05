<?php
/**
 * Class for representing a badge folder in Open Badge Factory
 */
class obf_badge_folder {// implements cacheable_object {

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
?>
