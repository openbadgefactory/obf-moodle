<?php

/**
 * Renderer for Open Badge Factory -plugin
 * 
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/form/issuance.php');

/**
 * HTML output renderer for local_obf-plugin
 */
class local_obf_renderer extends plugin_renderer_base {

    const BADGE_IMAGE_SIZE_SMALL = 32;
    const BADGE_IMAGE_SIZE_NORMAL = 100;

    /**
     * Renders the page where the user can issue a badge.
     * 
     * @param obf_badge $badge
     * @return type
     */
    public function page_issue(obf_badge $badge) {
        $html = $this->output->header();
        $html .= $this->print_issuer_wizard($badge);

        $this->page->requires->yui_module('moodle-local_obf-issuerwizard', 'M.local_obf.init_issuerwizard');
        $this->page->requires->strings_for_js(array('emailsubject'), 'local_obf');
        $html .= $this->output->footer();

        return $html;
    }

    /**
     * Renders the badge issuance history page.
     * 
     * @param obf_badge $badge
     * @param type $currentpage
     * @return type
     */
    public function page_history(obf_badge $badge = null, $currentpage = 0) {
        $html = $this->output->header();
        $html .= $this->print_badge_info_history($badge, $currentpage);
        $html .= $this->output->footer();

        return $html;
    }

    /**
     * Renders the page displaying the badge list.
     * 
     * @param type $reload
     * @return type
     */
    public function page_badgelist($reload = false) {
        $html = $this->output->header();
        $html .= $this->output->single_button(new moodle_url('badge.php', array('show' => 'list', 'reload' => 1)), get_string('updatebadges', 'local_obf'));

        try {
            $tree = obf_badge_tree::get_instance($reload);
            $html .= $this->render($tree);
        } catch (Exception $e) {
            $html .= $this->output->notification($e->getMessage(), 'notifyproblem');
        }

        $html .= $this->output->footer();

        return $html;
    }

    /**
     * Renders the page with the badge details.
     * 
     * @param obf_badge $badge
     * @param string $tab
     * @param type $page
     * @return type
     */
    public function page_badgedetails(obf_badge $badge, $tab = 'details', $page = 0) {
        $methodprefix = 'print_badge_info_';
        $rendererfunction = $methodprefix . $tab;
        $html = $this->output->header();

        if (!method_exists($this, $rendererfunction)) {
            $html .= $this->output->notification(get_string('invalidtab', 'local_obf'));
        } else {
            $html .= $this->output->heading($this->print_badge_image($badge) . ' ' .
                    $badge->get_name());
            $html .= $this->print_badge_tabs($badge->get_id(), $tab);
            $html .= call_user_func(array($this, $rendererfunction), $badge, $page);
        }

        $html .= $this->output->footer();

        return $html;
    }

    /**
     * Renders the page that displays the settings of the selected criteria type.
     * 
     * @param obf_badge $badge
     * @param type $criteriatype
     * @return type
     */
    /*
      public function page_criteriasettings(obf_badge $badge, $criteriatype) {
      // add a bit of security here before including any files
      $criteriatype = preg_replace('/[^a-z_]/', '', $criteriatype);
      $criteriaclass = 'obf_criteria_' . $criteriatype;
      $classfile = __DIR__ . '/class/criterion/' . $criteriatype . '.php';

      if (file_exists($classfile))
      require_once $classfile;

      $html = $this->output->header();

      if (!class_exists($criteriaclass)) {
      $html .= $this->output->notification(get_string('invalidcriteriatype', 'local_obf'));
      } else {
      $html .= $this->output->heading($this->print_badge_image($badge) .
      ' ' . $badge->get_name());
      $criteriaobj = new $criteriaclass();
      $html .= $criteriaobj->render($badge);

      foreach ($criteriaobj->get_yui_modules() as $module => $config) {
      $this->page->requires->yui_module($module, $config['init']);
      $this->page->requires->strings_for_js($config['strings'], 'local_obf');
      }
      }

      $html .= $this->output->footer();

      return $html;
      } */

