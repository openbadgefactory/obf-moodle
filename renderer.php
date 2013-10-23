<?php

/**
 * Renderer for Open Badge Factory -plugin
 *
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/form/issuance.php');
require_once(__DIR__ . '/class/tree.php');
require_once(__DIR__ . '/form/emailtemplate.php');

/**
 * HTML output renderer for local_obf-plugin
 */
class local_obf_renderer extends plugin_renderer_base {

    const BADGE_IMAGE_SIZE_TINY = 22;
    const BADGE_IMAGE_SIZE_SMALL = 32;
    const BADGE_IMAGE_SIZE_NORMAL = 100;

    /**
     * Renders the badge issuance history page.
     *
     * @param obf_badge $badge
     * @param type $currentpage
     * @return type
     */
    public function page_history(obf_badge $badge = null, $currentpage = 0) {
        $html = $this->print_badge_info_history($badge, $currentpage);

        return $html;
    }

    public function render_badgelist(obf_badge_tree $tree, $hasissuecapability, $message = '') {
        $heading = $this->print_heading('badgelisttitle', 2);
//        $button = $this->output->single_button(new moodle_url('badge.php',
//                array('show' => 'list', 'reload' => 1)), get_string('updatebadges', 'local_obf'));
//        $html = html_writer::div($heading . $button, 'badgeheading');
        $html = html_writer::div($heading, 'badgeheading');

        if (!empty($message)) {
            $html .= $this->output->notification($message, 'notifysuccess');
        }

//        $html .= $this->render($tree);
        $html .= $this->render_obf_badge_tree($tree, $hasissuecapability);

        return $html;
    }

    public function render_badge_selector(obf_badge_tree $tree, moodle_url $issueurl) {
        $html = $this->print_heading('selectbadge', 2);

        if ($tree->get_badgecount() === 0) {
            $html .= $this->output->notification(get_string('nobadges', 'local_obf'));
        }
        else {
            foreach ($tree->get_folders() as $folder) {
                $name = $folder->has_name() ? $folder->get_name() : get_string('nofolder', 'local_obf');
                $html .= $this->output->heading($name, 3);
                $items = array();

                foreach ($folder->get_badges() as $badge) {
                    $url = clone $issueurl;
                    $url->param('id', $badge->get_id());
                    $items[] = html_writer::link($url, $this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_NORMAL)) .
                            html_writer::tag('p', html_writer::link($url, $badge->get_name()));
                }

                $html .= html_writer::alist($items, array('class' => 'badgelist'));
            }
        }

