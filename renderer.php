<?php

/**
 * Renderer for Open Badge Factory -plugin
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/form/issuance.php');
require_once(__DIR__ . '/class/tree.php');
require_once(__DIR__ . '/class/criterion/criterion.php');
require_once(__DIR__ . '/form/emailtemplate.php');
require_once(__DIR__ . '/form/coursecriterion.php');

/**
 * HTML output renderer for local_obf-plugin
 */
class local_obf_renderer extends plugin_renderer_base {

    const BADGE_IMAGE_SIZE_TINY = 22;
    const BADGE_IMAGE_SIZE_SMALL = 32;
    const BADGE_IMAGE_SIZE_NORMAL = 100;

    /**
     * Renders the list of badges in course context
     *
     * @param obf_badge_tree $tree
     * @param type $hasissuecapability
     * @param context $context
     * @param type $message
     * @return type
     */
    public function render_badgelist_course(obf_badge_tree $tree, $hasissuecapability,
            context $context, $message = '') {
        $html = '';

        if (!empty($message)) {
            $html .= $this->output->notification($message, 'notifysuccess');
        }

        $badgesincourse = obf_badge::get_badges_in_course($context->instanceid);
        $html .= $this->print_heading('coursebadgelisttitle', 2);

        if (count($badgesincourse) == 0) {
            $html .= $this->output->notification(get_string('nobadgesincourse', 'local_obf'));
        } else {
            $table = new html_table();
            $table->head = array(
                get_string('badgeimage', 'local_obf'),
                get_string('badgename', 'local_obf'),
                get_string('badgecreated', 'local_obf'),
                get_string('badgeactions', 'local_obf')
            );
            $table->headspan = array(1, 1, 1, 1);

            foreach ($badgesincourse as $badge) {
                $table->data[] = $this->render_badge_row($badge, $hasissuecapability, $context);
            }

            $html .= html_writer::table($table);
        }

        $html .= $this->render_badgelist($tree, $hasissuecapability, $context);

        return $html;
    }

    /**
     * Renders the list of badges.
     *
     * @param obf_badge_tree $tree
     * @param type $hasissuecapability
     * @param context $context
     * @param type $message
     * @return type
     */
    public function render_badgelist(obf_badge_tree $tree, $hasissuecapability, context $context,
            $message = '') {
        $html = $this->print_heading('badgelisttitle', 2);

        if (!empty($message)) {
            $html .= $this->output->notification($message, 'notifysuccess');
        }

        $html .= $this->render_obf_badge_tree($tree, $hasissuecapability, $context);

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
            $url = $badge->get_criteria_url();
            $badgename = html_writer::tag('p', html_writer::link($url, $badge->get_name()),
                            array('class' => 'badgename'));
            $items[] = $badgeimage . $badgename;
        }

        $html .= html_writer::alist($items, array('class' => 'userbadges'));

