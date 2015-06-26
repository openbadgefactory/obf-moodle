<?php

defined('MOODLE_INTERNAL') or die();

global $CFG;

require_once(__DIR__ . '/obfform.php');
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once 'HTML/QuickForm/element.php';

class obf_issuance_form extends local_obf_form_base {

    /**
     * @var obf_badge
     */
    private $badge = null;
    private $courseid = null;

    /**
     *
     * @var local_obf_renderer
     */
    private $renderer = null;

    protected function definition() {
        $this->badge = $this->_customdata['badge'];
        $this->renderer = $this->_customdata['renderer'];
        $this->courseid = $this->_customdata['courseid'];

        $this->add_details_elements();
        $this->add_recipients_elements();
        $this->add_message_elements();
        $this->add_action_buttons(true, get_string('issue', 'local_obf'));
    }

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
                array('optional' => true, 'startyear' => date('Y'), 'stopyear' => date('Y')
            + 20));

        if ($this->badge->has_expiration_date()) {
            $mform->setDefault('expiresby',
                    $this->badge->get_default_expiration_date());
        }
    }

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

    private function add_message_elements() {
        require_once(__DIR__ . '/emailtemplate.php');

        $mform = $this->_form;
        $mform->addElement('header', 'badgeemailheader',
                get_string('editemailmessage', 'local_obf'));

        obf_email_template_form::add_email_fields($mform,
                $this->badge->get_email());
    }

    private function get_user_ids_with_badge_issued() {
        global $DB;

        $assertions = $this->badge->get_non_expired_assertions();
        $ids = array();
        $emails = array();

        foreach ($assertions as $issuance) {
            $emails = array_merge($emails, $issuance->get_recipients());
        }

        $users = $DB->get_records_list('user', 'email', $emails);

        foreach ($users as $user) {
            $ids[] = $user->id;
        }

        return $ids;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // To apply error messages to correct elements, use the following format:
        // $errors['recipientlist'] = 'Error text here';

        return $errors;
    }

}

class MoodleQuickForm_userselector extends HTML_QuickForm_element {

    protected $userselector;
    protected $strHtml;
    protected $name = '';

    public function MoodleQuickForm_userselector($elementName = null,
            $elementLabel = null, $options = null, $attributes = null) {
        parent::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->setName($elementName);
        $this->userselector = new badge_recipient_selector($elementName,
                $options);
        $this->userselector->set_multiselect(true);

        if (is_array($attributes) && isset($attributes['exclude'])) {
            $this->userselector->exclude($attributes['exclude']);
        }
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function toHtml() {
        $this->strHtml = $this->userselector->display(true);
        return $this->strHtml;
    }

    public function getValue() {
        return $this->userselector->get_selected_users();
    }

}

class badge_recipient_selector extends user_selector_base {

    const MAX_USERS_IN_LIST = 5000;

    private $existingrecipients = array();
    private $courseid = null;

    public function __construct($name, $options = array()) {
        parent::__construct($name, $options);

        if (isset($options['courseid'])) {
            $this->courseid = $options['courseid'];
        }
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['file'] = 'local/obf/form/issuance.php';
        $options['courseid'] = $this->courseid;
        return $options;
    }

    public function set_courseid($id) {
        $this->courseid = $id;
    }

    /**
     *
     * @global moodle_database $DB
     * @param type $search
     * @return type
     */
    public function find_users($search) {
        global $DB;

        $tablealias = 'u';
        $whereclauses = array();

        // Get the WHERE-part of the query
        list($where, $params) = $this->search_sql($search, $tablealias);

        if ($where) {
            $whereclauses[] = $where;
        }

        // Select only users without the current badge
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
