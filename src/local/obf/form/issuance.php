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
 * Issuance form.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

global $CFG;

require_once(__DIR__ . '/obfform.php');
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once('HTML/QuickForm/element.php');

/**
 * Manual issue form.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_issuance_form extends local_obf_form_base {

    /**
     * @var obf_badge
     */
    private $badge = null;
    /**
     * Course id
     * @var int
     */
    private $courseid = null;

    /**
     *
     * @var local_obf_renderer
     */
    private $renderer = null;
    /**
     * Defines forms elements
     */
    protected function definition() {
        $this->badge = $this->_customdata['badge'];
        $this->renderer = $this->_customdata['renderer'];
        $this->courseid = $this->_customdata['courseid'];

        $this->add_details_elements();
        $this->add_recipients_elements();
        $this->add_message_elements();
        $this->add_criteria_addendum_elements();
        $this->add_action_buttons(true, get_string('issue', 'local_obf'));
    }
    /**
     * Add details elements
     */
    private function add_details_elements() {
        $mform = $this->_form;
        $mform->addElement('header', 'badgedetailsheader',
                get_string('badgedetails', 'local_obf'));
        $mform->addElement('static', 'badgename',
                get_string('badgename', 'local_obf'),
                $this->renderer->print_badge_image($this->badge,
                        local_obf_renderer::BADGE_IMAGE_SIZE_TINY) .
                ' ' . $this->badge->get_name());
        $mform->addElement('static', 'badgedescription',
                get_string('badgedescription', 'local_obf'),
                $this->badge->get_description());
        $mform->addElement('date_selector', 'issuedon',
                get_string('issuedon', 'local_obf'),
                array('stopyear' => date('Y') + 1));
        $mform->addElement('date_selector', 'expiresby',
                get_string('expiresby', 'local_obf'),
                array('optional' => true, 'startyear' => date('Y'),
                        'stopyear' => date('Y') + 20));

        if ($this->badge->has_expiration_date()) {
            $mform->setDefault('expiresby',
                    $this->badge->get_default_expiration_date());
        }
    }

    /**
     * Add recipients elements.
     */
    private function add_recipients_elements() {
        $mform = $this->_form;
        $mform->addElement('header', 'badgerecipientsheader',
                get_string('selectrecipients', 'local_obf'));
        $excludes = $this->get_user_ids_with_badge_issued();
        $mform->registerElementType('obf_user_selector', __FILE__,
                'MoodleQuickForm_userselector');
        $mform->addElement('obf_user_selector', 'recipientlist',
                get_string('selectrecipients', 'local_obf'),
                array('courseid' => $this->courseid),
                array('exclude' => $excludes));
        $mform->addRule('recipientlist',
                get_string('selectatleastonerecipient', 'local_obf'), 'required');
    }

    /**
     * Add message elements.
     */
    private function add_message_elements() {
        require_once(__DIR__ . '/emailtemplate.php');

        $mform = $this->_form;
        $mform->addElement('header', 'badgeemailheader',
                get_string('editemailmessage', 'local_obf'));

        obf_email_template_form::add_email_fields($mform,
                $this->badge->get_email());
    }
    
    /**
     * Add message elements.
     */
    private function add_criteria_addendum_elements() {

        $mform = $this->_form;
        $mform->addElement('header', 'badgecriteriaaddendumheader',
                get_string('criteriaaddendumheader', 'local_obf'));

        $mform->addElement('advcheckbox', 'addcriteriaaddendum', get_string('criteriaaddendumadd', 'local_obf'));
        $mform->addElement('textarea', 'criteriaaddendum', get_string('criteriaaddendum', 'local_obf'));
        $mform->addHelpButton('criteriaaddendum', 'criteriaaddendum', 'local_obf');
    }

    /**
     * Get user ids with badge issued.
     * @return int[]
     */
    private function get_user_ids_with_badge_issued() {
        global $DB;

        $assertions = $this->badge->get_non_expired_assertions();
        $ids = array();
        $emails = array();

        foreach ($assertions as $issuance) {
            $emails = array_merge($emails, $issuance->get_valid_recipients());
        }

        $users = $DB->get_records_list('user', 'email', $emails);

        foreach ($users as $user) {
            $ids[] = $user->id;
        }

        return $ids;
    }

    /**
     * Validation.
     * @param  stdClass $data
     * @param  array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // To apply error messages to correct elements, use the following format:
        // $errors['recipientlist'] = 'Error text here';.

        return $errors;
    }

}

/**
 * User selector form element.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_userselector extends HTML_QuickForm_element {

    /**
     * User selector.
     * @var badge_recipient_selector
     */
    protected $userselector;
    /**
     * HTML string.
     * @var string
     */
    protected $strHtml;
    /**
     * Name.
     * @var string
     */
    protected $name = '';

    /**
     * Constructor.
     * @param string $name
     * @param string $label
     * @param array $options
     * @param array $attributes
     */
    public function __construct($name = null,
            $label = null, $options = null, $attributes = null) {
        parent::HTML_QuickForm_element($name, $label, $attributes);
        $this->setName($name);
        $this->userselector = new badge_recipient_selector($name,
                $options);
        $this->userselector->set_multiselect(true);

        if (is_array($attributes) && isset($attributes['exclude'])) {
            $this->userselector->exclude($attributes['exclude']);
        }
    }

    /**
     * Get name.
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set name.
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Get HTML of this selector element.
     * @return string HTML
     */
    public function toHtml() {
        $this->strHtml = $this->userselector->display(true);
        return $this->strHtml;
    }

    /**
     * Get value.
     * @return mixed
     */
    public function getValue() {
        return $this->userselector->get_selected_users();
    }

}

