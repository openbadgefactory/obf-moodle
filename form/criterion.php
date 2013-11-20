<?php

defined('MOODLE_INTERNAL') or die();

global $CFG;

if (file_exists($CFG->libdir . '/coursecatlib.php')) {
    require_once($CFG->libdir . '/coursecatlib.php');
}
// Moodle 2.2
else {
    require_once($CFG->dirroot . '/course/lib.php');
}

require_once(__DIR__ . '/obfform.php');
require_once($CFG->libdir . '/completionlib.php');

class obf_criterion_form extends obfform implements renderable {

    /**
     * @var obf_criterion
     */
    protected $criterion = null;

    protected function definition() {
        global $DB, $OUTPUT;

        $mform = $this->_form;
        $this->criterion = $this->_customdata['criterion'];

        // creating a new criterion
        if (!$this->criterion->exists()) {

            // Get only courses with course completion enabled (= can be completed somehow)
            $courses = $DB->get_records('course', array('enablecompletion' => COMPLETION_ENABLED));

            if (count($courses) > 0) {
                $categories = array();

                if (method_exists('coursecat', 'make_categories_list')) {
                    $categories = coursecat::make_categories_list();
                }
                // Moodle 2.2
                else {
                    $parents = array();
                    make_categories_list($categories, $parents);
                }

                $courselist = $this->initialize_categories($categories);

                foreach ($courses as $course) {
                    if (!$this->criterion->get_badge()->has_completion_criteria_with_course($course)) {
                        $categoryname = $categories[$course->category];
                        $courselist[$categoryname][$course->id] = format_string($course->fullname,
                                true);
                    }
                }

                $validcourses = 0;

                // Check each course category, are there any courses
                foreach ($courselist as $name => $courses) {
                    $validcourses += count($courses);
                }

                $mform->addElement('header', 'header_criterion_fields',
                        get_string('selectcourses', 'local_obf'));
                $this->setExpanded($mform, 'header_criterion_fields');

                // There aren't any courses that aren't already in this badge's criteria
                if ($validcourses === 0) {
                    $mform->addElement('html',
                            $OUTPUT->notification(get_string('novalidcourses', 'local_obf')));
                }

                // There are courses that can be selected -> show course selection
                else {
                    $mform->addElement('html',
                            html_writer::tag('p', get_string('selectcourses_help', 'local_obf')));
                    $mform->addElement('selectgroups', 'course',
                            get_string('selectcourses', 'local_obf'), $courselist,
                            array('multiple' => true));
                    $mform->addRule('course', get_string('courserequired', 'local_obf'), 'required');

                    $buttons[] = $mform->createElement('submit', 'savecriteria',
                            get_string('addcourses', 'local_obf'));
                }
            }

            // No courses found with completion enabled
            else {
                $mform->addElement('html',
                        $OUTPUT->notification(get_string('nocourseswithcompletionenabled',
                                        'local_obf')));
            }

            $buttons[] = $mform->createElement('cancel', 'cancelbutton',
                    get_string('back', 'local_obf'));
            $mform->addGroup($buttons, 'buttonar', '', null, false);
        }

        // editing an existing criterion
        else {
            $criterioncourses = $this->criterion->get_items();
            $courses = $this->criterion->get_related_courses();

            $mform->addElement('header', 'header_criteria_courses',
                    get_string('criteriacourses', 'local_obf'));

            foreach ($criterioncourses as $course) {
                $coursename = $courses[$course->get_courseid()]->fullname;
                $mform->addElement('html', $OUTPUT->heading($coursename, 3));
                self::add_coursefields($mform, $course);
            }

            // Radiobuttons to select whether this criterion is completed
            // when any of the courses are completed or all of them
            if (count($criterioncourses) > 1) {
                $radiobuttons = array();
                $radiobuttons[] = $mform->createElement('radio', 'completion_method', '',
                        get_string('criteriacompletionmethodall', 'local_obf'),
                        obf_criterion::CRITERIA_COMPLETION_ALL);
                $radiobuttons[] = $mform->createElement('radio', 'completion_method', '',
                        get_string('criteriacompletionmethodany', 'local_obf'),
                        obf_criterion::CRITERIA_COMPLETION_ANY);

                $mform->addElement('header', 'header_completion_method',
                        get_string('criteriacompletedwhen', 'local_obf'));
                $this->setExpanded($mform, 'header_completion_method');
                $mform->addGroup($radiobuttons, 'radioar', '', '<br />', false);
                $mform->setDefault('completion_method', $this->criterion->get_completion_method());
            }

            $mform->addElement('header', 'header_review_criterion_after_save',
                    get_string('reviewcriterionaftersave', 'local_obf'));
            $this->setExpanded($mform, 'header_review_criterion_after_save');
            $mform->addElement('html',
                    $OUTPUT->notification(get_string('warningcannoteditafterreview', 'local_obf')));
            $mform->addElement('advcheckbox', 'reviewaftersave', get_string('reviewcriterionaftersave', 'local_obf'));
            $mform->addHelpButton('reviewaftersave', 'reviewcriterionaftersave', 'local_obf');

            $this->add_action_buttons();
        }
    }

    public static function add_coursefields(&$mform, obf_criterion_course $course) {
        $criterioncourseid = $course->get_id();
        $grade = $course->get_grade();
        $completedby = $course->get_completedby();

        // Minimum grade -field
        $mform->addElement('text', 'mingrade[' . $criterioncourseid . ']',
                get_string('minimumgrade', 'local_obf'));

        // Fun fact: Moodle would like the developer to call $mform->setType()
        // for every form element just in case and shows a E_NOTICE in logs
        // if it detects a missing setType-call. But if we call setType,
        // server-side validation stops working and thus makes $mform->addRule()
        // completely useless. That's why we don't call setType() here.
        //
        // ... EXCEPT that Behat-tests are failing because of the E_NOTICE, so let's add client
        // side validation + server side cleaning
        $mform->addRule('mingrade[' . $criterioncourseid . ']', null, 'numeric', null, 'client');
        $mform->setType('mingrade[' . $criterioncourseid . ']', PARAM_INT);

        if ($course->has_grade()) {
            $mform->setDefault('mingrade[' . $criterioncourseid . ']', $grade);
        }

        // Course completion date -selector. We could try naming the element
        // using array (like above), but it's broken with date_selector.
        // Instead of returning an array like it should, $form->get_data()
        // returns something like array["completedby[60]"] which is fun.
        $mform->addElement('date_selector', 'completedby_' . $criterioncourseid . '',
                get_string('coursecompletedby', 'local_obf'),
                array('optional' => true, 'startyear' => date('Y')));

        if ($course->has_completion_date()) {
            $mform->setDefault('completedby_' . $criterioncourseid, $completedby);
        }
    }

    public function get_criterion() {
        return $this->criterion;
    }

    public function get_form() {
        return $this->_form;
    }

    private function initialize_categories(array $categories) {
        $courselist = array();

        // initialize categories for the select list's optgroups
        foreach ($categories as $category) {
            $courselist[$category] = array();
        }

        return $courselist;
    }

}
