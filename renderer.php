<?php

/**
 * Rendered for Open Badge Factory -plugin
 * 
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * HTML output renderer for local_obf-plugin
 */
class local_obf_renderer extends plugin_renderer_base {

    /**
     * Renders the OBF-badge tree with badges categorized into folders
     * 
     * @param obf_badge_tree $tree
     * @return string Returns the HTML-output
     */
    protected function render_obf_badge_tree(obf_badge_tree $tree) {
        $html = '';

        // No need to render the table if there aren't any folders/badges
        if (count($tree->get_folders())) {
            $table = new html_table();
            $table->head = array(
                get_string('badgeimage', 'local_obf'),
                get_string('badgename', 'local_obf'),
                get_string('badgecreated', 'local_obf')
            );

            foreach ($tree->get_folders() as $folder) {
                $foldername = $folder->has_name() ? $folder->get_name() : get_string('nofolder', 'local_obf');
                $header = new html_table_cell($foldername);
                $header->header = true;
                $header->colspan = count($table->head);
                $headerrow = new html_table_row(array($header));
                $table->data[] = $headerrow;

                foreach ($folder->get_badges() as $badge) {
                    $attributes = array('src' => $badge->get_image(), 'alt' => s($badge->get_name()), 'width' => 22);
                    $img = html_writer::empty_tag('img', $attributes);
                    $createdon = $badge->get_created();
                    $date = empty($createdon) ? '' : userdate($createdon);
                    $name = html_writer::link(new moodle_url('/local/obf/badgedetails.php', array('id' => $badge->get_id())), $badge->get_name());

                    $row = array($img, $name, $date);
                    $table->data[] = $row;
                }
            }

            $htmltable = html_writer::table($table);
            $html .= $htmltable;
        } else {
            $html .= $this->output->notification(get_string('nobadges', 'local_obf'), 'notifynotice');
        }

        return $html;
    }

    /**
     * 
     * @param obf_badge $badge
     * @return string
     */
    public function print_badge_details(obf_badge $badge) {
        $html = '';
        $badgeimage = html_writer::empty_tag("img", array("src" => $badge->get_image(), "width" => 140));
        
        // badge details table
        $badgetable = new html_table();
        $createdon = $badge->get_created();
        $badgecreated = empty($createdon) ? '&amp;' : userdate($createdon);

        $badgetable->data[] = array(new obf_table_header('badgename'), $badge->get_name());
        $badgetable->data[] = array(new obf_table_header('badgedescription'), $badge->get_description());
        $badgetable->data[] = array(new obf_table_header('badgecreated'), $badgecreated);
        $badgetable->data[] = array(new obf_table_header('badgecriteria'), html_writer::link($badge->get_criteria(), $badge->get_criteria()));

        $boxes = html_writer::div($badgeimage, 'obf-badgeimage');
        $badgedetails = $this->output->heading(get_string('badgedetails', 'local_obf'));
        $badgedetails .= html_writer::table($badgetable);

        // issuer details table
        $badgedetails .= $this->output->heading(get_string('issuerdetails', 'local_obf'));
        $issuertable = new html_table();
        $issuer = $badge->get_issuer();
        $issuertable->data[] = array(new obf_table_header('issuername'), $issuer->get_name());
        $issuertable->data[] = array(new obf_table_header('issuerurl'), $issuer->get_url());
        $issuertable->data[] = array(new obf_table_header('issuerdescription'), $issuer->get_description());
        $issuertable->data[] = array(new obf_table_header('issueremail'), $issuer->get_email());

        $badgedetails .= html_writer::table($issuertable);
        $boxes .= html_writer::div($badgedetails, 'obf-badgedetails');
        $html .= html_writer::div($boxes, 'obf-badgewrapper');

        return $html;
    }

}

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

class obf_table_header extends html_table_cell {

    public function __construct($stringid = null) {
        $this->header = true;
        $text = is_null($stringid) ? null : get_string($stringid, 'local_obf');
        parent::__construct($text);
    }

}

?>
