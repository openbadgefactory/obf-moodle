<?php

/**
 * Class representing the OBF-badge tree with folders and badges
 */
class obf_badge_tree implements renderable, cacheable_object {

    /**
     * @var obf_badge_folder[] The badge folders
     */
    private $folders = array();

    /**
     * Constructs the object by creating a sane folder structure
     * 
     * @param array $badges The OBF-badges and -folders in a format given
     *      by the OBF API.
     * @see obf_badge_tree::folderify_badges()
     */
    public function __construct($badges = array()) {
        $this->folderify_badges($badges);
    }

    /**
     * 
     * @param type $reload
     * @return \self
     */
    public static function get_instance($reload = false) {
        $tree = false;
        $clientid = obf_client_id();
        $obfcache = cache::make('local_obf', 'obfcache');

        if (!$reload) {
            $tree = $obfcache->get($clientid);
        }

        if ($tree === false) {
            $tree = new self(obf_get_badges());
            $obfcache->set($clientid, $tree);
        }

        return $tree;
    }

    /**
     * Converts the badges and folders into a format accepted by this class.
     * 
     * @param array $badges The OBF-badges and -folders in a format given
     *      by the OBF API converted from JSON to an associative array:
     * 
     *      [
     *          "/": ["folder1", ..., "folderN"],
     *          "badge1_id": [ "name": "foo", ... ],
     *          "badge2_id": [ "name": "bar", ... ]
     *      ]
     */
    private function folderify_badges($badges) {

        if (!is_array($badges))
            return;

        $emptyfoldercreated = false;

        // Are there any folders?
        if (array_key_exists('/', $badges)) {
            // Create the folders first.
            foreach ($badges['/'] as $folder)
                $this->add_folder(new obf_badge_folder($folder));

            // We don't need the folders when iterating through the badges.
            unset($badges['/']);
        }

        // Create the badge objects and add them to corresponding folder objects.
        foreach ($badges as $badgeid => $badgedata) {
            $badgefolder = $badgedata['path'];

            if (empty($badgefolder) && !$emptyfoldercreated) {
                $this->add_folder(new obf_badge_folder(''));
                $emptyfoldercreated = true;
            }

            $badge = obf_badge::get_instance()
                    ->set_id($badgeid)
                    ->set_folder($badgefolder)
                    ->populate();

            $this->get_folder($badge->get_folder())->add_badge($badge);
        }
    }

    /**
     * Returns the badge folders
     * 
     * @return obf_badge_folder[] The folders. 
     */
    public function get_folders() {
        return $this->folders;
    }

    /**
     * Adds a folder to the tree.
     * 
     * @param obf_badge_folder $folder The folder to be added. 
     */
    public function add_folder(obf_badge_folder $folder) {
        $this->folders[] = $folder;
    }

    /**
     * Returns the folder matching <code>$name</code>
     * 
     * @param string $name The name of the folder.
     * @return obf_badge_folder|boolean Returns the matching folder or <code>false</code>
     *      if not found.
     */
    public function get_folder($name) {
        foreach ($this->folders as $folder) {
            if ($folder->get_name() == $name)
                return $folder;
        }

        return false;
    }

    /**
     * Prepares the object to cache
     * 
     * @return \cacheable_object_array
     */
    public function prepare_to_cache() {
        return new cacheable_object_array($this->folders);
    }

    /**
     * Called when woken up from the cache
     * 
     * @param type $data
     * @return \self
     */
    public static function wake_from_cache($data) {
        $tree = new self();
        foreach ($data as $folder)
            $tree->add_folder($folder);

        return $tree;
    }

}

?>