/**
 * Badge recipient selector.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge_recipient_selector extends user_selector_base {
    /**
     * Max amount of users in list.
     */
    const MAX_USERS_IN_LIST = 5000;

    /**
     * Recipients who have already received the badge.
     * @var array
     */
    private $existingrecipients = array();
    /**
     * Course id.
     * @var int
     */
    private $courseid = null;

    /**
     * Constructor.
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options = array()) {
        parent::__construct($name, $options);

        if (isset($options['courseid'])) {
            $this->courseid = $options['courseid'];
        }
    }

    /**
     * Get options.
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/obf/form/issuance.php';
        $options['courseid'] = $this->courseid;
        return $options;
    }

    /**
     * Set course id.
     * @param int $id
     */
    public function set_courseid($id) {
        $this->courseid = $id;
    }

    /**
     * Find users from the database.
     *
     * @param string $search
     * @return array
     * @todo Is this used anymore? If not, remove.
     */
    public function find_users($search) {
        global $DB;

        $tablealias = 'u';
        $whereclauses = array();

        // Get the WHERE-part of the query.
        list($where, $params) = $this->search_sql($search, $tablealias);

        if ($where) {
            $whereclauses[] = $where;
        }

        // Select only users without the current badge.
        if (count($this->existingrecipients) > 0) {
            list($emailin, $emailparams) = $DB->get_in_or_equal($this->existingrecipients,
                    SQL_PARAMS_NAMED, 'obf', false);
            $whereclauses[] = 'u.email ' . $emailin;
            $params = array_merge($params, $emailparams);
        }

        if (count($whereclauses) > 0) {
            $wheresql = ' WHERE ' . implode(' AND ', $whereclauses);
        }

        $enrolledsql = '';

        if (!empty($this->courseid)) {
            $context = context_course::instance($this->courseid);
            list ($enrolledsql, $enrolledparams) = get_enrolled_sql($context,
                    'local/obf:earnbadge', 0, true);
            $params = array_merge($params, $enrolledparams);
        }

        list($sort, $sortparams) = users_order_by_sql($tablealias, $search);

        $fields = 'SELECT ' . $this->required_fields_sql($tablealias);
        $count = 'SELECT COUNT(' . $tablealias . '.id)';
        $sql = ' FROM {user} ' . $tablealias;

        if (!empty($enrolledsql)) {
            $sql .= ' JOIN (' . $enrolledsql . ') eu ON eu.id = ' . $tablealias . '.id';
        }

        $sql .= $wheresql;
        $orderby = ' ORDER BY ' . $sort;

        // Check how many users does the query return and return an error if the number
        // of users is too damn high.
        if (!$this->is_validating()) {
            $usercount = $DB->count_records_sql($count . $sql, $params);
            if ($usercount > self::MAX_USERS_IN_LIST) {
                return $this->too_many_results($search, $usercount);
            }
        }

        $users = $DB->get_records_sql($fields . $sql . $orderby,
                array_merge($params, $sortparams));

        return array(get_string('recipientcandidates', 'local_obf') => $users);
    }

}
