<?php

defined('MOODLE_INTERNAL') or die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class obf_courseselection_form extends moodleform {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $courses = $this->_customdata['courses'];
        $badge = $this->_customdata['badge'];
        $buttons = array();

        if (count($courses) > 0) {
            $coursecategories = coursecat::make_categories_list();
            $courselist = array();

            // initialize categories for the select list's optgroups
            foreach ($coursecategories as $category) {
                $courselist[$category] = array();
            }

            foreach ($courses as $course) {
                $categoryname = $coursecategories[$course->category];
                $courselist[$categoryname][$course->id] = format_string($course->fullname, true);
            }

            // Hidden fields for the page parameters
            $mform->addElement('hidden', 'badgeid', $badge->get_id());
            $mform->setType('badgeid', PARAM_ALPHANUM);
            $mform->addElement('hidden', 'type', obf_criterion_base::CRITERIA_TYPE_COURSESET);
            $mform->setType('type', PARAM_ALPHA);

            // Course selection
            $mform->addElement('header', 'header_criterion_fields', get_string('selectcourses', 'local_obf'));
            $mform->setExpanded('header_criterion_fields');
            $mform->addElement('selectgroups', 'course', get_string('selectcourses', 'local_obf'), $courselist, array('multiple' => true));
            $mform->addRule('course', get_string('courserequired', 'local_obf'), 'required');

            $buttons[] = $mform->createElement('submit', 'savecriteria', get_string('addcourses', 'local_obf'));
        }

        // No courses found with completion enabled
        else {
            $mform->addElement('html', $OUTPUT->notification(get_string('nocourseswithcompletionenabled', 'local_obf')));
        }

        $buttons[] = $mform->createElement('cancel', 'cancelbutton', get_string('cancel', 'local_obf'));
        $mform->addGroup($buttons, 'buttonar', '', null, false);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

}

?>
