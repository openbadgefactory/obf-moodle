<?php

defined('MOODLE_INTERNAL') or die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once 'HTML/QuickForm/element.php';

/**
 * Form handling the badge details.
 */
class badge_details_form extends moodleform {

    protected function definition() {
        global $CFG;

        $badge = $this->_customdata['badge'];
        $mform = $this->_form;

        $mform->addElement('date_selector', 'issuedon', get_string('issuedon', 'local_obf'), array('stopyear' => date('Y') + 1));
        $mform->addElement('date_selector', 'expiresby', get_string('expiresby', 'local_obf'), array('optional' => true, 'startyear' => date('Y'), 'stopyear' => date('Y') + 20));

        if ($badge->has_expiration_date()) {
            $mform->setDefault('expiresby', $badge->get_expiration_date());
        }

        $mform->disabledIf('expiresby', !$badge->has_expiration_date());
    }

}

/**
 * Form handling the selection of the badge recipients.
 */
class badge_recipients_form extends moodleform {

    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $mform->registerElementType('obf_user_selector', __FILE__, 'MoodleQuickForm_userselector');
        $mform->addElement('obf_user_selector', 'recipientlist', get_string('selectrecipients', 'local_obf'));
    }

}

class badge_email_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'emailsubject', get_string('emailsubject', 'local_obf'));
        $mform->addElement('textarea', 'emailbody', get_string('emailbody', 'local_obf'), array('rows' => 10));
        $mform->addElement('textarea', 'emailfooter', get_string('emailfooter', 'local_obf'), array('rows' => 5));
    }

}

class badge_confirmation_form extends moodleform {
    protected function definition() {
        $badge = $this->_customdata['badge'];       
        $mform = $this->_form;
        
        $mform->addElement('static', 'confirm-issuedon', get_string('issuedon', 'local_obf'), html_writer::div('', 'confirm-issuedon'));
        $mform->addElement('static', 'confirm-expiresby', get_string('expiresby', 'local_obf'), html_writer::div('', 'confirm-expiresby'));
        $mform->addElement('static', 'confirm-criteria', get_string('badgecriteria', 'local_obf'),
                html_writer::link($badge->get_criteria(), get_string('previewcriteria', 'local_obf')));
        $mform->addElement('static', 'confirm-email', get_string('emailmessage', 'local_obf'),
                html_writer::div(html_writer::link('#', get_string('previewemail', 'local_obf')), 'confirm-email'));
        $mform->addElement('static', 'confirm-recipients', get_string('recipients', 'local_obf'), html_writer::div('', 'confirm-recipients'));
        
        $this->add_action_buttons(false, get_string('issue', 'local_obf'));
    }    
}

class MoodleQuickForm_userselector extends HTML_QuickForm_element {

    protected $userselector;
    protected $strHtml;

    function MoodleQuickForm_userselector($elementName = null, $elementLabel = null, $options = null, $attributes = null) {
        parent::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->userselector = new badge_recipient_selector($elementName, $options);
        $this->userselector->set_multiselect(true);
    }

    function toHtml() {
        $this->strHtml = $this->userselector->display(true);
        return $this->strHtml;
    }

    function getValue() {
        return $this->userselector->get_selected_users();
    }

}

class badge_recipient_selector extends user_selector_base {

    const MAX_USERS_IN_LIST = 5000;

    private $existingrecipients = array();

    protected function get_options() {

        $options = parent::get_options();
        $options['file'] = 'local/obf/forms.php';
        return $options;
    }

    public function find_users($search) {
        /**
         * @var moodle_database
         */
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
            list($emailin, $emailparams) = $DB->get_in_or_equal($this->existingrecipients, SQL_PARAMS_NAMED, 'obf', false);
            $whereclauses[] = 'u.email ' . $emailin;
            $params = array_merge($params, $emailparams);
        }

        if (count($whereclauses) > 0) {
            $wheresql = ' WHERE ' . implode(' AND ', $whereclauses);
        }

        list($sort, $sortparams) = users_order_by_sql($tablealias, $search);

        $fields = 'SELECT ' . $this->required_fields_sql($tablealias);
        $count = 'SELECT COUNT(' . $tablealias . '.id)';
        $sql = ' FROM {user} ' . $tablealias . $wheresql;
        $orderby = ' ORDER BY ' . $sort;

        // Check how many users does the query return and return an error if the number
        // of users is too damn high.
        if (!$this->is_validating()) {
            $usercount = $DB->count_records_sql($count . $sql, $params);
            if ($usercount > self::MAX_USERS_IN_LIST) {
                return $this->too_many_results($search, $usercount);
            }
        }

        $users = $DB->get_records_sql($fields . $sql . $orderby, array_merge($params, $sortparams));

        return array(get_string('recipientcandidates', 'local_obf') => $users);
    }

}

?>
