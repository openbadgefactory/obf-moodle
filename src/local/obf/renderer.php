<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Renderer for Open Badge Factory -plugin
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/form/issuance.php');
require_once(__DIR__ . '/class/criterion/criterion.php');
require_once(__DIR__ . '/form/emailtemplate.php');
require_once(__DIR__ . '/form/coursecriterion.php');
require_once(__DIR__ . '/class/backpack.php');

/**
 * HTML output renderer for local_obf-plugin
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_obf_renderer extends plugin_renderer_base {
    /**
     * @var int tiny badge image.
     */
    const BADGE_IMAGE_SIZE_TINY = 22;
    /**
     * @var int small badge image.
     */
    const BADGE_IMAGE_SIZE_SMALL = 32;
    /**
     * @var int normal badge image.
     */
    const BADGE_IMAGE_SIZE_NORMAL = 100;

    /**
     * Render the list of backpack providers for backpack config -page.
     * @param type $backpacks
     */
    public function render_backpack_provider_list($backpacks) {
        $content = '';
        
        $table = new html_table();

        $table->id = 'obf-backpackproviders';
        $table->attributes = array('class' => 'local-obf generaltable');

        $table->head = array(get_string('backpackprovidershortname', 'local_obf'), get_string('backpackproviderfullname', 'local_obf'),
            get_string('backpackproviderurl', 'local_obf'), get_string('backpackprovideremailconfigureable', 'local_obf'), get_string('backpackprovideractions', 'local_obf'));


        foreach($backpacks as $backpack) {
            $row = new html_table_row();
            $editurl = new moodle_url('/local/obf/backpackconfig.php', array('action' => 'edit', 'id' => $backpack->get_provider()));
            $links = html_writer::link($editurl, get_string('edit'));
            $actionscell = new html_table_cell($links);
            $row->cells = array(
                $backpack->get_providershortname(),
                $backpack->get_providerfullname(),
                $backpack->get_apiurl(),
                $backpack->requires_email_verification() ? get_string('yes') : '',
                $actionscell
                );
            $table->data[] = $row;
        }
        $content .= html_writer::table($table);
        $createurl = new moodle_url('/local/obf/backpackconfig.php', array('action' => 'create'));
        $content .= html_writer::div(
                html_writer::link($createurl, get_string('create'), array('class' => 'btn btn-default'))
                ,
                'pull-right');
        return $content;
    }

    /**
     * Renders the list of badges in course context
     *
     * @param obf_badge[] $badges
     * @param bool $hasissuecapability
     * @param context $context
     * @param string $message
     * @return string
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
        } else {
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
     * @param bool $hasissuecapability
     * @param context $context
     * @param string $message
     * @return string
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
     * Render all available badge categories.
     *
     * @param obf_badge[] $badges
     * @return string
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
            $html .= local_obf_html::div(
                            local_obf_html::div(
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
     * Render assertions for user.
     *
     * @param obf_assertion_collection $assertions
     * @param stdClass $user
     * @param bool $large
     * @return string
     */
    public function render_user_assertions(obf_assertion_collection $assertions, $user = null, $large = false) {
        global $USER, $DB;

        $html = '';
        $items = '';
        if (is_numeric($user)) {
            $userid = $user;
            $user = $DB->get_record('user', array('id' => $userid));
        } else if ($user instanceof stdClass) {
            $userid = $user->id;
        } else if (empty($userid)) {
            $userid = $USER->id;
            $user = $DB->get_record('user', array('id' => $userid));
        }
        $jsassertions = array();

        for ($i = 0; $i < count($assertions); $i++) {
            $assertion = $assertions->get_assertion($i);
            if ($assertion->is_revoked_for_user($user)) {
                continue;
            }
            $badge = $assertion->get_badge();
            $aid = $userid . '-' . $i;
            $jsassertions[$aid] = $assertion->toArray();
            $attributes = array('id' => $aid);
            $attributes['class'] = '';
            if ($assertion->badge_has_expired()) {
                $attributes['class'] = 'expired-assertion';
            }
            $items .= html_writer::tag('li',
                    $this->render_single_simple_assertion($assertion, $large),
                            $attributes);
        }

        $ulid = uniqid('badgelist');
        $html .= html_writer::tag('ul', $items, array('class' => 'badgelist', 'id' => $ulid));
        $params = $this->get_displayer_params($jsassertions, $ulid, $userid);

        $this->page->requires->yui_module('moodle-local_obf-courseuserbadgedisplayer',
                'M.local_obf.init_badgedisplayer', array($params));
        $this->page->requires->string_for_js('closepopup', 'local_obf');
        $this->page->requires->string_for_js('blacklistbadge', 'local_obf');

        return $html;
    }
    /**
     * Render one assertion.
     *
     * @param obf_assertion $assertion
     * @param bool $large Use large style? Default: false.
     * @return string HTML of one badge assertion.
     */
    public function render_single_simple_assertion($assertion, $large = false) {
        $badge = $assertion->get_badge();
        $badgeimage = $this->print_badge_image($badge, -1);
        $badgename = html_writer::tag('p', s($badge->get_name()), array('class' => 'badgename'));
        $badgedescription = html_writer::tag('p', s($badge->get_description()), array('class' => 'description'));
        $extra = '';
        $divclass = 'obf-badge';
        if ($assertion->badge_has_expired()) {
            $divclass .= ' expired-assertion';
            $extra = html_writer::tag('div', get_string('expired', 'local_obf'), array('class' => 'expired-info'));
        }
        if ($large) {
            $divclass .= ' large';
        }
        $html = local_obf_html::div($extra . $badgeimage . local_obf_html::div($badgename . $badgedescription, 'body'), $divclass);
        return $html;
    }

    /**
     * Renders a single badge assertion.
     *
     * @param obf_assertion $assertion
     * @param bool $printheading
     * @param obf_revoke_form $revokeform
     * @param bool $modal
     * @return string
     */
    public function render_assertion(obf_assertion $assertion,
                                     $printheading = true, $revokeform = null, $modal = false) {
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

        $expiresby = $assertion->get_expires();
        $assertionitems = array_merge($assertionitems,
                array(get_string('badgeexpiresby', 'local_obf') => $expiresby));

        if (count($assertion->get_recipients()) > 0) {
            if (!is_null($revokeform)) {
                $list = $revokeform->render();
            } else {
                $list = html_writer::alist(array_map(
                        function ($user) {
                            if ($user instanceof stdClass) {
                                return fullname($user);
                            }

                            return $user;
                        }, $users));
            }

            $assertionitems[get_string('recipients', 'local_obf')] = $list;
        }

        if ($printheading) {
            $html .= $this->print_heading('issuancedetails', 2);
        }

        if ($modal) {
            $html .= $this->render_badge_modal($badge, $assertion);
        } else {
            $html .= local_obf_html::div(
                            local_obf_html::div(
                                    html_writer::empty_tag('img',
                                            array('src' => $badge->get_image())),
                                    'image-wrapper') .
                            local_obf_html::div(
                                    local_obf_html::div(
                                            $this->print_heading('badgedetails') .
                                            $this->render_definition_list($assertionitems),
                                            'badge-details') .
                                    local_obf_html::div(
                                            $this->print_heading('issuerdetails') .
                                            $this->render_issuer_details($badge->get_issuer()),
                                            'issuer-details'), 'assertion-details'),
                            'obf-assertion');
        }

        return $html;
    }
    /**
     * Render details used in badge/assertion displayed in a modal.
     * @param obf_badge $badge
     * @param obf_assertion $assertion
     * @return string HTML-string.
     */
    public function render_badge_modal($badge, $assertion) { 
        $issuer = $badge->get_issuer();
        $issuerdetails = html_writer::tag('label', get_string('issuer', 'local_obf'));
        $issuerurl = $issuer->get_url();
        if (!empty($issuerurl)) {
            $issuerdetails .= html_writer::link($issuer->get_url(),
                    $issuer->get_name(), array('class' => 'issuer-url'));
        } else {
            $issuerdetails .= $issuer->get_name();
        }
        $issuerdetails .= ' / ' . html_writer::link('mailto:'.$issuer->get_email(), $issuer->get_email());
        $issuedon = $assertion->get_issuedon();
        $assertionitems = array(
            get_string('issuedon', 'local_obf') => $issuedon,
            get_string('badgeexpiresby', 'local_obf') => $assertion->get_expires()
        );

        $assertiondetails = html_writer::tag('h1', $badge->get_name(), array('class' => 'badgename'));
        foreach ($assertionitems as $key => $value) {
            $assertiondetails .= html_writer::tag('div', html_writer::tag('label', $key) . $value);
        }
        $html = local_obf_html::div(
                        local_obf_html::div(
                                html_writer::empty_tag('img',
                                        array('src' => $badge->get_image())),
                                'image-wrapper') .
                        local_obf_html::div(
                                local_obf_html::div(
                                         $assertiondetails,
                                        'assertion-details') .
                                local_obf_html::div(
                                         $issuerdetails,
                                        'issuer-details') .
                                local_obf_html::div(
                                        $badge->get_description(),
                                        'badge-details') .

                                local_obf_html::div(
                                        html_writer::link($badge->get_criteria_url().'#',
                                                get_string('showbadgecriteria', 'local_obf'),
                                                array('class' => 'view-criteria',
                                                        'data-id' => $badge->get_id(),
                                                        'data-url' => $badge->get_criteria_url())) .
                                        html_writer::tag('div', $badge->get_criteria_html(),
                                                array('class' => 'criteria-area')), 'criteria'), 'details-area')
                        ,
                        'obf-assertion-modal');
        return $html;
    }
    /**
     * Renders a definition list.
     *
     * @param array $items An array of items to render (key-value -pairs)
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
     * @param obf_client $client
     * @param obf_badge $badge
     * @param context $context
     * @param string $tab
     * @param int $page
     * @param string $message
     * @return string
     */
    public function page_badgedetails(obf_client $client, obf_badge $badge,
                                      context $context, $tab = 'details',
                                      $page = 0, $message = '', $onlydetailstab = null) {
        $methodprefix = 'print_badge_info_';
        $rendererfunction = $methodprefix . $tab;
        $html = ''; 

        if (!method_exists($this, $rendererfunction)) {
            $html .= $this->output->notification(get_string('invalidtab',
                            'local_obf'));
        } else {
            if (!empty($message)) {
                $html .= $this->output->notification($message, 'notifysuccess');
            }

            $html .= $this->print_badge_tabs($badge->get_id(), $context, $tab, $onlydetailstab);
            $html .= call_user_func(array($this, $rendererfunction), $client,
                    $badge, $context, $page);
        }

        return $html;
    }

    /**
     * @param obf_badge|null $badge
     * @param context|null $context
     * @param string $label
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function render_button(obf_badge $badge = null, context $context = null, $label = '') {
        global $PAGE;
        if (!is_null($badge)){
            $issueurl = new moodle_url('/local/obf/issue.php',
            array('id' => $badge->get_id()));
            if ($label === 'createcsv'){
                $issueurl = new moodle_url('/local/obf/badge.php',
                    array('id' => $badge->get_id(),
                        'action' => 'show',
                        'show'   => 'history',
                        'csv'    => '1'));
            }
        }

        if ($context instanceof context_course) {
            $issueurl->param('courseid', $context->instanceid);
        }

        $button = $this->output->single_button($issueurl,
                get_string($label, 'local_obf'), 'get');

        if ($_GET['csv'] == 1){
            $this->create_csv();
        }


        return local_obf_html::div($button);
    }

    /**
     * Renders the heading element in badge-details -page.
     *
     * @param obf_badge $badge
     * @param context $context
     * @return string HTML
     */
    public function render_badge_heading(obf_badge $badge, context $context) {
        $heading = $this->output->heading($this->print_badge_image($badge) . ' ' .
                $badge->get_name());
        $issueurl = new moodle_url('/local/obf/issue.php',
                array('id' => $badge->get_id()));

        if ($context instanceof context_course) {
            $issueurl->param('courseid', $context->instanceid);
        }
        return local_obf_html::div($heading, 'badgeheading');
    }

    /**
     * Renders the criterion-form.
     * @param obf_criterion_form $form
     * @return string HTML.
     */
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

    /**
     * Renders the criterion deletion form.
     * @param obf_criterion_deletion_form $form
     * @return string HTML.
     */
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
     * @param bool $hasissuecapability
     * @param context $context
     * @return string HTML
     */
    protected function render_badges($badges, $hasissuecapability,
                                     context $context) {
        $html = '';

        if (count($badges) === 0) {
            $html .= $this->output->notification(get_string('nobadges',
                            'local_obf'), 'notifynotice');
        } else {
            $items = '';

            foreach ($badges as $badge) {
                $badgeimage = $this->print_badge_image($badge, -1);
                $badgename = html_writer::tag('p', s($badge->get_name()), array('class' => 'badgename'));

                $url = new moodle_url('/local/obf/badge.php',
                        array('id' => $badge->get_id(), 'action' => 'show'));

                if ($context instanceof context_course) {
                    $url->param('courseid', $context->instanceid);
                }

                $items .= html_writer::tag('li',
                                local_obf_html::div(html_writer::link(
                                                $url, $badgeimage . $badgename), 'obf-badge'),
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
     * @param obf_client $client
     * @param obf_badge $badge
     * @param context $context
     * @return string HTML
     */
    public function print_badge_info_details(obf_client $client,
                                             obf_badge $badge, context $context) {
        $html = '';
        $badgeimage = $this->print_badge_image($badge,
                self::BADGE_IMAGE_SIZE_NORMAL);
        $createdon = $badge->get_created();
        $badgecreated = empty($createdon) ? '&amp;' : userdate($createdon,
                        get_string('dateformatdate', 'local_obf'));

        $boxes = local_obf_html::div($badgeimage, 'obf-badgeimage');
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

        // Issuer details table.
        $badgedetails .= $this->print_heading('issuerdetails');
        $badgedetails .= $this->render_issuer_details($issuer);

        $boxes .= local_obf_html::div($badgedetails, 'obf-badgedetails');
        $html .= local_obf_html::div($boxes, 'obf-badgewrapper');

        return $html;
    }

    /**
     * Render issuer details.
     *
     * @param obf_issuer $issuer
     * @return string HTML
     */
    public function render_issuer_details(obf_issuer $issuer) {
        $url = $issuer->get_url();
        $description = $issuer->get_description();
        $issuerurl = empty($url) ? '' : html_writer::link($url, $url);
        $html = $this->render_definition_list(array(
            get_string('issuername', 'local_obf') => $issuer->get_name(),
            get_string('issuerurl', 'local_obf') => $issuerurl,
            get_string('issuerdescription', 'local_obf') => empty($description) ? '-' : $description,
            get_string('issueremail', 'local_obf') => $issuer->get_email()
        ));

        return $html;
    }

    /**
     * Renders the badge criteria page.
     *
     * @param obf_client $client
     * @param obf_badge $badge
     * @param context $context
     * @return string HTML
     */
    public function print_badge_info_criteria(obf_client $client,
                                              obf_badge $badge, context $context) {
        $html = ''; 

        if ($context instanceof context_course) {
            $html .= $this->render_badge_criteria_course($badge,
                    $context->instanceid);
        } else {
            $html .= $this->render_badge_criteria_site($badge);
        }

        return $html;
    }

    /**
     * Renders the form for modifying the criteria of a single course
     *
     * @param obf_badge $badge
     * @param int $courseid
     * @return string HTML
     */
    public function render_badge_criteria_course(obf_badge $badge, $courseid) {
        $html = '';
        $criteria = $badge->get_completion_criteria();
        $coursewithcriterion = null;
        $criterioncourseid = null;
        $courseincriterion = false;
        $error = '';
        $url = new moodle_url('/local/obf/badge.php',
                array('id' => $badge->get_id(), 'action' => 'show',
                'show' => 'criteria', 'courseid' => $courseid));

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
                } else if ($criterion->is_met()) {
                    $error = 'cannoteditcriterion';
                } else {
                    $criterioncourseid = $courses[0]->get_id();
                }

                break;
            }
        }
        

        $canedit = !is_null($criterioncourseid) || !$courseincriterion;
        $course = get_course($courseid);
        $completionenabled = !empty($course) ? $course->enablecompletion : false;

        // The criteria cannot be modified or added -> show a message to user.
        if (!$canedit) {                         
            if (!is_null($coursewithcriterion) && $coursewithcriterion->is_met()) { 
                $items = $coursewithcriterion->get_items();

                $html .= html_writer::tag('p',
                            get_string('badgeissuedwhen', 'local_obf'));
                
                foreach ($criteria as $id => $criterion) {
                        $criterionhtml = '';
                        $attributelist = array();
                        $criterionitems = $criterion->get_items();
                        
                        $courseincriterion = $criterion->has_course($courseid); /* fixit */
                        
                        if (!$courseincriterion) {
                            continue;
                        }
                        
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

                        // The criterion can be edited if the criterion hasn't already been met.
                        $canedit = !$criterion->is_met();
                        $heading = $groupname;

                        // If the criterion can be edited, show the edit-icon.
                        if ($canedit) {
                            $editurl = new moodle_url($file,
                                    array('badgeid' => $badge->get_id(),
                                'action' => 'edit', 'id' => $id));
                            $heading .= local_obf_html::icon($editurl, 't/edit', 'edit');
                        }

                        $criterionhtml .= $this->output->heading(local_obf_html::div($heading), 3);

                        if (!$canedit) {
                            $criterionhtml .= $this->output->notification(get_string('cannoteditcriterion',
                                            'local_obf'));
                        }

                        if (count($criterionitems) > 1) {
                            $method = $criterion->get_completion_method() == obf_criterion::CRITERIA_COMPLETION_ALL ? 'all' : 'any';
                            $criterionhtml .= html_writer::tag('p',
                                            get_string('criteriacompletedwhen' . $method,
                                                    'local_obf'));
                        }

                        $criterionhtml .= html_writer::alist($attributelist);
                        $html .= $this->output->box($criterionhtml, 'generalbox service');
                    }
            } else {

                if (!is_null($coursewithcriterion) ) {
                     $html .= html_writer::tag('p',
                                            get_string('criteriapartofcourseset',
                                                    'local_obf'));
                }

            }

        } else if (!$completionenabled) { 
            $courseediturl = new moodle_url('/course/edit.php', array('id' => $courseid));
            $html .= $this->output->notification(get_string('coursecompletionnotenabled', 'local_obf', (string)$courseediturl));
        } else { // Show the course criteria form.
            $params = array(
                    'criteriatype' => optional_param('criteriatype', obf_criterion_item::CRITERIA_TYPE_UNKNOWN, PARAM_INT),
                    'courseid' => optional_param('courseid', null, PARAM_INT));

            if (!is_null($coursewithcriterion)) {
                $params = array_merge($params, array('obf_criterion_id' => $coursewithcriterion->get_id()));
            }
            if (is_null($criterioncourseid)) {
                $criterioncourse = obf_criterion_item::build($params);
            } else {
                $criterioncourse = obf_criterion_item::get_instance($criterioncourseid);
            }

            $criterionform = new obf_coursecriterion_form($url,
                    array('criterioncourse' => $criterioncourse));

            // Deleting the rule is done via cancel-button.
            if ($criterionform->is_cancelled()) {
                $criterioncourse->delete();
                redirect(new moodle_url('/local/obf/badge.php',
                        array('id' => $badge->get_id(),
                    'action' => 'show', 'show' => 'criteria', 'courseid' => $courseid,
                    'msg' => get_string('criteriondeleted', 'local_obf'))));
            }

            if (!is_null($data = $criterionform->get_data())) {

                if (!$criterioncourse->exists()) {
                    $criterion = new obf_criterion();
                    $criterion->set_badge($badge);
                    $criterion->set_completion_method(obf_criterion::CRITERIA_COMPLETION_ALL);
                    $criterion->set_items($criterioncourse);
                }

                if (isset($data->criteriaaddendum)) {
                    $criterion->set_criteria_addendum($data->criteriaaddendum);
                }
                if (isset($data->addcriteriaaddendum)) {
                    $criterion->set_use_addendum($data->addcriteriaaddendum);
                }

                if (!is_null($courseid)) {
                    $criterioncourse->set_courseid((int)$courseid);
                }

                $pickingtype = ((!$criterioncourse->exists() && !$criterioncourse->is_createable_with_params($_REQUEST)) ||
                        $criterioncourse->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_UNKNOWN ||
                        property_exists($data, 'picktype') && $data->picktype === 'yes');

                if ($pickingtype) {
                    if (!is_null($courseid)) {

                        $criterionform = new obf_coursecriterion_form($url,
                                array('criterioncourse' => $criterioncourse));
                    }
                } else { // Saving the rule.
                    if (!$criterioncourse->exists()) {
                        // Update or insert criterion course
                        // Object doesn't exist yet, let's create the criterion.
                        $criterion->save();
                        $criterioncourse->set_criterionid($criterion->get_id());
                    }

                    $midgrades = property_exists($data, 'mingrade') ? $data->mingrade : array();
                    $grade = null;
                    $completedby = null;
                    foreach ($midgrades as $key => $value) {
                        $grade = (int) $data->mingrade[$key];
                        if (property_exists($data, 'completedby_'.$key)) {
                            $completedby = (int) $data->{'completedby_'.$key};
                        }
                    }
                    $criterioncourse->set_grade($grade);
                    $criterioncourse->set_completedby($completedby);

                    $criterioncourse->save_params($data);

                    $redirecturl = new moodle_url('/local/obf/badge.php',
                            array('id' => $badge->get_id(),
                        'action' => 'show', 'show' => 'criteria', 'courseid' => $courseid,
                        'msg' => get_string('criterionsaved', 'local_obf')));

                    if (property_exists($data, 'reviewaftersave') && $data->reviewaftersave) {
                        $crit = $criterioncourse->get_criterion();
                        if ($crit) {
                            $recipientcount = $crit->review_previous_completions(); 
                        } else {
                            $recipientcount = 0;
                        }
                        
                        $redirecturl->param('msg',
                                get_string('badgewasautomaticallyissued',
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
     * @return string HTML
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
            $multiplecourseactivities = 0;
            
            if (count($criterionitems) == 1) {
                $criteriontype = obf_criterion_item::get_criterion_type_text($criterionitems[0]->get_criteriatype());
            }
            
            // if activities from multiple courses
            if (count($criterionitems) > 1 && get_class($criterionitems[0]) == "obf_criterion_activity") {
                $criteriontype = obf_criterion_item::get_criterion_type_text($criterionitems[0]->get_criteriatype());
                $multiplecourseactivities = 1;
            }
            
            $groupname = get_string('criteriatype' . $criteriontype, 'local_obf');

            if ($criterionitems[0]->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_TOTARA_CERTIF) {
                $groupname = get_string('criteriatypetotaracertif', 'local_obf');
            }
            

            if ($multiplecourseactivities) {
                $attributelist = $criterionitems[0]->get_text_array();
            } else {
                foreach ($criterionitems as $item) { 
                    $attributelist = array_merge($attributelist, $item->get_text_array());
                }  
            }

            // The criterion can be edited if the criterion hasn't already been met.
            $canedit = !$criterion->is_met();
            $heading = $groupname;

            

            $deleteurl = new moodle_url($file,
                    array('badgeid' => $badge->get_id(),
                'action' => 'delete', 'id' => $id));
            $heading .= local_obf_html::icon($deleteurl, 't/delete', 'delete');

            // If the criterion can be edited, show the edit-icon.
            if ($canedit) {
                $editurl = new moodle_url($file,
                        array('badgeid' => $badge->get_id(),
                    'action' => 'edit', 'id' => $id));
                $heading .= local_obf_html::icon($editurl, 't/edit', 'edit');
            }

            
            $criterionhtml .= $this->output->heading(local_obf_html::div($heading), 3);
            
            if ($multiplecourseactivities) {
              global $DB;
                foreach ($criterionitems as $item) { 
                    $course = $DB->get_record('course', array('id' => $courseid = $item->get_courseid()));
                    $criterionhtml .= $this->output->heading(local_obf_html::div($course->fullname), 5);
                }
            } elseif ($criteriontype == "activity") {
              global $DB;
              $course = $DB->get_record('course', array('id' => $courseid = $item->get_courseid()));
              $criterionhtml .= $this->output->heading(local_obf_html::div($course->fullname), 5); 

            } 

           

            if (!$canedit) {
                $criterionhtml .= $this->output->notification(get_string('cannoteditcriterion',
                                'local_obf'));
            }

            if (count($criterionitems) > 1 && !$multiplecourseactivities) {
                
                $method = $criterion->get_completion_method() == obf_criterion::CRITERIA_COMPLETION_ALL ? 'all' : 'any';
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
     * @param obf_client $client
     * @param obf_badge $badge
     * @param context $context
     * @param int $currentpage
     * @param obf_issue_event[] $eventfilter
     * @return string HTML
     */
    public function print_badge_info_history(obf_client $client,
                                             obf_badge $badge = null,
                                             context $context, $currentpage = 0,
                                             $eventfilter = null) {
        global $PAGE;
        $singlebadgehistory = !is_null($badge);
        $history = $singlebadgehistory ? $badge->get_assertions() : obf_assertion::get_assertions($client);
        if (!is_null($eventfilter)) {
            $eventidfilter = array();
            foreach ($eventfilter as $event) {
                $eventidfilter[] = $event->get_eventid();
            }
            $newhistory = new obf_assertion_collection();
            foreach ($history as $assertion) {
                if (in_array($assertion->get_id(), $eventidfilter)) {
                    $newhistory->add_assertion($assertion);
                }
            }
            $history = $newhistory;
        }
        $historytable = new html_table();
        $historytable->attributes = array('class' => 'local-obf generaltable historytable');
        $html = $this->print_heading('history', 2);

        if($PAGE->url->get_param('action') != 'history') {
            $csvbutton = $this->render_button($badge, null, 'createcsv');
            $html .= $csvbutton;
        }
        $historysize = count($history);
        $langkey = $singlebadgehistory ? 'nobadgehistory' : 'nohistory';

        if ($historysize == 0) {
            $html .= $this->output->notification(get_string($langkey,
                            'local_obf'), 'generalbox');
        } else {
            // Paging settings.
            $perpage = 10; // TODO: Hard-coded here.
            if ($PAGE->pagetype == 'local-obf-courseuserbadges') {
              $path = '/local/obf/courseuserbadges.php';
            } else {
              $path = '/local/obf/badge.php';
            }
            
            $urlparams = $singlebadgehistory ? array('action' => 'show', 'id' => $badge->get_id(),
                    'show' => 'history') : array('action' => 'history');
            if (!$singlebadgehistory && $context instanceof context_course) {
              $urlparams['courseid'] = $context->instanceid;
            }
            /*$url = $singlebadgehistory ? new moodle_url($path, array('action' => 'show', 'id' => $badge->get_id(),
                    'show' => 'history')) : new moodle_url($path, array('action' => 'history'));*/
            $url = new moodle_url($path, $urlparams);
            $pager = new paging_bar($historysize, $currentpage, $perpage, $url,
                    'page');
            $htmlpager = $this->render($pager);
            $startindex = $currentpage * $perpage;
            $endindex = $startindex + $perpage > $historysize ? $historysize : $startindex + $perpage;

            // Heading row.
            $headingrow = array();

            if (!$singlebadgehistory) {
                $headingrow[] = new local_obf_table_header('badgename');
                $historytable->headspan = array(2, 1, 1, 1, 1);
            } else {
                $historytable->headspan = array();
            }

            $headingrow[] = new local_obf_table_header('recipients');
            $headingrow[] = new local_obf_table_header('issuedon');
            $headingrow[] = new local_obf_table_header('expiresby');
            $headingrow[] = new local_obf_table_header('issuedfrom');
            $headingrow[] = new html_table_cell();
            $historytable->head = $headingrow;

            // Add history rows.
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
     * @param bool $singlebadgehistory
     * @param string $path
     * @param array $users
     * @return \html_table_row
     */
    private function render_historytable_row(obf_assertion $assertion,
                                             $singlebadgehistory, $path,
                                             array $users) {

        global $PAGE;

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
            } else {
                $row->cells[] = '&nbsp;';
                $row->cells[] = s($assertion->get_name());
            }
        }

        $recipienthtml = '';
        $badge_id = $PAGE->url->get_param('id');
        $logs = $assertion->get_log_entry('course_id');
        $activity = $assertion->get_log_entry('activity_name');

        if (!empty($logs)) {
            $courses = $this->get_course_name($logs);
            if (!empty($activity)){
                $courses .=   ' (' . $activity .')';
            }
        }
        else {$courses = 'Manual issuing';}

        if (count($users) > 1) {
            $recipienthtml .= html_writer::tag('p',
                            get_string('historyrecipients', 'local_obf',
                                    count($users)),
                            array('title' => $this->render_userlist($users,
                                false)));
        } else {
            $recipienthtml .= $this->render_userlist($users);
        }

        $row->cells[] = $recipienthtml;
        $row->cells[] = userdate($assertion->get_issuedon(),
                get_string('dateformatdate', 'local_obf'));
        $row->cells[] = $expirationdate;
        $row->cells[] = $courses;
        $row->cells[] = html_writer::link(new moodle_url('/local/obf/event.php',
                        array('id' => $assertion->get_id())),
                        get_string('showassertion', 'local_obf'));

        return $row;
    }

    /**
     * @param $course_id
     * @return mixed
     * @throws dml_exception
     */
    private function get_course_name($course_id)
    {
        global $DB;
        $result = $DB->get_record('course', array('id' => $course_id));
        return $result->fullname;
    }
    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    private function create_csv() {
        global $PAGE;
        $badgeid = $PAGE->url->get_param('id');
        $badge = obf_badge::get_instance($badgeid);
        $history = $badge->get_assertions();
        $assertion_count = $badge->get_assertions()->count();
        $filename = $badge->get_name() . '.csv';

        header("Content-Type: text/csv");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $file = fopen('php://output', 'w');
        fputcsv($file, array(get_string('recipients', 'local_obf'),
                get_string('issuedon', 'local_obf'),
                get_string('expiresby', 'local_obf'),
                get_string('issuedfrom', 'local_obf')
            )
        );
        $data = array();

        for ($i = 0; $i < $assertion_count; $i++){
            try {
                $assertion = $history->get_assertion($i);
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
            $users = $history->get_assertion_users($assertion);
            $name = $this->render_userlist($users, false);
            $data['name'] = $name;
            $issued_on = userdate($assertion->get_issuedon(),
                get_string('dateformatdate', 'local_obf'));
            $data['issuedon'] = $issued_on;
            $expires = $assertion->get_expires();

            if ($expires != null){
                $expires = userdate($expires,
                    get_string('dateformatdate', 'local_obf'));
            }
            else { $expires = "-"; }

            $data['expires'] = $expires;
            $course = $assertion->get_log_entry("course_id");
            $course_name = $this->get_course_name($course);
            $activity = $assertion->get_log_entry('activity_name');

            if ($course_name !== null) {
                $data['course'] = $course_name;
                if (!empty($activity)){
                    $data['course'] .= ' (' . $activity . ')';
                }
            }

            else { $data['course'] = 'Manual issuing'; }
            fputcsv($file, $data);
        }
        fclose($file);
        exit();
    }

    /**
     * Renders a list of users.
     *
     * @param array $users
     * @param bool $addlinks
     * @return string User list as HTML
     */
    private function render_userlist(array $users, $addlinks = true) {
        $userlist = array();

        foreach ($users as $user) {
            if (is_string($user)) {
                $userlist[] = $user;
            } else {
                if ($addlinks) {
                    $url = new moodle_url('/user/view.php',
                            array('id' => $user->id));
                    $userlist[] = html_writer::link($url, fullname($user),
                                    array('title' => $user->email));
                } else {
                    $userlist[] = fullname($user);
                }
            }
        }

        return implode(', ', $userlist);
    }

    /**
     * Renders the tabs in badge-page.
     *
     * @param string|int $badgeid
     * @param context $context
     * @param string $selectedtab
     * @return string HTML
     */
    public function print_badge_tabs($badgeid, context $context,
                                     $selectedtab = 'details', $onlydetailstab = null) {

        if ($onlydetailstab != 1) {
            $tabdata = array('details', 'criteria');
        }
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
        } else { // Moodle 2.2.
            $tabhtml = self::render_tabs($tabs, $selectedtab);
        }

        return $tabhtml;
    }

    /**
     * For Moodle 2.2. tabtree replacement.
     * @param array $tabs
     * @param string $selectedtab
     * @return string HTML
     */
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
     * @return string HTML
     */
    public function render_obf_config_form(obf_config_form $form) {
        $html = $this->print_heading('obfconnectionconfig', 2);
        $html .= $form->render();

        return $html;
    }
    
    /**
     * Renders the OBF configuration form
     *
     * @param obf_config_form $form
     * @return string HTML
     */
    public function render_obf_settings_form(obf_settings_form $form) {
        $html = $this->print_heading('settings', 2);
        $html .= $form->render();

        return $html;
    }


    /**
     * Renders the badge exporter form.
     *
     * @param obf_badge_export_form $form
     * @return string HTML
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
     * @param string $errormsg
     * @return string HTML
     */
    public function render_userconfig(obf_userconfig_form $form, $errormsg = '') {
        $html = $this->print_heading('obfuserpreferences', 2);

        if (!empty($errormsg)) {
            $html .= $this->output->notification($errormsg);
        }

        $html .= $form->render();
        $url = new moodle_url('/local/obf/verifyemail.php');

        require_once(__DIR__ . '/form/useremail.php');
        $verifyform = new obf_user_email_form();

        //$this->page->requires->js(new moodle_url('https://login.persona.org/include.js'));
        /*$this->page->requires->yui_module('moodle-local_obf-emailverifier',
                'M.local_obf.init_emailverifier',
                array(array('url' => $url->out(), 'verifyform' => $verifyform->render())));*/
        //$this->page->requires->jquery_plugin('obf-emailverifier', 'local_obf');
        $params = array(array(
            'url' => $url->out(),
            'selector' => '.verifyemail,.verifyobpemail',
            'verifyform' => $verifyform->render()
        ));
        if (method_exists($this->page->requires, 'js_call_amd')) {
            //$this->page->requires->js_call_amd('local_obf/emailverifier', 'initialise', $params);
        }
        $this->page->requires->js_init_call('LOCAL_OBF_EMAILVERIFIER.initialise', $params);

        return $html;
    }
    /**
     * Renders the OBF-blacklist form.
     *
     * @param obf_blacklist_form $form
     * @param string $errormsg
     * @return string HTML
     */
    public function render_blacklistconfig(obf_blacklist_form $form, $errormsg = '') {
        $html = $this->print_heading('badgeblacklist', 2);

        if (!empty($errormsg)) {
            $html .= $this->output->notification($errormsg);
        }

        $html .= $form->render();
        return $html;
    }
    /**
     * Render table of users who are participating a course.
     *
     * @param int $courseid
     * @param array $participants
     * @return string HTML.
     */
    public function render_course_participants($courseid, array $participants) {
        $html = '';
        $html .= $this->print_heading('courseuserbadges');

        if (count($participants) === 0) {
            $html .= $this->output->notification(get_string('noparticipants',
                            'local_obf'));
        } else {
            $table = new html_table();
            $userpicparams = array('size' => 16, 'courseid' => $courseid);
            $provider = obf_backpack::BACKPACK_PROVIDER_MOZILLA;
            $userswithbackpack = obf_backpack::get_user_ids_with_backpack($provider);

            $table->id = 'obf-participants';
            $table->attributes = array('class' => 'local-obf generaltable');

            // Some of the formatting taken from user/index.php.
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
                $link = html_writer::link('#', get_string('showbadges', 'local_obf'));
                $linkcell = new html_table_cell($link);

                $linkcell->attributes = array('class' => 'show-badges');

                $row->cells = array(
                    $this->output->user_picture($user, $userpicparams),
                    html_writer::link(new moodle_url('/user/view.php',
                            array('id' => $user->id, 'course' => $courseid)), fullname($user)),
                    $user->city,
                    $lastaccess,
                    $linkcell
                );

                $table->data[] = $row;
            }

            $html .= html_writer::table($table);
            $url = new moodle_url('/local/obf/userbadges.php', array('provider' => $provider));
            $params = $this->get_displayer_params(null, null, null);
            $params['url'] = $url->out();
            $criteriaurl = new moodle_url('/local/obf/criteriapreview.php');
            $params['criteria_baseurl'] = $criteriaurl->out();

            $this->page->requires->yui_module('moodle-local_obf-courseuserbadgedisplayer',
                    'M.local_obf.init_courseuserbadgedisplayer', array($params));
            $this->page->requires->string_for_js('closepopup', 'local_obf');
            $this->page->requires->string_for_js('blacklistbadge', 'local_obf');
        }

        return $html;
    }
    /**
     * Get parameters to pass to javascript displayer.
     * @param array $jsassertions Assertions in json_encoded form.
     * @param string $elementid ID of element to bind to.
     * @param int $userid
     * @return array params.
     */
    private function get_displayer_params($jsassertions = null, $elementid = null, $userid = null) {
        global $USER;
        $assertion = $this->get_template_assertion();
        $params = array(
            'tpl' => array(
                'list' => html_writer::tag('ul', '{{{ this.content }}}',
                        array('class' => 'badgelist')),
                'assertion' => $this->render_assertion($assertion, false, null, true),
                'badge' => html_writer::tag('li',
                        local_obf_html::div(
                                html_writer::empty_tag('img',
                                        array('src' => '{{{ this.badge.image }}}')) .
                                html_writer::tag('p', '{{ this.badge.name }}', array('class' => 'badgename')), 'obf-badge'),
                        array('title' => '{{ this.badge.name }}', 'id' => '{{ this.id }}'))
        ));
        if (!empty($jsassertions)) {
            $params['assertions'] = $jsassertions;
        }
        if (!empty($elementid)) {
            $params['elementid'] = $elementid;
        }
        $criteriaurl = new moodle_url('/local/obf/criteriapreview.php');
        $params['criteria_baseurl'] = $criteriaurl->out();

        $brandingurl = new moodle_url('/local/obf/pix/branding-obf-45px.png');
        
        $isverified = get_config('local_obf', 'verified_client');
        $verifiedbyurl = get_config('local_obf','verified_by_image_url');
        $issuedbyurl = get_config('local_obf','issued_by_image_url');
        
        $brandingurl = $isverified == 1 && !empty($verifiedbyurl) ? 
                $verifiedbyurl : 
                (!empty($issuedbyurl) ? $issuedbyurl : $brandingurl->out());
        
        $params['branding_urls'] = array(obf_assertion::ASSERTION_SOURCE_OBF => $brandingurl);
        $params['verified_by_image_url'] = $verifiedbyurl;
        $params['issued_by_image_url'] = $issuedbyurl;
        $params['obf_api_url'] = obf_client::get_api_url();

        if (!empty($userid) && $userid == $USER->id) {
            $blacklisturl = new moodle_url('/local/obf/blacklist.php');
            $params['blacklist_params'] = array(
                    'action' => 'addbadge', 'sesskey' => sesskey());
            $params['blacklist_url'] = $blacklisturl->out();
            $params['blacklistable'] = true;
        }

        return $params;
    }
    /**
     * Get an assertion template.
     * @return obf_assertion Template assertion.
     */
    private function get_template_assertion() {
        $issuer = obf_issuer::get_instance_from_arr(array('id' => '', 'description' => '{{ this.badge.issuer.description }}',
                    'email' => '{{ this.badge.issuer.email }}', 'url' => '{{ this.badge.issuer.url }}',
                    'name' => '{{ this.badge.issuer.name }}'));
        $badge = new obf_badge();
        $badge->set_id('{{ this.badge.id }}');
        $badge->set_name('{{ this.badge.name }}');
        $badge->set_description('{{ this.badge.description }}');
        $badge->set_issuer($issuer);
        $badge->set_image('{{{ this.badge.image }}}');
        $badge->set_criteria_url('{{{ this.badge.criteria_url }}}');
        $badge->set_criteria_html('{{{ this.badge.criteria_html }}}');

        $assertion = new obf_assertion();
        $assertion->set_badge($badge);
        $assertion->set_issuedon('{{{ this.issued_on }}}');
        $assertion->set_expires('{{{ this.expires }}}');

        return $assertion;
    }

}
/**
 * Badge renderer.
 *
 * @package    local_obf
 * @copyright  20013-2015 Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @todo Check if deprecated.
 */
class local_obf_badge_renderer extends plugin_renderer_base {
    /**
     * Generates the HTML for the badge tabs.
     * @param string $badgeid
     * @param string $selectedtab
     * @return string HTML
     */
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
        } else {
            return local_obf_renderer::render_tabs($tabs, $selectedtab);
        }
    }
    /**
     * Generates the HTML for the badge page.
     *
     * @param obf_badge $badge
     * @param string $tab Selected tab
     * @param string $content
     * @return string HTML
     */
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
/**
 * Table header -class.
 *
 * @package    local_obf
 * @copyright  20013-2015 Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_obf_table_header extends html_table_cell {
    /**
     * Constructor
     * @param string $stringid
     */
    public function __construct($stringid = null) {
        $this->header = true;
        parent::__construct(is_null($stringid) ? null : get_string($stringid,
                                'local_obf'));
    }

}
/**
 * Class for some custom html function.
 *
 * @package    local_obf
 * @copyright  20013-2015 Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_obf_html {

    /**
     * Renders an icon.
     *
     * @param moodle_url $url
     * @param string $icon
     * @param string $langkey
     * @return string HTML
     */
    public static function icon(moodle_url $url, $icon, $langkey) {
        global $OUTPUT;
        return $OUTPUT->action_icon($url,
                        new pix_icon($icon, get_string($langkey), null,
                        array('class' => 'obf-icon')));
    }
    /**
     * Renders a div.
     * @param string $content
     * @param string $classes
     * @return string HTML.
     */
    public static function div($content, $classes = '') {
        return html_writer::tag('div', $content,
                        empty($classes) ? array() : array('class' => $classes));
    }

}
