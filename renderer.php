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
        $tableclass = 'generaltable obf-table';
        
        // badge details table
        $badgetable = new html_table();
        $badgetable->attributes['class'] = $tableclass;
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
        $issuertable->attributes['class'] = $tableclass;
        
        $issuer = $badge->get_issuer();
        $url = $issuer->get_url();
        $issuerurl = empty($url) ? '' : html_writer::link($url, $url);
        $issuertable->data[] = array(new obf_table_header('issuername'), $issuer->get_name());
        $issuertable->data[] = array(new obf_table_header('issuerurl'), $issuerurl);
        $issuertable->data[] = array(new obf_table_header('issuerdescription'), $issuer->get_description());
        $issuertable->data[] = array(new obf_table_header('issueremail'), $issuer->get_email());

        $badgedetails .= html_writer::table($issuertable);
        $boxes .= html_writer::div($badgedetails, 'obf-badgedetails');
        $html .= html_writer::div($boxes, 'obf-badgewrapper');

        return $html;
    }

}

class obf_table_header extends html_table_cell {

    public function __construct($stringid = null) {
        $this->header = true;
        $this->text = is_null($stringid) ? null : get_string($stringid, 'local_obf');
    }

}

?>
