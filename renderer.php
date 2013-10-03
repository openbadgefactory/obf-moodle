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

    const BADGE_IMAGE_SIZE_SMALL = 32;
    const BADGE_IMAGE_SIZE_NORMAL = 100;

    /**
     * Renders the OBF-badge tree with badges categorized into folders
     * 
     * @param obf_badge_tree $tree
     * @return string Returns the HTML-output.
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
                    $img = $this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_SMALL);
                    $createdon = $badge->get_created();
                    $date = empty($createdon) ? '' : userdate($createdon, get_string('strftimedate'));
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

    /**
     * Generates the HTML for the badge image.
     * 
     * @param obf_badge $badge The badge object
     * @param int $width The width of the image
     * @return string The img-tag
     */
    public function print_badge_image(obf_badge $badge, $width = self::BADGE_IMAGE_SIZE_SMALL) {
        return html_writer::empty_tag("img", array("src" => $badge->get_image(), "width" => $width, "alt" => $badge->get_name()));
    }

    /**
     * Generates the HTML for a heading.
     * 
     * @param string $id The string id in the module's language file.
     * @param int $level The heading level.
     * @return string The hX-tag
     */
    public function print_heading($id, $level = 3) {
        return $this->output->heading(get_string($id, 'local_obf'), $level);
    }

    /**
     * Generates the HTML for the badge teaser -component. The component
     * contains the badge image, the name of the badge and the description.
     * 
     * @param obf_badge $badge The badge object.
     * @return string The HTML markup.
     */
    public function print_badge_teaser(obf_badge $badge) {
        $html = $this->print_heading('badgedetails');
        $imgdiv = html_writer::div($this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_NORMAL), 'obf-badgeimage');
        $detailsdiv = html_writer::div(html_writer::tag('dl', html_writer::tag('dt', get_string('badgename', 'local_obf')) .
                                html_writer::tag('dd', $badge->get_name()) .
                                html_writer::tag('dt', get_string('badgedescription', 'local_obf')) .
                                html_writer::tag('dd', $badge->get_description())));
        $html .= html_writer::div($imgdiv . $detailsdiv, 'obf-badgeteaser');

        return $html;
    }

    /**
     * 
     * @param obf_badge $badge
     * @return string
     */
    public function print_badge_info_details(obf_badge $badge) {
        $html = '';
        $badgeimage = $this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_NORMAL);
        $tableclass = 'generaltable obf-table';

        // badge details table
        $badgetable = new html_table();
        $badgetable->attributes['class'] = $tableclass;
        $createdon = $badge->get_created();
        $badgecreated = empty($createdon) ? '&amp;' : userdate($createdon, get_string('strftimedate'));

        $badgetable->data[] = array(new obf_table_header('badgename'), $badge->get_name());
        $badgetable->data[] = array(new obf_table_header('badgedescription'), $badge->get_description());
        $badgetable->data[] = array(new obf_table_header('badgecreated'), $badgecreated);
        $badgetable->data[] = array(new obf_table_header('badgecriteriaurl'), html_writer::link($badge->get_criteria(), $badge->get_criteria()));

        $boxes = html_writer::div($badgeimage, 'obf-badgeimage');
        $badgedetails = $this->print_heading('badgedetails');
        $badgedetails .= html_writer::table($badgetable);

        // issuer details table
        $badgedetails .= $this->print_heading('issuerdetails');
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

    public function print_badge_info_criteria(obf_badge $badge) {
        return html_writer::tag('p', 'TODO');
    }

    public function print_badge_info_history(obf_badge $badge = null, $currentpage = 0) {
        $singlebadgehistory = !is_null($badge);
        $history = $singlebadgehistory ? $badge->get_assertions() : obf_issuance::get_assertions();
        $historytable = new html_table();
        $html = '';
        $historysize = count($history);

        if ($historysize == 0) {
            $html .= $this->output->notification(get_string('nohistory', 'local_obf'));
        } else {
            // paging settings
            $perpage = 10; // TODO: hard-coded here
            $url = $singlebadgehistory ? new moodle_url('badgedetails.php', array('id' => $badge->get_id(), 'show' => 'history')) : new moodle_url('history.php');
            $pager = new paging_bar($historysize, $currentpage, $perpage, $url, 'page');
            $htmlpager = $this->render($pager);
            $startindex = $currentpage * $perpage;
            $endindex = $startindex + $perpage > $historysize ? $historysize : $startindex + $perpage;

            // heading row
            $headingrow = array();

            if (!$singlebadgehistory) {
                $headingrow[] = new obf_table_header('badgename');
                $historytable->headspan = array(2, 1, 1, 1);
            }

            $headingrow[] = new obf_table_header('recipients');
            $headingrow[] = new obf_table_header('issuedon');
            $headingrow[] = new obf_table_header('expiresby');
            $historytable->head = $headingrow;

            // add history rows
            for ($i = $startindex; $i < $endindex; $i++) {
                $assertion = $history->get_assertion($i);
                $expirationdate = $assertion->get_badge()->has_expiration_date()
                        ? userdate($assertion->get_badge()->get_expires(), get_string('strftimedate'))
                        : '-';
                $row = new html_table_row();

                // If we're watching the whole history (not just a single badge),
                // show the badge details in the table.
                if (!$singlebadgehistory) {
                    $url = new moodle_url('badgedetails.php', array('id' => $assertion->get_badge()->get_id()));
                    $row->cells[] = $this->print_badge_image($assertion->get_badge(), self::BADGE_IMAGE_SIZE_SMALL);
                    $row->cells[] = html_writer::link($url, $assertion->get_badge()->get_name());
                }

                // Map the assertion recipients to Moodle users
                $users = $history->get_assertion_users($assertion);
                $userlist = array();
                
                foreach ($users as $user) {
                    // TODO: handle case where the user doesn't exist in the
                    // Moodle database
                    $url = new moodle_url('/user/profile.php', array('id' => $user->id));
                    $userlist[] = html_writer::link($url, $user->firstname . ' ' .
                            $user->lastname) . ' (' . $user->email . ')';
                }
                
                $row->cells[] = html_writer::alist($userlist);
                $row->cells[] = userdate($assertion->get_issuedon(), get_string('strftimedate'));
                $row->cells[] = $expirationdate;
                $historytable->data[] = $row;
            }

            $html .= $htmlpager;
            $html .= html_writer::table($historytable);
            $html .= $htmlpager;
        }

        return $html;
    }

    public function print_badge_tabs($badgeid, $selectedtab = 'details') {
        $tabs = array();
        $tabs[] = new tabobject('details', new moodle_url('/local/obf/badgedetails.php', array('id' => $badgeid)), get_string('badgedetails', 'local_obf'));
        $tabs[] = new tabobject('criteria', new moodle_url('/local/obf/badgedetails.php', array('id' => $badgeid, 'show' => 'criteria')), get_string('badgecriteria', 'local_obf'));
        $tabs[] = new tabobject('history', new moodle_url('badgedetails.php', array('id' => $badgeid, 'show' => 'history')), get_string('badgehistory', 'local_obf'));

        return $this->output->tabtree($tabs, $selectedtab);
    }

    public function print_issuer_wizard(obf_badge $badge) {

        $tabs = array(
            'preview' => get_string('previewbadge', 'local_obf'),
            'details' => get_string('badgedetails', 'local_obf'),
            'recipients' => get_string('selectrecipients', 'local_obf'),
            'message' => get_string('editemailmessage', 'local_obf'),
            'confirm' => get_string('confirmandissue', 'local_obf'));

        $issuerform = new badge_issuer_form(new moodle_url('/local/obf/issue.php?id=' . $badge->get_id()), array('badge' => $badge,
            'tabs' => $tabs, 'renderer' => $this));
        $output = '';

        if ($issuerform->is_submitted()) {
            if ($issuerform->is_validated()) {
                $issuance = $issuerform->get_issuance();
                $success = $issuance->process();

                if ($success) {
                    redirect(new moodle_url('/local/obf/badgedetails.php', array('id' => $badge->get_id(), 'show' => 'history')), get_string('badgeissued', 'local_obf'));
                } else {
                    $output .= $this->output->notification('Badge issuance failed. Reason: ' . $issuance->get_error());
                }
            } else {
                $output .= $this->output->notification('Validation failed!'); // TODO: why?
            }
        }

        $output .= $issuerform->render();

        return $output;
    }

}

class obf_table_header extends html_table_cell {

    public function __construct($stringid = null) {
        $this->header = true;
        parent::__construct(is_null($stringid) ? null : get_string($stringid, 'local_obf'));
    }

}

?>