    protected function render_obf_criterion_courseset(obf_criterion_courseset $criterion) {
        $badge = $criterion->get_badge();
        $html = $this->output->header();
        $html .= $this->output->heading($this->print_badge_image($badge) .
                ' ' . $badge->get_name());
        $html .= $criterion->render();
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
                    $name = html_writer::link(new moodle_url('/local/obf/badge.php', array('id' => $badge->get_id(), 'action' => 'show')), $badge->get_name());
                    $issuebutton = '';

                    if ($hasissuecapability) {
                        $issueurl = new moodle_url('/local/obf/issue.php', array('id' => $badge->get_id()));
                        $issuebutton = $this->output->single_button($issueurl, get_string('issuethisbadge', 'local_obf'), 'get');
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
        $html = '';
        $file = '/local/obf/criterion.php';
        $url = new moodle_url($file, array('badgeid' => $badge->get_id()));
        $options = array();

        foreach (obf_criterion_base::$CRITERIA_TYPES as $id => $type) {
            $options[$id] = get_string('criteriatype' . $type, 'local_obf');
        }

        $html .= html_writer::tag('label', get_string('addcriteria', 'local_obf'));
        $html .= $this->output->single_select($url, 'type', $options);

        $criteria = $badge->get_completion_criteria();

        foreach ($criteria as $id => $criterion) {
            $criteriontype = obf_criterion_base::$CRITERIA_TYPES[$criterion->get_type_id()];
            $groupname = get_string('criteriatype' . $criteriontype, 'local_obf');

            // icons
            $editurl = new moodle_url($file, array('badgeid' => $badge->get_id(),
                'action' => 'edit', 'id' => $id));
            $deleteurl = new moodle_url($file, array('badgeid' => $badge->get_id(),
                'action' => 'delete', 'id' => $id));
            $editaction = $this->output->action_icon($editurl, new pix_icon('t/edit', get_string('edit'), null, array('class' => 'obf-icon')));
            $deleteaction = $this->output->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete'), null, array('class' => 'obf-icon')));

            $method = $criterion->get_completion_method() == obf_criterion_base::CRITERIA_COMPLETION_ALL ? 'all' : 'any';
            $html .= $this->output->heading(html_writer::div($groupname . $editaction . $deleteaction), 3);
            $html .= html_writer::tag('p', get_string('criteriacompletedwhen' . $method, 'local_obf') . ':');
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
        $html = '';
        $historysize = count($history);

        if ($historysize == 0) {
            $html .= $this->output->notification(get_string('nohistory', 'local_obf'));
        } else {
            // paging settings
            $perpage = 10; // TODO: hard-coded here
            $url = $singlebadgehistory ? new moodle_url('badge.php', array('action' => 'show', 'id' => $badge->get_id(), 'show' => 'history')) : new moodle_url('badge.php', array('action' => 'history'));
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
                $expirationdate = $assertion->has_expiration_date() ? userdate($assertion->get_expires(), get_string('strftimedate')) : '-';
                $row = new html_table_row();

                // If we're watching the whole history (not just a single badge),
                // show the badge details in the table.
                if (!$singlebadgehistory) {
                    $b = $assertion->get_badge();
                    $url = new moodle_url('badge.php', array('action' => 'show', 'id' => $b->get_id()));
                    $row->cells[] = $this->print_badge_image($b, self::BADGE_IMAGE_SIZE_SMALL);
                    $row->cells[] = html_writer::link($url, $b->get_name());
                }

                // Map the assertion recipients to Moodle users
                $users = $history->get_assertion_users($assertion);
                $userlist = array();

                foreach ($users as $user) {
                    // TODO: handle case where the user doesn't exist in the
                    // Moodle database
                    $url = new moodle_url('/user/profile.php', array('id' => $user->id));
                    $userlist[] = html_writer::link($url, fullname($user)) . ' (' . $user->email . ')';
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
        $tabdata = array('details', 'criteria', 'history');
        $tabs = array();

        foreach ($tabdata as $tabname) {
            $url = new moodle_url('badge.php', array('id' => $badgeid, 'action' => 'show',
                'show' => $tabname));
            $tabs[] = new tabobject($tabname, $url, get_string('badge' . $tabname, 'local_obf'));
        }

        return $this->output->tabtree($tabs, $selectedtab);
    }

    public function print_issuer_wizard(obf_badge $badge) {

        $tabs = array(
            'preview' => get_string('previewbadge', 'local_obf'),
            'details' => get_string('badgedetails', 'local_obf'),
            'recipients' => get_string('selectrecipients', 'local_obf'),
            'message' => get_string('editemailmessage', 'local_obf'),
            'confirm' => get_string('confirmandissue', 'local_obf'));

        $issuerform = new obf_issuance_form(new moodle_url('/local/obf/issue.php?id=' . $badge->get_id()), array('badge' => $badge,
            'tabs' => $tabs, 'renderer' => $this));
        $output = '';

        if ($issuerform->is_submitted()) {
            if ($issuerform->is_validated()) {
                $issuance = $issuerform->get_issuance();
                $success = $issuance->process();

                if ($success) {
                    redirect(new moodle_url('badge.php', array('id' => $badge->get_id(),
                        'action' => 'show', 'show' => 'history')), get_string('badgeissued', 'local_obf'));
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
