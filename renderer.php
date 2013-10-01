<?php

/**
 * Rendered for Open Badge Factory -plugin
 * 
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/forms.php');

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
            $table->headspan = array(1, 1, 2);
            $hasissuecapability = has_capability('local/obf:issuebadge', context_system::instance());

            foreach ($tree->get_folders() as $folder) {
                $foldername = $folder->has_name() ? $folder->get_name() : get_string('nofolder', 'local_obf');
                $header = new html_table_cell($foldername);
                $header->header = true;
                $header->colspan = array_sum($table->headspan);
                $headerrow = new html_table_row(array($header));
                $table->data[] = $headerrow;

                foreach ($folder->get_badges() as $badge) {
                    $img = $this->print_badge_image($badge, 32);
                    $createdon = $badge->get_created();
                    $date = empty($createdon) ? '' : userdate($createdon);
                    $name = html_writer::link(new moodle_url('/local/obf/badgedetails.php', array('id' => $badge->get_id())), $badge->get_name());
                    $issuebutton = '';

                    if ($hasissuecapability) {
                        $issuebutton = $this->output->single_button(new moodle_url('/local/obf/issue.php', array('id' => $badge->get_id())), get_string('issuethisbadge', 'local_obf'), 'get');
                    }

                    $row = array($img, $name, $date, $issuebutton);
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

    public function print_badge_image(obf_badge $badge, $width = 32) {
        return html_writer::empty_tag("img", array("src" => $badge->get_image(), "width" => $width, "alt" => $badge->get_name()));
    }

    /**
     * 
     * @param obf_badge $badge
     * @return string
     */
    public function print_badge_details(obf_badge $badge) {
        $html = '';
        $badgeimage = $this->print_badge_image($badge, 140);
        $tableclass = 'generaltable obf-table';

        // badge details table
        $badgetable = new html_table();
        $badgetable->attributes['class'] = $tableclass;
        $createdon = $badge->get_created();
        $badgecreated = empty($createdon) ? '&amp;' : userdate($createdon);

        $badgetable->data[] = array(new obf_table_header('badgename'), $badge->get_name());
        $badgetable->data[] = array(new obf_table_header('badgedescription'), $badge->get_description());
        $badgetable->data[] = array(new obf_table_header('badgecreated'), $badgecreated);
        $badgetable->data[] = array(new obf_table_header('badgecriteriaurl'), html_writer::link($badge->get_criteria(), $badge->get_criteria()));

        $boxes = html_writer::div($badgeimage, 'obf-badgeimage');
        $badgedetails = $this->output->heading(get_string('badgedetails', 'local_obf'), 3);
        $badgedetails .= html_writer::table($badgetable);

        // issuer details table
        $badgedetails .= $this->output->heading(get_string('issuerdetails', 'local_obf'), 3);
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

    public function print_badge_criteria(obf_badge $badge) {
        return html_writer::tag('p', 'TODO');
    }

    public function print_badge_tabs($badgeid, $selectedtab = 'details') {
        $tabs = array();
        $tabs[] = new tabobject('details', new moodle_url('/local/obf/badgedetails.php', array('id' => $badgeid)), get_string('badgedetails', 'local_obf'));
        $tabs[] = new tabobject('criteria', new moodle_url('/local/obf/badgedetails.php', array('id' => $badgeid, 'show' => 'criteria')), get_string('badgecriteria', 'local_obf'));

        return $this->output->tabtree($tabs, $selectedtab);
    }

    public function print_issuer_wizard(obf_badge $badge) {

        $tabs = array(
            'preview' => get_string('previewbadge', 'local_obf'),
            'details' => get_string('badgedetails', 'local_obf'),
            'recipients' => get_string('selectrecipients', 'local_obf'),
            'message' => get_string('editemailmessage', 'local_obf'),
            'confirm' => get_string('confirmandissue', 'local_obf'));

        $issuerform = new badge_issuer_form(null, array('badge' => $badge,
            'tabs' => $tabs, 'renderer' => $this));

        return $issuerform->render();
    }

}

class obf_table_header extends html_table_cell {

    public function __construct($stringid = null) {
        $this->header = true;
        $this->text = is_null($stringid) ? null : get_string($stringid, 'local_obf');
    }

}

?>
