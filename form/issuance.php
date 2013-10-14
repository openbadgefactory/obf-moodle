<?php
defined('MOODLE_INTERNAL') or die();

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once 'HTML/QuickForm/element.php';

class obf_issuance_form extends moodleform {

    /**
     * @var obf_badge
     */
    private $badge = null;

    /**
     *
     * @var local_obf_renderer
     */
    private $renderer = null;

    /**
     *
     * @var obf_issuance
     */
    private $issuance = null;

    protected function definition() {
        $this->badge = $this->_customdata['badge'];
        $this->renderer = $this->_customdata['renderer'];

        $tabs = $this->_customdata['tabs'];
        $navitems = array();

        // HACK: Moodle (or HTML_QuickForm really) secretly sometimes creates empty
        // fieldsets to ensure XHTML-validity. Moodle uses different HTML-templates
        // and those templates are broken (missing closing div). HTML_QuickForm
        // ->defaultRenderer() returns an instance of HTML_QuickForm_Renderer_Default,
        // but here we're using methods of its subclass MoodleQuickForm_Renderer that
        // Moodle is using. If the renderer somehow changes, this won't work.
        $renderer = $this->_form->defaultRenderer();
        if (method_exists($renderer, 'setOpenHiddenFieldsetTemplate')) {
            $renderer->setOpenHiddenFieldsetTemplate("\n\t<fieldset class=\"hidden\">");
            $renderer->setCloseFieldsetTemplate("\n\t\t</fieldset>");
        }

        foreach ($tabs as $key => $item) {
            $navitems[] = html_writer::tag('li', html_writer::link('#' . $key, $item), array('id' => 'tab-' . $key));
        }

        $this->startdiv('obf-issuerwizard');
        $navigation = html_writer::tag('ul', implode('', $navitems), array('class' => 'nav nav-tabs'));
        $this->_form->addElement('html', $navigation);
        $this->startdiv();

        foreach ($tabs as $key => $item) {
            $method = 'add_' . $key . '_elements';
            if (method_exists($this, $method)) {
                $this->start_tabpanel($key);
                call_user_func(array($this, $method));
                $this->end_tabpanel();
            }
        }

        $this->enddiv();
        $this->enddiv();
    }

    public function validation($data, $files) {
        global $CFG;

        require_once $CFG->dirroot . '/user/lib.php';

        $errors = parent::validation($data, $files);

        $emailsubject = $data['emailsubject'];
        $emailbody = $data['emailbody'];
        $emailfooter = $data['emailfooter'];
        $issuedon = $data['issuedon'];
        $expiresby = $data['expiresby'];
        $recipient_ids = $data['recipientlist'];

        $users = user_get_users_by_id($recipient_ids);
        $recipients = array();

        foreach ($users as $user) {
            $recipients[] = $user->email;
        }

        $this->badge->set_expires($expiresby);
        $this->issuance = obf_issuance::get_instance()
                ->set_badge($this->badge)
                ->set_emailbody($emailbody)
                ->set_emailsubject($emailsubject)
                ->set_emailfooter($emailfooter)
                ->set_issuedon($issuedon)
                ->set_recipients($recipients);

        return $errors;
    }

    private function add_preview_elements() {
        $this->_form->addElement('html', $this->renderer->print_badge_info_details($this->badge));
    }

    private function add_details_elements() {
        $mform = $this->_form;
        $mform->addElement('html', $this->renderer->print_badge_teaser($this->badge));
        $mform->addElement('html', $this->renderer->print_heading('issueandexpiration'));
        $mform->addElement('date_selector', 'issuedon', get_string('issuedon', 'local_obf'), array('stopyear' => date('Y') + 1));
        $mform->addElement('date_selector', 'expiresby', get_string('expiresby', 'local_obf'), array('optional' => true, 'startyear' => date('Y'), 'stopyear' => date('Y') + 20));

        if ($this->badge->has_expiration_date()) {
            $mform->setDefault('expiresby', $this->badge->get_default_expiration_date());
        }
    }

    private function add_recipients_elements() {
        $mform = $this->_form;
        $excludes = $this->get_user_ids_with_badge_issued();
        $mform->addElement('html', $this->renderer->print_badge_teaser($this->badge));
        $mform->addElement('html', $this->renderer->print_heading('recipients'));
        $mform->registerElementType('obf_user_selector', __FILE__, 'MoodleQuickForm_userselector');
        $mform->addElement('obf_user_selector', 'recipientlist', get_string('selectrecipients', 'local_obf'), array(), array('exclude' => $excludes));
    }

    private function add_message_elements() {
        require_once(__DIR__ . '/emailtemplate.php');
        
        $mform = $this->_form;
        $mform->addElement('html', $this->renderer->print_badge_teaser($this->badge));
        $mform->addElement('html', $this->renderer->print_heading('editemailmessage'));
        
        obf_email_template_form::add_email_fields($mform, $this->badge->get_email());
    }

    private function add_confirm_elements() {
        $mform = $this->_form;        
        $mform->addElement('html', $this->renderer->print_badge_teaser($this->badge));
        $mform->addElement('html', $this->renderer->print_heading('confirmandissue'));
        $mform->addElement('static', 'confirm-issuedon', get_string('issuedon', 'local_obf'), html_writer::div('', 'confirm-issuedon'));
        $mform->addElement('static', 'confirm-expiresby', get_string('expiresby', 'local_obf'), html_writer::div('', 'confirm-expiresby'));
        $mform->addElement('static', 'confirm-criteria', get_string('badgecriteria', 'local_obf'), html_writer::link($this->badge->get_criteria(), get_string('previewcriteria', 'local_obf'), array('target' => '_blank')));
        $mform->addElement('static', 'confirm-email', get_string('emailmessage', 'local_obf'), html_writer::div(html_writer::link('#', get_string('previewemail', 'local_obf')), 'confirm-email'));
        $mform->addElement('static', 'confirm-recipients', get_string('recipients', 'local_obf'), html_writer::div('', 'confirm-recipients'));

        $this->add_action_buttons(false, get_string('issue', 'local_obf'));
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

    private function start_tabpanel($id, $class = "yui3-tab-panel") {
        $this->startdiv($id, $class);
    }

    private function end_tabpanel() {
        $this->enddiv();
    }

    private function startdiv($name = '', $class = '') {
        if (!empty($name))
            $this->_form->addElement('html', html_writer::start_div($class, array('id' => $name)));
        else
            $this->_form->addElement('html', html_writer::start_div());
    }

    private function enddiv() {
        $this->_form->addElement('html', html_writer::end_div());
    }

    public function get_issuance() {
        return $this->issuance;
    }

}

class MoodleQuickForm_userselector extends HTML_QuickForm_element {

    protected $userselector;
    protected $strHtml;
    protected $name = '';

    public function MoodleQuickForm_userselector($elementName = null, $elementLabel = null, $options = null, $attributes = null) {
        parent::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->setName($elementName);
        $this->userselector = new badge_recipient_selector($elementName, $options);
        $this->userselector->set_multiselect(true);
        $this->userselector->exclude($attributes['exclude']);
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

    protected function get_options() {

        $options = parent::get_options();
        $options['file'] = 'local/obf/forms.php';
        return $options;
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
