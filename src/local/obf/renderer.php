<?php
/**
 * Renderer for Open Badge Factory -plugin
 */
defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/tablelib.php';
require_once __DIR__ . '/form/issuance.php';
require_once __DIR__ . '/class/criterion/criterion.php';
require_once __DIR__ . '/form/emailtemplate.php';
require_once __DIR__ . '/form/coursecriterion.php';
require_once __DIR__ . '/class/backpack.php';

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
     * @param obf_badge[] $badges
     * @param type $hasissuecapability
     * @param context $context
     * @param type $message
     * @return type
     */
    public function render_badgelist_course($badges, $hasissuecapability,
                                            context $context, $message = '') {
        $html = '';

        if (!empty($message)) {
            $html .= $this->output->notification($message, 'notifysuccess');
        }

        $html .= $this->render_badge_categories($badges);
        $badgesincourse = obf_badge::get_badges_in_course($context->instanceid);
        $html .= $this->print_heading('coursebadgelisttitle', 2);
        $errormsg = $this->output->notification(get_string('nobadgesincourse',
                        'local_obf'));

        if (count($badgesincourse) == 0) {
            $html .= $errormsg;
        }
        else {
            $validbadges = array();

            foreach ($badgesincourse as $badge) {
                // Badge is deleted from OBF, but there are still rules in the
                // database. It shouldn't happen in practice, but in theory
                // it's possible. We should handle it better.
                if ($badge->has_name()) {
                    $validbadges[] = $badge;
                }
            }

            $html .= count($validbadges) > 0 ? $this->render_badges($validbadges,
                            $hasissuecapability, $context) : $errormsg;
        }

        $html .= $this->print_heading('badgelisttitle', 2);
        $html .= $this->render_badges($badges, $hasissuecapability, $context);

        return $html;
    }

    /**
     * Renders the list of badges.
     *
     * @param obf_badge[] $badges
     * @param type $hasissuecapability
     * @param context $context
     * @param type $message
     * @return type
     */
    public function render_badgelist($badges, $hasissuecapability,
                                     context $context, $message = '') {
        $html = $this->print_heading('badgelisttitle', 2);

        if (!empty($message)) {
            $html .= $this->output->notification($message, 'notifysuccess');
        }

        $html .= $this->render_badge_categories($badges);
        $html .= $this->render_badges($badges, $hasissuecapability, $context);

        return $html;
    }

    /**
     *
     * @param type $badges
     * @return type
     */
    public function render_badge_categories($badges) {
        $html = '';
        $categories = array();
        $items = array();
        $availablecategories = obf_badge::get_available_categories();
        $filtercategories = count($availablecategories) > 0;

        foreach ($badges as $badge) {
            foreach ($badge->get_categories() as $category) {
                if (!in_array($category, $categories) && (!$filtercategories || in_array($category,
                                $availablecategories))) {
                    $items[] = html_writer::tag('button', s($category),
                                    array('class' => ''));
                    $categories[] = $category;
                }
            }
        }

        if (count($items) > 0) {
            $html .= obf_html::div(
                            obf_html::div(
                                    html_writer::tag('p',
                                            get_string('showcategories',
                                                    'local_obf')) .
                                    html_writer::tag('button',
                                            get_string('resetfilter',
                                                    'local_obf'),
                                            array('class' => 'obf-reset-filter')),
                                    'obf-category-reset-wrapper') .
                            html_writer::alist($items,
                                    array('class' => 'obf-categories')),
                            'obf-category-wrapper');
            $this->page->requires->yui_module('moodle-local_obf-badgecategorizer',
                    'M.local_obf.init_badgecategorizer');
        }

        return $html;
    }

    /**
     *
     * @param obf_assertion_collection $assertions
     * @return type
     */
    public function render_user_assertions(obf_assertion_collection $assertions) {
        global $USER;

        $html = '';
        $items = '';
        $userid = $USER->id;
        $js_assertions = array();

        for ($i = 0; $i < count($assertions); $i++) {
            $assertion = $assertions->get_assertion($i);
            $badge = $assertion->get_badge();
            $badgeimage = $this->print_badge_image($badge, -1);
            $badgename = html_writer::tag('p', s($badge->get_name()));
            $aid = $userid . '-' . $i;
            $js_assertions[$aid] = $assertion->toArray();
            $items .= html_writer::tag('li',
                            obf_html::div($badgeimage . $badgename),
                            array('id' => $aid));
        }

        $html .= html_writer::tag('ul', $items, array('class' => 'badgelist'));
        $params = $this->get_displayer_params();
        $params['assertions'] = $js_assertions;

        $this->page->requires->yui_module('moodle-local_obf-courseuserbadgedisplayer',
                'M.local_obf.init_badgedisplayer', array($params));
        $this->page->requires->string_for_js('closepopup', 'local_obf');

        return $html;
    }

    /**
     * Renders a single badge assertion.
     *
     * @param obf_assertion $assertion
     * @return type
     */
    public function render_assertion(obf_assertion $assertion,
                                     $printheading = true) {
        $html = '';
        $badge = $assertion->get_badge();
        $collection = new obf_assertion_collection(array($assertion));
        $issuedon = $assertion->get_issuedon();
        $issuedon = is_numeric($issuedon) ? userdate($issuedon,
                        get_string('dateformatdate', 'local_obf')) : $issuedon;
        $users = $collection->get_assertion_users($assertion);

        $assertionitems = array(
            get_string('badgename', 'local_obf') => $badge->get_name(),
            get_string('badgedescription', 'local_obf') => $badge->get_description(),
            get_string('issuedon', 'local_obf') => $issuedon);

        if (count($assertion->get_recipients()) > 0) {
            $list = html_writer::alist(array_map(function ($user) {
                                if ($user instanceof stdClass) {
                                    return fullname($user);
                                }

                                return $user;
                            }, $users));
            $assertionitems[get_string('recipients', 'local_obf')] = $list;
        }

        if ($printheading) {
            $html .= $this->print_heading('issuancedetails', 2);
        }

        $html .= obf_html::div(
                        obf_html::div(
                                html_writer::empty_tag('img',
                                        array('src' => $badge->get_image())),
                                'image-wrapper') .
                        obf_html::div(
                                obf_html::div(
                                        $this->print_heading('badgedetails') .
                                        $this->render_definition_list($assertionitems),
                                        'badge-details') .
                                obf_html::div(
                                        $this->print_heading('issuerdetails') .
                                        $this->render_issuer_details($badge->get_issuer()),
                                        'issuer-details'), 'assertion-details'),
                        'obf-assertion');

        return $html;
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
            $arr[] = html_writer::tag('dt', $name) . html_writer::tag('dd',
                            $value);
        }

        return html_writer::tag('dl', implode('', $arr),
                        array('class' => 'obf-definition-list'));
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
    public function page_badgedetails(obf_client $client, obf_badge $badge,
                                      context $context, $tab = 'details',
                                      $page = 0, $message = '') {
        $methodprefix = 'print_badge_info_';
        $rendererfunction = $methodprefix . $tab;
        $html = '';

        if (!method_exists($this, $rendererfunction)) {
            $html .= $this->output->notification(get_string('invalidtab',
                            'local_obf'));
        }
        else {
            if (!empty($message)) {
                $html .= $this->output->notification($message, 'notifysuccess');
            }

            $html .= $this->print_badge_tabs($badge->get_id(), $context, $tab);
            $html .= call_user_func(array($this, $rendererfunction), $client,
                    $badge, $context, $page);
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
        $issueurl = new moodle_url('/local/obf/issue.php',
                array('id' => $badge->get_id()));

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
     * Renders the OBF-badges.
     *
     * @param obf_badge[] $badges
     * @param type $hasissuecapability
     * @param context $context
     * @return type
     */
    protected function render_badges($badges, $hasissuecapability,
                                     context $context) {
        $html = '';

        if (count($badges) === 0) {
            $html .= $this->output->notification(get_string('nobadges',
                            'local_obf'), 'notifynotice');
        }
        else {
            $items = '';

            foreach ($badges as $badge) {
                $badgeimage = $this->print_badge_image($badge, -1);
                $badgename = html_writer::tag('p', s($badge->get_name()));

                $url = new moodle_url('/local/obf/badge.php',
                        array('id' => $badge->get_id(), 'action' => 'show'));

                if ($context instanceof context_course) {
                    $url->param('courseid', $context->instanceid);
                }

                $items .= html_writer::tag('li',
                                obf_html::div(html_writer::link(
                                                $url, $badgeimage . $badgename)),
                                array('data-categories' => json_encode($badge->get_categories())));
            }

            $html .= html_writer::tag('ul', $items,
                            array('class' => 'badgelist'));
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
    public function print_badge_image(obf_badge $badge,
                                      $width = self::BADGE_IMAGE_SIZE_SMALL) {
        $params = array("src" => $badge->get_image(), "alt" => $badge->get_name());

        if ($width > 0) {
            $params['width'] = $width;
        }

        return html_writer::empty_tag("img", $params);
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
     * Renders the badge and issuer details.
     *
     * @param obf_badge $badge
     * @param context $context
     * @return type
     */
    public function print_badge_info_details(obf_client $client,
                                             obf_badge $badge, context $context) {
        $html = '';
        $badgeimage = $this->print_badge_image($badge,
                self::BADGE_IMAGE_SIZE_NORMAL);
        $createdon = $badge->get_created();
        $badgecreated = empty($createdon) ? '&amp;' : userdate($createdon,
                        get_string('dateformatdate', 'local_obf'));

        $boxes = obf_html::div($badgeimage, 'obf-badgeimage');
        $badgedetails = $this->print_heading('badgedetails');

        $definitions = array(
            get_string('badgename', 'local_obf') => $badge->get_name(),
            get_string('badgedescription', 'local_obf') => $badge->get_description(),
            get_string('badgecreated', 'local_obf') => $badgecreated
        );

        if ($badge->has_criteria_url()) {
            $definitions[get_string('badgecriteriaurl', 'local_obf')] = html_writer::link($badge->get_criteria_url(),
                            s($badge->get_criteria_url()));
        }

        if (count($badge->get_tags()) > 0) {
            $definitions[get_string('badgetags', 'local_obf')] = implode(', ',
                    array_map('s', $badge->get_tags()));
        }

        $badgedetails .= $this->render_definition_list($definitions);
        $issuer = $badge->get_issuer();


        // issuer details table
        $badgedetails .= $this->print_heading('issuerdetails');
        $badgedetails .= $this->render_issuer_details($issuer);

        $boxes .= obf_html::div($badgedetails, 'obf-badgedetails');
        $html .= obf_html::div($boxes, 'obf-badgewrapper');

        return $html;
    }

    /**
     *
     * @param obf_issuer $issuer
     * @return type
     */
    public function render_issuer_details(obf_issuer $issuer) {
        $url = $issuer->get_url();
        $description = $issuer->get_description();
        $issuerurl = empty($url) ? '' : html_writer::link($url, $url);
        $html = $this->render_definition_list(array(
            get_string('issuername', 'local_obf') => $issuer->get_name(),
            get_string('issuerurl', 'local_obf') => $issuerurl,
            get_string('issuerdescription', 'local_obf') => empty($description) ? '-'
                        : $description,
            get_string('issueremail', 'local_obf') => $issuer->get_email()
        ));

        return $html;
    }

    /**
     * Renders the badge criteria page.
     *
     * @param obf_badge $badge
     * @param context $context
     * @return type
     */
    public function print_badge_info_criteria(obf_client $client,
                                              obf_badge $badge, context $context) {
        $html = '';

        if ($context instanceof context_course) {
            $html .= $this->render_badge_criteria_course($badge,
                    $context->instanceid);
        }
        else {
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
        $criteria = $badge->get_completion_criteria();
        $coursewithcriterion = null;
        $criterioncourseid = null;
        $courseincriterion = false;
        $error = '';
        $url = new moodle_url('/local/obf/badge.php',
                array('id' => $badge->get_id(), 'action' =>
            'show', 'show' => 'criteria', 'courseid' => $courseid));

        // Show edit form if there aren't any criteria related to this badge or
        // there is only one which hasn't been met yet by any user.
        foreach ($criteria as $criterion) {
            $courses = $criterion->get_items(true);
            $courseincriterion = $criterion->has_course($courseid);


            // If the course is already related to a criterion containing other courses also,
            // the criterion cannot be modified in course context.
            if ($courseincriterion) {
                $coursewithcriterion = $criterion;

                if (count($courses) > 1) {
                    $error = 'coursealreadyincriterion';
                }
                else if ($criterion->is_met()) {
                    $error = 'cannoteditcriterion';
                }
                else {
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
                $html .= html_writer::tag('p',
                                $items[0]->get_text_for_single_course());
            }

            $html .= $this->output->notification(get_string($error, 'local_obf'));
        }

        // Show the course criteria form
        else {
            $params = array(
                    'criteriatype' => optional_param('criteriatype', obf_criterion_item::CRITERIA_TYPE_UNKNOWN, PARAM_INT),
                    'courseid' => optional_param('courseid', null, PARAM_INT));

            if (!is_null($coursewithcriterion)) {
                $params = array_merge($params, array('obf_criterion_id' => $coursewithcriterion->get_id()));
            }
            $criterioncourse = is_null($criterioncourseid) ? obf_criterion_item::build($params)
                        : obf_criterion_item::get_instance($criterioncourseid);
            $criterionform = new obf_coursecriterion_form($url,
                    array('criterioncourse' => $criterioncourse));

            // Deleting the rule is done via cancel-button
            if ($criterionform->is_cancelled()) {
                $criterioncourse->delete();
                redirect(new moodle_url('/local/obf/badge.php',
                        array('id' => $badge->get_id(),
                    'action' => 'show', 'show' => 'criteria', 'courseid' => $courseid,
                    'msg' => get_string('criteriondeleted', 'local_obf'))));
            }

            if (!is_null($data = $criterionform->get_data())) {
                $pickingtype = (!$criterioncourse->exists() || $criterioncourse->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_UNKNOWN ||
                        property_exists($data, 'picktype') && $data->picktype === 'yes');
                if ($pickingtype) {
                    if (!is_null($courseid)) {
                        if (!$criterioncourse->exists()) {
                            $criterion = new obf_criterion();
                            $criterion->set_badge($badge);
                            $criterion->set_completion_method(obf_criterion::CRITERIA_COMPLETION_ALL);
                            $criterion->save();

                            $criterioncourse->set_criterionid($criterion->get_id());
                        }
                        $criterioncourse->set_courseid($courseid);
                        $criterioncourse->set_criteriatype($data->criteriatype);
                        $criterioncourse->save();

                        $redirecturl = new moodle_url('/local/obf/badge.php',
                                array('id' => $badge->get_id(),
                            'action' => 'show', 'show' => 'criteria', 'courseid' => $courseid
                            ));
                        redirect($redirecturl);
                    }
                } else { // Saving the rule
                    if ($data->criteriatype === obf_criterion_item::CRITERIA_TYPE_ACTIVITY) {
                        if (!$criterioncourse->exists()) {
                            $criterion = new obf_criterion();
                            $criterion->set_badge($badge);
                            $criterion->set_completion_method(obf_criterion::CRITERIA_COMPLETION_ALL);
                            $criterion->save();

                            $criterioncourse->set_criterionid($criterion->get_id());
                        }
                        $criterioncourse->set_courseid($courseid);
                        $criterioncourse->save_params($data);
                    } else if ($data->criteriatype === obf_criterion_item::CRITERIA_TYPE_COURSE) {
                        foreach ($data->mingrade as $key => $value) {
                            $grade = (int) $data->mingrade[$key];
                            $completedby = (int) $data->{'completedby_'.$key};
                        }

                        // Both fields are empty -> remove criterion
                        // if ($criterioncourse->exists() && $grade === 0 && $completedby === 0) {
                        //     $criterioncourse->delete();
                        // }
                        // Update or insert criterion course
                        // else {
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

                        $redirecturl = new moodle_url('/local/obf/badge.php',
                                array('id' => $badge->get_id(),
                            'action' => 'show', 'show' => 'criteria', 'courseid' => $courseid,
                            'msg' => get_string('criterionsaved', 'local_obf')));

                        if ($data->reviewaftersave) {
                            $crit = $criterioncourse->get_criterion();
                            $recipientcount = $crit->review_previous_completions();
                            $redirecturl->param('msg',
                                    get_string('badgewasautomaticallyissued',
                                            'local_obf', $recipientcount));
                        }

                        redirect($redirecturl);

                    }
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
        $url = new moodle_url($file,
                array('badgeid' => $badge->get_id(), 'action' => 'new'));
        $options = array();
        $criteria = $badge->get_completion_criteria();

        if (count($criteria) === 0) {
            $html .= $this->output->notification(get_string('nocriteriayet',
                            'local_obf'));
        }

        if (count($criteria) > 0) {
            $html .= html_writer::tag('p',
                            get_string('badgeissuedwhen', 'local_obf'));
        }

        foreach ($criteria as $id => $criterion) {
            $criterionhtml = '';
            $attributelist = array();
            $criteriontype = 'courseset';
            $criterionitems = $criterion->get_items();
            if (count($criterionitems) == 1) {
                $criteriontype = obf_criterion_item::get_criterion_type_text($criterionitems[0]->get_criteriatype());
            }
            $groupname = get_string('criteriatype' . $criteriontype, 'local_obf');

            if ($criterionitems[0]->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_TOTARA_CERTIF) {
                $groupname = get_string('criteriatypetotaracertif', 'local_obf');
            }

            foreach ($criterionitems as $item) {
                $attributelist = array_merge($attributelist, $item->get_text_array());
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
                                get_string('criteriacompletedwhen' . $method,
                                        'local_obf'));
            }

            $criterionhtml .= html_writer::alist($attributelist);
            $html .= $this->output->box($criterionhtml, 'generalbox service');
        }

        $html .= $this->output->single_button($url,
                get_string('addcriteria', 'local_obf'));

        return $html;
    }

    /**
     * Renders badge issuance history.
     *
     * @param obf_badge $badge
     * @param type $currentpage
     * @return type
     */
    public function print_badge_info_history(obf_client $client,
                                             obf_badge $badge = null,
                                             context $context, $currentpage = 0) {
        $singlebadgehistory = !is_null($badge);
        $history = $singlebadgehistory ? $badge->get_assertions() : obf_assertion::get_assertions($client);
        $historytable = new html_table();
        $historytable->attributes = array('class' => 'generaltable historytable');
        $html = $this->print_heading('history', 2);
        $historysize = count($history);
        $langkey = $singlebadgehistory ? 'nobadgehistory' : 'nohistory';

        if ($historysize == 0) {
            $html .= $this->output->notification(get_string($langkey,
                            'local_obf'), 'generalbox');
        }
        else {
            // paging settings
            $perpage = 10; // TODO: hard-coded here
            $path = '/local/obf/badge.php';
            $url = $singlebadgehistory ? new moodle_url($path,
                    array('action' => 'show', 'id' => $badge->get_id(), 'show' => 'history'))
                        : new moodle_url($path, array('action' => 'history'));
            $pager = new paging_bar($historysize, $currentpage, $perpage, $url,
                    'page');
            $htmlpager = $this->render($pager);
            $startindex = $currentpage * $perpage;
            $endindex = $startindex + $perpage > $historysize ? $historysize : $startindex
                    + $perpage;

            // heading row
            $headingrow = array();

            if (!$singlebadgehistory) {
                $headingrow[] = new obf_table_header('badgename');
                $historytable->headspan = array(2, 1, 1, 1, 1);
            }
            else {
                $historytable->headspan = array(1, 1, 2);
            }

            $headingrow[] = new obf_table_header('recipients');
            $headingrow[] = new obf_table_header('issuedon');
            $headingrow[] = new obf_table_header('expiresby');
            $headingrow[] = new html_table_cell();
            $historytable->head = $headingrow;

            // add history rows
            for ($i = $startindex; $i < $endindex; $i++) {
                $assertion = $history->get_assertion($i);
                $users = $history->get_assertion_users($assertion);
                $historytable->data[] = $this->render_historytable_row($assertion,
                        $singlebadgehistory, $path, $users);
            }

            $html .= $htmlpager;
            $html .= html_writer::table($historytable);
            $html .= $htmlpager;
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
    private function render_historytable_row(obf_assertion $assertion,
                                             $singlebadgehistory, $path,
                                             array $users) {
        $expirationdate = $assertion->has_expiration_date() ? userdate($assertion->get_expires(),
                        get_string('dateformatdate', 'local_obf')) : '-';
        $row = new html_table_row();

        // If we're watching the whole history (not just a single badge),
        // show the badge details in the table.
        if (!$singlebadgehistory) {
            $badge = $assertion->get_badge();

            if (!is_null($badge)) {
                $url = new moodle_url($path,
                        array('action' => 'show', 'id' => $badge->get_id()));
                $row->cells[] = $this->print_badge_image($badge,
                        self::BADGE_IMAGE_SIZE_TINY);
                $row->cells[] = html_writer::link($url, s($badge->get_name()));
            }
            else {
                $row->cells[] = '&nbsp;';
                $row->cells[] = s($assertion->get_name());
            }
        }

        $recipienthtml = '';

        if (count($users) > 3) {
            $recipienthtml .= html_writer::tag('p',
                            get_string('historyrecipients', 'local_obf',
                                    count($users)),
                            array('title' => $this->render_userlist($users,
                                false)));
        }
        else {
            $recipienthtml .= $this->render_userlist($users);
        }

//        $userlist = $this->render_userlist($users);
//        $row->cells[] = obf_html::div(implode(', ', $userlist), 'recipientlist');
        $row->cells[] = $recipienthtml;
        $row->cells[] = userdate($assertion->get_issuedon(),
                get_string('dateformatdate', 'local_obf'));
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
    private function render_userlist(array $users, $addlinks = true) {
        $userlist = array();

        foreach ($users as $user) {
            if (is_string($user)) {
                $userlist[] = $user;
            }
            else {
                if ($addlinks) {
                    $url = new moodle_url('/user/view.php',
                            array('id' => $user->id));
                    $userlist[] = html_writer::link($url, fullname($user),
                                    array('title' => $user->email));
                }
                else {
                    $userlist[] = fullname($user);
                }
            }
        }

        return implode(', ', $userlist);
    }

    /**
     * Renders the tabs in badge-page.
     *
     * @param type $badgeid
     * @param context $context
     * @param type $selectedtab
     * @return type
     */
    public function print_badge_tabs($badgeid, context $context,
                                     $selectedtab = 'details') {
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

            $tabs[] = new tabobject($tabname, $url,
                    get_string('badge' . $tabname, 'local_obf'));
        }

        $tabhtml = '';

        if (method_exists($this->output, 'tabtree')) {
            $tabhtml = $this->output->tabtree($tabs, $selectedtab);
        }
        // Moodle 2.2
        else {
            $tabhtml = self::render_tabs($tabs, $selectedtab);
        }

        return $tabhtml;
    }

    // For Moodle 2.2
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
        $html = $this->print_heading('backpacksettings', 2);

        if (!empty($errormsg)) {
            $html .= $this->output->notification($errormsg);
        }

        $html .= $form->render();
        $url = new moodle_url('/local/obf/verifyemail.php');

        $this->page->requires->js(new moodle_url('https://login.persona.org/include.js'));
        $this->page->requires->yui_module('moodle-local_obf-backpackconfigurator',
                'M.local_obf.init_backpackconfigurator',
                array(array('url' => $url->out())));

        return $html;
    }

    public function render_course_participants($courseid, array $participants) {
        $html = '';
        $html .= $this->print_heading('courseuserbadges');

        if (count($participants) === 0) {
            $html .= $this->output->notification(get_string('noparticipants',
                            'local_obf'));
        }
        else {
            $table = new html_table();
            $userpicparams = array('size' => 16, 'courseid' => $courseid);
            $userswithbackpack = obf_backpack::get_user_ids_with_backpack();

            $table->id = 'obf-participants';

            // some of the formatting taken from user/index.php
            $datestring = new stdClass();
            $datestring->year = get_string('year');
            $datestring->years = get_string('years');
            $datestring->day = get_string('day');
            $datestring->days = get_string('days');
            $datestring->hour = get_string('hour');
            $datestring->hours = get_string('hours');
            $datestring->min = get_string('min');
            $datestring->mins = get_string('mins');
            $datestring->sec = get_string('sec');
            $datestring->secs = get_string('secs');

            $strnever = get_string('never');

            $table->head = array(get_string('userpic'), get_string('fullnameuser'),
                get_string('city'), get_string('lastaccess'));
            $table->headspan = array(1, 1, 1, 2);

            foreach ($participants as $user) {
                $lastaccess = $user->lastaccess ? format_time(time() - $user->lastaccess,
                                $datestring) : $strnever;
                $row = new html_table_row();
                $row->id = 'participant-' . $user->id;
                $link = in_array($user->id, $userswithbackpack) ? html_writer::link('#',
                                get_string('showbadges', 'local_obf')) : '&nbsp;';
                $linkcell = new html_table_cell($link);

                $linkcell->attributes = array('class' => 'show-badges');

                $row->cells = array(
                    $this->output->user_picture($user, $userpicparams),
                    html_writer::link(new moodle_url('/user/view.php',
                            array('id' => $user->id, 'course' =>
                        $courseid)), fullname($user)),
                    $user->city,
                    $lastaccess,
                    $linkcell
                );

                $table->data[] = $row;
            }

            $html .= html_writer::table($table);
            $url = new moodle_url('/local/obf/backpack.php');
            $params = $this->get_displayer_params();
            $params['url'] = $url->out();

            $this->page->requires->yui_module('moodle-local_obf-courseuserbadgedisplayer',
                    'M.local_obf.init_courseuserbadgedisplayer', array($params));
            $this->page->requires->string_for_js('closepopup', 'local_obf');
        }

        return $html;
    }

    private function get_displayer_params() {
        $assertion = $this->get_template_assertion();
        $params = array(
            'tpl' => array(
                'list' => html_writer::tag('ul', '{{{ this.content }}}',
                        array('class' => 'badgelist')),
                'assertion' => $this->render_assertion($assertion, false),
                'badge' => html_writer::tag('li',
                        obf_html::div(
                                html_writer::empty_tag('img',
                                        array('src' => '{{{ this.badge.image }}}')) .
                                html_writer::tag('p', '{{ this.badge.name }}')),
                        array('title' => '{{ this.badge.name }}', 'id' => '{{ this.id }}'))
        ));

        return $params;
    }

    private function get_template_assertion() {
        $issuer = obf_issuer::get_instance_from_arr(array('id' => '', 'description' => '{{ this.badge.issuer.description }}',
                    'email' => '', 'url' => '{{ this.badge.issuer.url }}', 'name' => '{{ this.badge.issuer.name }}'));
        $badge = new obf_badge();
        $badge->set_name('{{ this.badge.name }}');
        $badge->set_description('{{ this.badge.description }}');
        $badge->set_issuer($issuer);
        $badge->set_image('{{{ this.badge.image }}}');

        $assertion = new obf_assertion();
        $assertion->set_badge($badge);
        $assertion->set_issuedon('{{{ this.issued_on }}}');

        return $assertion;
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
            $tabs[] = new tabobject($tabname, $url,
                    get_string('badge' . $tabname, 'local_obf'));
        }

        if (method_exists($this->output, 'tabtree')) {
            return $this->output->tabtree($tabs, $selectedtab);
        }
        else {
            return local_obf_renderer::render_tabs($tabs, $selectedtab);
        }
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
        parent::__construct(is_null($stringid) ? null : get_string($stringid,
                                'local_obf'));
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
                        new pix_icon($icon, get_string($langkey), null,
                        array('class' => 'obf-icon')));
    }

    public static function div($content, $classes = '') {
        return html_writer::tag('div', $content,
                        empty($classes) ? array() : array('class' => $classes));
    }

}
