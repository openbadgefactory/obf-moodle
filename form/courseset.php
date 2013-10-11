<?php

defined('MOODLE_INTERNAL') or die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class obf_courseset_form extends moodleform {

    /**
     * 
     * @global moodle_database $DB
     * @global core_renderer $OUTPUT
     */
    protected function definition() {
        global $DB, $OUTPUT;

        $mform = $this->_form;
        $criterionid = $this->_customdata['id'];
        $attributes = $this->_customdata['attributes'];
        $criterion = $DB->get_record('obf_criterion', array('id' => $criterionid));

        if ($criterion === false)
            throw new Exception(get_string('invalidcriterionid', 'local_obf'));

        $mform->addElement('header', 'header_criteria_courses',
                get_string('criteriacourses', 'local_obf'));
        $mform->addElement('hidden', 'id', $criterionid);
        $mform->setType('id', PARAM_INT);

        foreach ($attributes as $courseid => $coursedata) {
            $mform->addElement('html', $OUTPUT->heading($coursedata->coursename, 3));

            // Minimum grade -field
            $mform->addElement('text', 'mingrade[' . $courseid . ']',
                    get_string('minimumgrade', 'local_obf'));
            $mform->addRule('mingrade[' . $courseid . ']', null, 'numeric');

            // Fun fact: Moodle would like the developer to call $mform->setType()
            // for every form element just in case and shows a E_NOTICE in logs
            // if it detects a missing setType-call. But if we call setType,
            // server-side validation stops working and thus makes $mform->addRule()
            // completely useless. That's why we don't call setType() here.

            if (isset($coursedata->attributes['grade']))
                $mform->setDefault('mingrade[' . $courseid . ']', $coursedata->attributes['grade']);

            // Course completion date -selector. We could try naming the element
            // using array (like above), but it's broken with date_selector.
            // Instead of returning an array like it should, $form->get_data()
            // returns something like array["completedby[60]"] which is fun.
            $mform->addElement('date_selector', 'completedby_' . $courseid . '',
                    get_string('coursecompletedby', 'local_obf'),
                    array('optional' => true, 'startyear' => date('Y')));

            if (isset($coursedata->attributes['completedby']))
                $mform->setDefault('completedby_' . $courseid,
                        $coursedata->attributes['completedby']);
        }

        // Radiobuttons to select whether this criterion is completed
        // when any of the courses are completed or all of them
        $radiobuttons = array();
        $radiobuttons[] = $mform->createElement('radio', 'completion_method', '',
                get_string('criteriacompletionmethodall', 'local_obf'),
                obf_criterion_base::CRITERIA_COMPLETION_ALL);
        $radiobuttons[] = $mform->createElement('radio', 'completion_method', '',
                get_string('criteriacompletionmethodany', 'local_obf'),
                obf_criterion_base::CRITERIA_COMPLETION_ANY);

        $mform->addElement('header', 'header_completion_method',
                get_string('criteriacompletedwhen', 'local_obf'));
        $mform->setExpanded('header_completion_method');
        $mform->addGroup($radiobuttons, 'radioar', '', '<br />', false);
        $mform->setDefault('completion_method', $criterion->completion_method);

        $this->add_action_buttons();
    }

    /**
     * 
     * @global moodle_database $DB
     * @param array $courseattributes
     *//*
      protected function get_related_courses(array $courseattributes) {
      global $DB;

      $courseids = array();

      foreach ($courseattributes as $attribute) {
      list($type, $courseid) = explode('_', $attribute->name);
      $courseids[] = $courseid;
      }

      $courses = $DB->get_records_list('course', 'id', $courseids, null,
      'id,fullname');

      return $courses;
      } */
}

?>
