<?php

require_once(__DIR__ . '/client.php');
require_once(__DIR__ . '/folder.php');
require_once(__DIR__ . '/badge.php');

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
     * @param array $tree The OBF-badges and -folders in a format given
     *      by the OBF API.
     * @param array $badges The OBF-badges with image data and everything
     * @see obf_badge_tree::folderify_badges()
     */
    public function __construct($tree = array(), $badges = array()) {
        $this->folderify_badges($tree, $badges);
    }

    /**
     *
     * @param type $reload
     * @return obf_badge_tree
     */
    public static function get_instance($reload = false) {
//        $tree = false;
//        $clientid = obf_client::get_client_id();
//        $obfcache = cache::make('local_obf', 'obfcache');
//        if (!$reload) {
//            $tree = $obfcache->get($clientid);
//        }
//        if ($tree === false) {
//            $tree = new self(obf_client::get_instance()->get_tree());
//            var_dump($tree);
//            $obfcache->set($clientid, $tree);
//        }

        $client = obf_client::get_instance();
        $tree = new self($client->get_tree(), $client->get_badges());

        return $tree;
    }

    /**
     *
     * @return obf_badge_tree
     */
//    public static function get_from_cache() {
//        $clientid = obf_client::get_client_id();
//        $obfcache = cache::make('local_obf', 'obfcache');
//
//        return $obfcache->get($clientid);
//    }

    public function get_badgecount() {
        $count = 0;

        foreach ($this->folders as $folder) {
            $count += count($folder->get_badges());
        }

        return $count;
    }

    /**
     *
     * @param type $badgeid
     * @return boolean|obf_badge
     */
    public function get_badge($badgeid) {
        foreach ($this->folders as $folder) {
            foreach ($folder->get_badges() as $badge) {
                if ($badge->get_id() == $badgeid) {
                    return $badge;
                }
            }
        }

        return false;
    }

    /**
     * Converts the badges and folders into a format accepted by this class.
     *
     * @param array $tree The OBF-badges and -folders in a format given
     *      by the OBF API converted from JSON to an associative array:
     *
     *      [
     *          "/": ["folder1", ..., "folderN"],
     *          "badge1_id": [ "name": "foo", ... ],
     *          "badge2_id": [ "name": "bar", ... ]
     *      ]
     */
    private function folderify_badges($tree, $badges) {

//        var_dump($badges);

        if (!is_array($tree)) {
            return;
        }

        $emptyfoldercreated = false;

        // Are there any folders?
        if (array_key_exists('/', $tree)) {
            // Create the folders first.
            foreach ($tree['/'] as $folder) {
                $this->add_folder(new obf_badge_folder($folder));
            }

            // We don't need the folders when iterating through the badges.
            unset($tree['/']);
        }

        // Create the badge objects and add them to corresponding folder objects.
        foreach ($tree as $badgeid => $badgedata) {
            // Skip drafts.
            if ($badgedata['draft']) {
                continue;
            }

            $badgefolder = $badgedata['path'];

            if (empty($badgefolder) && !$emptyfoldercreated) {
                $this->add_folder(new obf_badge_folder(''));
                $emptyfoldercreated = true;
            }

            $badgedata = $this->findbadgedata($badgeid, $badges);
            $badge = $badgedata ? obf_badge::get_instance_from_array($badgedata) : obf_badge::get_instance($badgeid);
            $badge->set_folder($badgefolder);

            $this->get_folder($badge->get_folder())->add_badge($badge);
        }
    }

    private function findbadgedata($badgeid, $badges) {
        foreach ($badges as $badgedata) {
            if ($badgedata['id'] == $badgeid) {
                return $badgedata;
            }
        }

        return false;
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
            if ($folder->get_name() == $name) {
                return $folder;
            }
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
        foreach ($data as $folder) {
            $tree->add_folder($folder);
        }

        return $tree;
    }

}