        return $html;
    }

    /**
     *
     * @param obf_assertion_collection $assertions
     * @return type
     */
    public function render_user_assertions(obf_assertion_collection $assertions) {
        $html = '';
        $items = array();

        for ($i = 0; $i < count($assertions); $i++) {
            $assertion = $assertions->get_assertion($i);
            $badge = $assertion->get_badge();
            $badgeimage = $this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_NORMAL);
            $url = new moodle_url('/local/obf/event.php', array('id' => $assertion->get_id()));
            $badgename = html_writer::tag('p', html_writer::link($url, $badge->get_name()),
                            array('class' => 'badgename'));
            $items[] = $badgeimage . $badgename;
        }

        $html .= html_writer::alist($items, array('class' => 'userbadges'));

        return $html;
    }

    public function render_assertion(obf_assertion $assertion) {
        $badge = $assertion->get_badge();
        $issuer = $badge->get_issuer();
        $html = $this->print_heading('issuancedetails', 2);
        $html .= $this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_NORMAL);
        $details = '';

        // issuer details
        $details .= $this->print_heading('issuerdetails');
        $details .= $this->render_definition_list(array(
            get_string('issuername', 'local_obf') => $issuer->get_name(),
            get_string('issuerurl', 'local_obf') => html_writer::link($issuer->get_url(),
                    $issuer->get_url())
        ));

        // badge details
        $details .= $this->print_heading('badgedetails');
        $details .= $this->render_definition_list(array(
            get_string('badgename', 'local_obf') => $badge->get_name(),
            get_string('badgedescription', 'local_obf') => $badge->get_description(),
            get_string('badgecriteriaurl', 'local_obf') => '-'
        ));

        // assertion details
        $details .= $this->print_heading('issuancedetails');
        $details .= $this->render_definition_list(array(
            get_string('evidence', 'local_obf') => '-'
        ));

        $html .= html_writer::div($details, 'obf-assertion-details');

        return html_writer::div($html, 'obf-assertion');
    }

    public function render_definition_list(array $items) {
        $arr = array();

        foreach ($items as $name => $value) {
            $arr[] = html_writer::tag('dt', $name) . html_writer::tag('dd', $value);
        }

        return html_writer::tag('dl', implode('', $arr), array('class' => 'obf-definition-list'));
    }

    /**
     * Renders the page with the badge details.
     *
     * @param obf_badge $badge
     * @param string $tab
     * @param type $page
     * @return type
     */
    public function page_badgedetails(obf_badge $badge, $tab = 'details', $page = 0, $message = '') {
        $methodprefix = 'print_badge_info_';
        $rendererfunction = $methodprefix . $tab;
        $html = '';

        if (!method_exists($this, $rendererfunction)) {
            $html .= $this->output->notification(get_string('invalidtab', 'local_obf'));
        } else {
            $heading = $this->output->heading($this->print_badge_image($badge) . ' ' .
                    $badge->get_name());
            $heading .=$this->output->single_button(new moodle_url('/local/obf/issue.php',
                    array('id' => $badge->get_id())), get_string('issuethisbadge', 'local_obf'),
                    'get');

            $html .= html_writer::div($heading, 'badgeheading');

            if (!empty($message)) {
                $html .= $this->output->notification($message, 'notifysuccess');
            }

            $html .= $this->print_badge_tabs($badge->get_id(), $tab);
            $html .= call_user_func(array($this, $rendererfunction), $badge, $page);
        }

        return $html;
    }

    protected function render_obf_criterion_form(obf_criterion_form $form) {
        $criterion = $form->get_criterion();
        $badge = $criterion->get_badge();
        $html = $this->output->header();
        $html .= $this->output->heading($this->print_badge_image($badge) .
                ' ' . $badge->get_name());
        $html .= $form->render();
        $html .= $this->output->footer();

        return $html;
    }

    protected function render_obf_criterion_deletion_form(obf_criterion_deletion_form $form) {
        $html = $this->output->header();
        $html .= $form->render();
        $html .= $this->output->footer();

        return $html;
    }

    /**
     * Renders the OBF-badge tree with badges categorized into folders
     *
     * @param obf_badge_tree $tree
     * @return string Returns the HTML-output.
     */
    protected function render_obf_badge_tree(obf_badge_tree $tree, $hasissuecapability) {
        $html = '';

        // No need to render the table if there aren't any folders/badges
        if (count($tree->get_folders())) {
            $table = new html_table();
            $table->head = array(
                get_string('badgeimage', 'local_obf'),
                get_string('badgename', 'local_obf'),
                get_string('badgecreated', 'local_obf'),
                get_string('badgeactions', 'local_obf')
            );
            $table->headspan = array(1, 1, 1, 1);

            foreach ($tree->get_folders() as $folder) {
                $foldername = $folder->has_name() ? $folder->get_name() : get_string('nofolder',
                                'local_obf');
                $header = new html_table_cell($foldername);
                $header->header = true;
                $header->colspan = array_sum($table->headspan);
                $headerrow = new html_table_row(array($header));
                $table->data[] = $headerrow;

                foreach ($folder->get_badges() as $badge) {
                    $img = $this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_SMALL);
                    $createdon = $badge->get_created();
                    $date = empty($createdon) ? '' : userdate($createdon, get_string('strftimedate'));
                    $name = html_writer::link(new moodle_url('/local/obf/badge.php',
                                    array('id' => $badge->get_id(), 'action' => 'show')),
                                    $badge->get_name());

                    $actions = '';

                    if ($hasissuecapability) {
                        $issueurl = new moodle_url('/local/obf/issue.php',
                                array('id' => $badge->get_id()));
                        $actions .= $this->output->action_icon($issueurl,
                                        new pix_icon('t/award',
                                        get_string('issuethisbadge', 'local_obf'))) . " ";
                    }

                    $row = array($img, $name, $date, $actions);
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
        return html_writer::empty_tag("img",
                        array("src" => $badge->get_image(), "width" => $width, "alt" => $badge->get_name()));
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
        $imgdiv = html_writer::div($this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_NORMAL),
                        'obf-badgeimage');
        $detailsdiv = html_writer::div(html_writer::tag('dl',
                                html_writer::tag('dt', get_string('badgename', 'local_obf')) .
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
        $createdon = $badge->get_created();
        $badgecreated = empty($createdon) ? '&amp;' : userdate($createdon,
                        get_string('strftimedate'));

        $boxes = html_writer::div($badgeimage, 'obf-badgeimage');
        $badgedetails = $this->print_heading('badgedetails');
        $badgedetails .= $this->render_definition_list(array(
            get_string('badgename', 'local_obf') => $badge->get_name(),
            get_string('badgedescription', 'local_obf') => $badge->get_description(),
            get_string('badgecreated', 'local_obf') => $badgecreated
        ));

        $issuer = $badge->get_issuer();
        $url = $issuer->get_url();
        $issuerurl = empty($url) ? '' : html_writer::link($url, $url);

        // issuer details table
        $badgedetails .= $this->print_heading('issuerdetails');
        $description = $issuer->get_description();
        $badgedetails .= $this->render_definition_list(array(
            get_string('issuername', 'local_obf') => $issuer->get_name(),
            get_string('issuerurl', 'local_obf') => $issuerurl,
            get_string('issuerdescription', 'local_obf') => empty($description) ? '-' : $description,
            get_string('issueremail', 'local_obf') => $issuer->get_email()
        ));
//        $issuertable = new html_table();
//        $issuertable->attributes['class'] = $tableclass;
//        $issuer = $badge->get_issuer();
//        $url = $issuer->get_url();
//        $issuerurl = empty($url) ? '' : html_writer::link($url, $url);
//        $issuertable->data[] = array(new obf_table_header('issuername'), $issuer->get_name());
//        $issuertable->data[] = array(new obf_table_header('issuerurl'), $issuerurl);
//        $issuertable->data[] = array(new obf_table_header('issuerdescription'), $issuer->get_description());
//        $issuertable->data[] = array(new obf_table_header('issueremail'), $issuer->get_email());
//        $badgedetails .= html_writer::table($issuertable);
        $boxes .= html_writer::div($badgedetails, 'obf-badgedetails');
        $html .= html_writer::div($boxes, 'obf-badgewrapper');

        return $html;
    }

    public function print_badge_info_criteria(obf_badge $badge) {
        $html = '';
        $file = '/local/obf/criterion.php';
        $url = new moodle_url($file, array('badgeid' => $badge->get_id(), 'action' => 'new'));
        $options = array();
        $criteriatypes = obf_criterion_base::$CRITERIA_TYPES;
        $criteria = $badge->get_completion_criteria();

        // Show only criteria types that aren't set yet
        foreach ($criteria as $criterion) {
            if (isset($criteriatypes[$criterion->get_type_id()])) {
                unset($criteriatypes[$criterion->get_type_id()]);
            }
        }

        // No need to show the dropdown if there aren't any criteria types
        if (count($criteriatypes) > 0) {
            foreach ($criteriatypes as $id => $type) {
                $options[$id] = get_string('criteriatype' . $type, 'local_obf');
            }

            $html .= html_writer::tag('label', get_string('addcriteria', 'local_obf'));
            $html .= $this->output->single_select($url, 'type', $options);
        }

        foreach ($criteria as $id => $criterion) {
            $criteriontype = obf_criterion_base::$CRITERIA_TYPES[$criterion->get_type_id()];
            $groupname = get_string('criteriatype' . $criteriontype, 'local_obf');

            // icons
            $editurl = new moodle_url($file,
                    array('badgeid' => $badge->get_id(),
                'action' => 'edit', 'id' => $id));
            $deleteurl = new moodle_url($file,
                    array('badgeid' => $badge->get_id(),
                'action' => 'delete', 'id' => $id));
            $editaction = $this->output->action_icon($editurl,
                    new pix_icon('t/edit', get_string('edit'), null, array('class' => 'obf-icon')));
            $deleteaction = $this->output->action_icon($deleteurl,
                    new pix_icon('t/delete', get_string('delete'), null,
                    array('class' => 'obf-icon')));

            $method = $criterion->get_completion_method() == obf_criterion_base::CRITERIA_COMPLETION_ALL
                        ? 'all' : 'any';
            $html .= $this->output->heading(html_writer::div($groupname . $editaction . $deleteaction),
                    3);
            $html .= html_writer::tag('p',
                            get_string('criteriacompletedwhen' . $method, 'local_obf') . ':');
            $attributes = $criterion->get_parsed_attributes();

            foreach ($attributes as $attribute) {
                $attributelist[] = $criterion->get_attribute_text($attribute);
            }

            $html .= html_writer::alist($attributelist);
        }

        return $html;
    }

    /**
     *
     * @param obf_badge $badge
     * @param type $currentpage
     * @return type
     */
    public function print_badge_info_history(obf_badge $badge = null, $currentpage = 0) {
        $singlebadgehistory = !is_null($badge);
        $history = $singlebadgehistory ? $badge->get_assertions() : obf_assertion::get_assertions();
        $historytable = new html_table();
        $historytable->attributes = array('class' => 'generaltable historytable');
        $html = $this->print_heading('history', 2);
        $historysize = count($history);

        if ($historysize == 0) {
            $html .= $this->output->notification(get_string('nohistory', 'local_obf'), 'generalbox');
        } else {
            // paging settings
            $perpage = 10; // TODO: hard-coded here
            $path = '/local/obf/badge.php';
            $url = $singlebadgehistory ? new moodle_url($path,
                    array('action' => 'show', 'id' => $badge->get_id(), 'show' => 'history')) : new moodle_url($path,
                    array('action' => 'history'));
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
                $expirationdate = $assertion->has_expiration_date() ? userdate($assertion->get_expires(),
                                get_string('strftimedate')) : '-';
                $row = new html_table_row();

                // If we're watching the whole history (not just a single badge),
                // show the badge details in the table.
                if (!$singlebadgehistory) {
                    $b = $assertion->get_badge();
                    $url = new moodle_url($path, array('action' => 'show', 'id' => $b->get_id()));
                    $row->cells[] = $this->print_badge_image($b, self::BADGE_IMAGE_SIZE_TINY);
                    $row->cells[] = html_writer::link($url, s($b->get_name()));
                }

                // Map the assertion recipients to Moodle users
                $users = $history->get_assertion_users($assertion);
                $userlist = array();

                foreach ($users as $user) {
                    // TODO: handle case where the user doesn't exist in the
                    // Moodle database
                    $url = new moodle_url('/user/view.php', array('id' => $user->id));
                    $userlist[] = html_writer::link($url, fullname($user),
                                    array('title' => $user->email));
                }

                $row->cells[] = html_writer::div(implode(', ', $userlist), 'recipientlist');
                $row->cells[] = userdate($assertion->get_issuedon(), get_string('strftimedate'));
                $row->cells[] = $expirationdate;
                $row->cells[] = html_writer::link(new moodle_url('/local/obf/event.php',
                                array('id' => $assertion->get_id())),
                                get_string('showassertion', 'local_obf'));
                $historytable->data[] = $row;
            }

            $html .= $htmlpager;
            $html .= html_writer::table($historytable);
            $html .= $htmlpager;

            $this->page->requires->yui_module('moodle-local_obf-historyenhancer',
                    'M.local_obf.init_historyenhancer');
            $this->page->requires->string_for_js('showmorerecipients', 'local_obf');
        }

        return $html;
    }

    public function print_badge_tabs($badgeid, $selectedtab = 'details') {
        $tabdata = array('details', 'criteria', 'email', 'history');
        $tabs = array();

        foreach ($tabdata as $tabname) {
            $url = new moodle_url('badge.php',
                    array('id' => $badgeid, 'action' => 'show',
                'show' => $tabname));
            $tabs[] = new tabobject($tabname, $url, get_string('badge' . $tabname, 'local_obf'));
        }

        return $this->output->tabtree($tabs, $selectedtab);
    }

    public function render_obf_config_form(obf_config_form $form) {
        $html = $this->print_heading('settings', 2);
        $html .= $form->render();

        return $html;
    }

    public function render_badge_exporter(obf_badge_export_form $form) {
        $html = $this->print_heading('badgeexport', 2);
        $html .= $form->render();

        return $html;
    }
}

class local_obf_badge_renderer extends plugin_renderer_base {

    public function tabs($badgeid, $selectedtab = 'details') {
        $tabdata = array('details', 'criteria', 'email', 'history');
        $tabs = array();

        foreach ($tabdata as $tabname) {
            $url = new moodle_url('/local/obf/badge.php',
                    array('id' => $badgeid, 'action' => 'show',
                'show' => $tabname));
            $tabs[] = new tabobject($tabname, $url, get_string('badge' . $tabname, 'local_obf'));
        }

        return $this->output->tabtree($tabs, $selectedtab);
    }

    public function page(obf_badge $badge, $tab, $content) {
        $html = $this->output->heading($this->badge_image($badge) . ' ' .
                $badge->get_name());
        $html .= $this->tabs($badge->get_id(), $tab);
        $html .= $content;

        return $html;
    }

    /**
     * Generates the HTML for the badge image.
     *
     * @param obf_badge $badge The badge object
     * @param int $width The width of the image
     * @return string The img-tag
     */
    public function badge_image(obf_badge $badge,
            $width = local_obf_renderer::BADGE_IMAGE_SIZE_SMALL) {
        return html_writer::empty_tag("img",
                        array("src" => $badge->get_image(), "width" => $width, "alt" => $badge->get_name()));
    }

}

class obf_table_header extends html_table_cell {

    public function __construct($stringid = null) {
        $this->header = true;
        parent::__construct(is_null($stringid) ? null : get_string($stringid, 'local_obf'));
    }

}