        return $html;
    }

    /**
     * Renders a single badge assertion.
     *
     * @param obf_assertion $assertion
     * @return type
     */
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

        $html .= obf_html::div($details, 'obf-assertion-details');

        return obf_html::div($html, 'obf-assertion');
    }

    /**
     * Renders a definition list.
     *
     * @param array An array of items to render (key-value -pairs)
     * @return string Returns the html-markup
     */
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
     * @param context $context
     * @param type $tab
     * @param type $page
     * @param type $message
     * @return type
     */
    public function page_badgedetails(obf_badge $badge, context $context, $tab = 'details',
            $page = 0, $message = '') {
        $methodprefix = 'print_badge_info_';
        $rendererfunction = $methodprefix . $tab;
        $html = '';

        if (!method_exists($this, $rendererfunction)) {
            $html .= $this->output->notification(get_string('invalidtab', 'local_obf'));
        } else {
            if (!empty($message)) {
                $html .= $this->output->notification($message, 'notifysuccess');
            }

            $html .= $this->print_badge_tabs($badge->get_id(), $context, $tab);
            $html .= call_user_func(array($this, $rendererfunction), $badge, $context, $page);
        }

        return $html;
    }

    /**
     * Renders the heading element in badge-details -page.
     *
     * @param obf_badge $badge
     * @param context $context
     * @return type
     */
    public function render_badge_heading(obf_badge $badge, context $context) {
        $heading = $this->output->heading($this->print_badge_image($badge) . ' ' .
                $badge->get_name());
        $issueurl = new moodle_url('/local/obf/issue.php', array('id' => $badge->get_id()));

        if ($context instanceof context_course) {
            $issueurl->param('courseid', $context->instanceid);
        }

        $heading .= $this->output->single_button($issueurl,
                get_string('issuethisbadge', 'local_obf'), 'get');

        return obf_html::div($heading, 'badgeheading');
    }

    // Renders the criterion-form
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

    // Renders the criterion deletion form.
    protected function render_obf_criterion_deletion_form(obf_criterion_deletion_form $form) {
        $html = $this->output->header();
        $html .= $form->render();
        $html .= $this->output->footer();

        return $html;
    }

    /**
     * Renders the OBF-badge tree with badges categorized into folders.
     *
     * @param obf_badge_tree $tree
     * @param type $hasissuecapability
     * @param context $context
     * @return type
     */
    protected function render_obf_badge_tree(obf_badge_tree $tree, $hasissuecapability,
            context $context) {
        $html = '';

        // No need to render the table if there aren't any folders/badges
        if (count($tree->get_folders()) > 0) {
            $table = new html_table();
            $table->head = array(
                get_string('badgeimage', 'local_obf'),
                get_string('badgename', 'local_obf'),
                get_string('badgecreated', 'local_obf'),
                get_string('badgeactions', 'local_obf')
            );
            $table->headspan = array(1, 1, 1, 1);

            foreach ($tree->get_folders() as $folder) {
                $this->render_badge_folder($table, $folder, $hasissuecapability, $context);
            }

            $html .= html_writer::table($table);
        } else {
            $html .= $this->output->notification(get_string('nobadges', 'local_obf'), 'notifynotice');
        }

        return $html;
    }

    /**
     * Renders a single badge folder in a tree.
     *
     * @param type $table
     * @param obf_badge_folder $folder
     * @param type $hasissuecapability
     * @param context $context
     */
    private function render_badge_folder(&$table, obf_badge_folder $folder, $hasissuecapability,
            context $context) {
        $foldername = $folder->has_name() ? $folder->get_name() : get_string('nofolder', 'local_obf');
        $header = new html_table_cell($foldername);
        $header->header = true;
        $header->colspan = array_sum($table->headspan);
        $headerrow = new html_table_row(array($header));
        $table->data[] = $headerrow;

        foreach ($folder->get_badges() as $badge) {
            $table->data[] = $this->render_badge_row($badge, $hasissuecapability, $context);
        }
    }

    /**
     * Renders a single badge row in table of badges.
     *
     * @param obf_badge $badge
     * @param type $hasissuecapability
     * @param context $context
     * @return type
     */
    private function render_badge_row(obf_badge $badge, $hasissuecapability, context $context) {
        $img = $this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_SMALL);
        $createdon = $badge->get_created();
        $date = empty($createdon) ? '' : userdate($createdon, get_string('strftimedate'));
        $url = new moodle_url('/local/obf/badge.php',
                array('id' => $badge->get_id(), 'action' => 'show'));

        if ($context instanceof context_course) {
            $url->param('courseid', $context->instanceid);
        }

        $name = html_writer::link($url, s($badge->get_name()));
        $actions = '';

        if ($hasissuecapability) {
            $issueurl = new moodle_url('/local/obf/issue.php', array('id' => $badge->get_id()));

            if ($context instanceof context_course) {
                $issueurl->param('courseid', $context->instanceid);
            }

            $actions .= html_writer::link($issueurl, get_string('issue', 'local_obf'));
//            $actions .= $this->output->action_icon($issueurl,
//                            new pix_icon('t/award', get_string('issuethisbadge', 'local_obf'))) . " ";
        }

        $row = array($img, $name, $date, $actions);

        return $row;
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
//    public function print_badge_teaser(obf_badge $badge) {
//        $html = $this->print_heading('badgedetails');
//        $imgdiv = obf_html::div($this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_NORMAL),
//                        'obf-badgeimage');
//        $detailsdiv = obf_html::div(html_writer::tag('dl',
//                                html_writer::tag('dt', get_string('badgename', 'local_obf')) .
//                                html_writer::tag('dd', $badge->get_name()) .
//                                html_writer::tag('dt', get_string('badgedescription', 'local_obf')) .
//                                html_writer::tag('dd', $badge->get_description())));
//        $html .= obf_html::div($imgdiv . $detailsdiv, 'obf-badgeteaser');
//
//        return $html;
//    }

    /**
     * Renders the badge and issuer details.
     *
     * @param obf_badge $badge
     * @param context $context
     * @return type
     */
    public function print_badge_info_details(obf_badge $badge, context $context) {
        $html = '';
        $badgeimage = $this->print_badge_image($badge, self::BADGE_IMAGE_SIZE_NORMAL);
        $createdon = $badge->get_created();
        $badgecreated = empty($createdon) ? '&amp;' : userdate($createdon,
                        get_string('strftimedate'));

        $boxes = obf_html::div($badgeimage, 'obf-badgeimage');
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

        $boxes .= obf_html::div($badgedetails, 'obf-badgedetails');
        $html .= obf_html::div($boxes, 'obf-badgewrapper');

        return $html;
    }

    /**
     * Renders the badge criteria page.
     *
     * @param obf_badge $badge
     * @param context $context
     * @return type
     */
    public function print_badge_info_criteria(obf_badge $badge, context $context) {
        $html = '';

        if ($context instanceof context_course) {
            $html .= $this->render_badge_criteria_course($badge, $context->instanceid);
        } else {
            $html .= $this->render_badge_criteria_site($badge);
        }

        return $html;
    }

    /**
     * Renders the form for modifying the criteria of a single course
     *
     * @param obf_badge $badge
     * @param type $courseid
     * @return type
     */
    public function render_badge_criteria_course(obf_badge $badge, $courseid) {
        $html = '';
//        $course = get_course($courseid);
        $criteria = $badge->get_completion_criteria();
        $coursewithcriterion = null;
        $criterioncourseid = null;
        $courseincriterion = false;
        $error = '';
        $url = new moodle_url('/local/obf/badge.php',
                array('id' => $badge->get_id(), 'action' =>
            'show', 'show' => 'criteria', 'courseid' => $courseid));

        // Show edit form if there aren't any criteria related to this badge or there is only one
        // which hasn't been met yet by any user.
        foreach ($criteria as $criterion) {
            $courses = $criterion->get_items();
            $courseincriterion = $criterion->has_course($courseid);

            // If the course is already related to a criterion containing other courses also,
            // the criterion cannot be modified in course context.
            if ($courseincriterion) {
                $coursewithcriterion = $criterion;

                if (count($courses) > 1) {
                    $error = 'coursealreadyincriterion';
                } else if ($criterion->is_met()) {
                    $error = 'cannoteditcriterion';
                } else {
                    $criterioncourseid = $courses[0]->get_id();
                }

                break;
            }
        }

        $canedit = !is_null($criterioncourseid) || !$courseincriterion;

        // The criteria cannot be modified or added -> show a message to user
        if (!$canedit) {
            if (!is_null($coursewithcriterion) && $coursewithcriterion->is_met()) {
                $items = $coursewithcriterion->get_items();
                $html .= html_writer::tag('p', $items[0]->get_text_for_single_course());
            }

            $html .= $this->output->notification(get_string($error, 'local_obf'));
        }

        // Show the course criteria form
        else {
            $criterioncourse = is_null($criterioncourseid) ? new obf_criterion_course : obf_criterion_course::get_instance($criterioncourseid);
            $criterionform = new obf_coursecriterion_form($url,
                    array('criterioncourse' => $criterioncourse));

            if (!is_null($data = $criterionform->get_data())) {
                $grade = (int) $data->mingrade;
                $completedby = (int) $data->completedby;

                // Both fields are empty -> remove criterion
                if ($criterioncourse->exists() && $grade === 0 && $completedby === 0) {
                    $criterioncourse->delete();
                }

                // Update or insert criterion course
                else {
                    // Object doesn't exist yet, let's create the criterion.
                    if (!$criterioncourse->exists()) {
                        $criterion = new obf_criterion();
                        $criterion->set_badge($badge);
                        $criterion->set_completion_method(obf_criterion::CRITERIA_COMPLETION_ALL);
                        $criterion->save();

                        $criterioncourse->set_criterionid($criterion->get_id());
                    }

                    $criterioncourse->set_courseid($courseid);
                    $criterioncourse->set_grade($grade);
                    $criterioncourse->set_completedby($completedby);
                    $criterioncourse->save();

                    $redirecturl = new moodle_url('/local/obf/badge.php', array('id' => $badge->get_id(),
                        'action' => 'show', 'show' => 'criteria', 'courseid' => $courseid));

                    if ($data->reviewaftersave) {
                        $crit = $criterioncourse->get_criterion();
                        $recipientcount = $crit->review_previous_completions();
                        $redirecturl->param('msg', get_string('badgewasautomaticallyissued',
                                        'local_obf', $recipientcount));
                    }

                    redirect($redirecturl);
                }
            }

            $html .= $criterionform->render();
        }

        return $html;
    }

    /**
     * Renders the badge criteria in site context.
     *
     * @param obf_badge $badge
     * @return type
     */
    public function render_badge_criteria_site(obf_badge $badge) {
        $html = '';
        $file = '/local/obf/criterion.php';
        $url = new moodle_url($file, array('badgeid' => $badge->get_id(), 'action' => 'new'));
        $options = array();
        $criteria = $badge->get_completion_criteria();

        if (count($criteria) === 0) {
            $html .= $this->output->notification(get_string('nocriteriayet', 'local_obf'));
        }

        if (count($criteria) > 0) {
            $html .= html_writer::tag('p', get_string('badgeissuedwhen', 'local_obf'));
        }

        foreach ($criteria as $id => $criterion) {
            $criterionhtml = '';
            $attributelist = array();
            $criteriontype = 'courseset';
            $groupname = get_string('criteriatype' . $criteriontype, 'local_obf');
            $criterionitems = $criterion->get_items();

            foreach ($criterionitems as $item) {
                $attributelist[] = $item->get_text();
            }

            // The criterion can be edited if the criterion hasn't already been met
            $canedit = !$criterion->is_met();
            $heading = $groupname;

            $deleteurl = new moodle_url($file,
                    array('badgeid' => $badge->get_id(),
                'action' => 'delete', 'id' => $id));
            $heading .= obf_html::icon($deleteurl, 't/delete', 'delete');

            // If the criterion can be edited, show the edit-icon
            if ($canedit) {
                $editurl = new moodle_url($file,
                        array('badgeid' => $badge->get_id(),
                    'action' => 'edit', 'id' => $id));
                $heading .= obf_html::icon($editurl, 't/edit', 'edit');
            }

            $criterionhtml .= $this->output->heading(obf_html::div($heading), 3);

            if (!$canedit) {
                $criterionhtml .= $this->output->notification(get_string('cannoteditcriterion',
                                'local_obf'));
            }

            if (count($criterionitems) > 1) {
                $method = $criterion->get_completion_method() == obf_criterion::CRITERIA_COMPLETION_ALL
                            ? 'all' : 'any';
                $criterionhtml .= html_writer::tag('p',
                                get_string('criteriacompletedwhen' . $method, 'local_obf'));
            }

            $criterionhtml .= html_writer::alist($attributelist);
            $html .= $this->output->box($criterionhtml, 'generalbox service');
        }

        $html .= $this->output->single_button($url, get_string('addcriteria', 'local_obf'));

        return $html;
    }

    /**
     * Renders badge issuance history.
     *
     * @param obf_badge $badge
     * @param context $context
     * @param type $currentpage
     * @return type
     */
    public function print_badge_info_history(obf_badge $badge = null, context $context,
            $currentpage = 0) {
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
                $historytable->headspan = array(2, 1, 1, 2);
            } else {
                $historytable->headspan = array(1, 1, 2);
            }

            $headingrow[] = new obf_table_header('recipients');
            $headingrow[] = new obf_table_header('issuedon');
            $headingrow[] = new obf_table_header('expiresby');
            $historytable->head = $headingrow;

            // add history rows
            for ($i = $startindex; $i < $endindex; $i++) {
                $assertion = $history->get_assertion($i);
                $historytable->data[] = $this->render_historytable_row($assertion,
                        $singlebadgehistory, $path, $history->get_assertion_users($assertion));
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

    /**
     * Renders a single row in badge issuance history table.
     *
     * @param obf_assertion $assertion
     * @param type $singlebadgehistory
     * @param type $path
     * @param array $users
     * @return \html_table_row
     */
    private function render_historytable_row(obf_assertion $assertion, $singlebadgehistory, $path,
            array $users) {
        $expirationdate = $assertion->has_expiration_date() ? userdate($assertion->get_expires(),
                        get_string('strftimedate')) : '-';
        $row = new html_table_row();

        // If we're watching the whole history (not just a single badge),
        // show the badge details in the table.
        if (!$singlebadgehistory) {
            $b = $assertion->get_badge();

            if (!is_null($b)) {
                $url = new moodle_url($path, array('action' => 'show', 'id' => $b->get_id()));
                $row->cells[] = $this->print_badge_image($b, self::BADGE_IMAGE_SIZE_TINY);
                $row->cells[] = html_writer::link($url, s($b->get_name()));
            } else {
                $row->cells[] = '&nbsp;';
                $row->cells[] = s($assertion->get_name());
            }
        }

        $userlist = $this->render_userlist($users);

        $row->cells[] = obf_html::div(implode(', ', $userlist), 'recipientlist');
        $row->cells[] = userdate($assertion->get_issuedon(), get_string('strftimedate'));
        $row->cells[] = $expirationdate;
        $row->cells[] = html_writer::link(new moodle_url('/local/obf/event.php',
                        array('id' => $assertion->get_id())),
                        get_string('showassertion', 'local_obf'));

        return $row;
    }

    /**
     * Renders a list of users.
     *
     * @param array $users
     * @return type
     */
    private function render_userlist(array $users) {
        $userlist = array();

        foreach ($users as $user) {
            if (is_string($user)) {
                $userlist[] = $user;
            } else {
                $url = new moodle_url('/user/view.php', array('id' => $user->id));
                $userlist[] = html_writer::link($url, fullname($user),
                                array('title' => $user->email));
            }
        }

        return $userlist;
    }

    /**
     * Renders the tabs in badge-page.
     *
     * @param type $badgeid
     * @param context $context
     * @param type $selectedtab
     * @return type
     */
    public function print_badge_tabs($badgeid, context $context, $selectedtab = 'details') {
        $tabdata = array('details', 'criteria');
        $tabs = array();

        if ($context instanceof context_system) {
            $tabdata[] = 'email';
            $tabdata[] = 'history';
        }

        foreach ($tabdata as $tabname) {
            $url = new moodle_url('/local/obf/badge.php',
                    array('id' => $badgeid, 'action' => 'show',
                'show' => $tabname));

            if ($context instanceof context_course) {
                $url->param('courseid', $context->instanceid);
            }

            $tabs[] = new tabobject($tabname, $url, get_string('badge' . $tabname, 'local_obf'));
        }

        return self::render_tabs($tabs, $selectedtab);
//        return $this->output->tabtree($tabs, $selectedtab);
    }

    public static function render_tabs(array $tabs, $selectedtab = '') {
        ob_start();
        print_tabs(array($tabs), $selectedtab);
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    /**
     * Renders the OBF configuration form
     *
     * @param obf_config_form $form
     * @return type
     */
    public function render_obf_config_form(obf_config_form $form) {
        $html = $this->print_heading('settings', 2);
        $html .= $form->render();

        return $html;
    }

    /**
     * Renders the badge exporter form.
     *
     * @param obf_badge_export_form $form
     * @return type
     */
    public function render_badge_exporter(obf_badge_export_form $form) {
        $html = $this->print_heading('badgeexport', 2);
        $html .= $form->render();

        return $html;
    }

    /**
     * Renders the OBF-userconfig form.
     *
     * @param obf_userconfig_form $form
     * @param type $errormsg
     * @return type
     */
    public function render_userconfig(obf_userconfig_form $form, $errormsg = '') {
        $html = $this->print_heading('obf', 2);

        if (!empty($errormsg)) {
            $html .= $this->output->notification($errormsg);
        }

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

        return local_obf_renderer::render_tabs($tabs, $selectedtab);
    }

    public function page(obf_badge $badge, $tab, $content) {
        $html = $this->tabs($badge->get_id(), $tab);
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

class obf_html {
    /**
     * Renders an icon.
     *
     * @param moodle_url $url
     * @param type $icon
     * @param type $langkey
     * @return type
     */
    public static function icon(moodle_url $url, $icon, $langkey) {
        global $OUTPUT;
        return $OUTPUT->action_icon($url,
                        new pix_icon($icon, get_string($langkey), null, array('class' => 'obf-icon')));
    }

    public static function div($content, $classes = '') {
        return html_writer::tag('div', $content, empty($classes) ? array() : array('class' => $classes));
    }
}
