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
        $addcourse = $this->_customdata['addcourse'];

        if (!empty($addcourse)) {
            $this->get_courses($mform);
        }

        // editing an existing criterion
        else {
            $criterioncourses = $this->criterion->get_items();
            $courses = $this->criterion->get_related_courses();
            $showaddcourse = true;
            $showoptions = true;
            $showbuttons = true;
            // Show options
            if (count($criterioncourses) == 0) {
                $course = obf_criterion_item::build_type(obf_criterion_item::CRITERIA_TYPE_UNKNOWN);
                $criterioncourses[] = $course;
            }

            $mform->addElement('header', 'header_criteria_courses',
                    get_string('criteriacourses', 'local_obf'));

            if (count($criterioncourses) == 1 && empty($addcourse) &&
                    $criterioncourses[0]->get_courseid() == -1 &&
                    $criterioncourses[0]->requires_field('courseid') &&
                    $criterioncourses[0]->get_criteriatype() != obf_criterion_item::CRITERIA_TYPE_UNKNOWN) {
                $this->get_courses($mform);
                $showoptions = false;
                $showaddcourse = false;
                $showbuttons = false;
            }
            if ($showoptions) {
                foreach ($criterioncourses as $course) {
                    if ($course->get_courseid() != -1) {
                        $coursename = $courses[$course->get_courseid()]->fullname;
                        $mform->addElement('html', $OUTPUT->heading($coursename, 3));
                    }
                    $course->get_options($mform);
                    if (!$course->criteria_supports_multiple_courses()) {
                        $showaddcourse = false;
                    }
                }
            }
            if (count($criterioncourses) > 0) {
                $criterioncourses[0]->get_form_config($mform);
                if ($showaddcourse) {
                    $mform->addElement('submit','addcourse',get_string('criteriaaddcourse','local_obf'), array('class' => 'addcourse'));
                }
                $criterioncourses[0]->get_form_completion_options($mform, $this, $criterioncourses);
                $criterioncourses[0]->get_form_after_save_options($mform, $this);
            }


            if ($showbuttons) {
                $this->add_action_buttons();
            }
        }
    }
    private function get_courses($mform) {
        global $DB, $OUTPUT;
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
            $existingcourselist = $this->criterion->exists() ? $this->criterion->get_items() : array();
            $existingcourseids = array_map(function($c) {
                return $c->get_courseid();
            }, $existingcourselist);

            foreach ($courses as $course) {
                $hascourse = $this->criterion->exists() ? $this->criterion->has_course($course->id) : false;
                if ($hascourse || !$this->criterion->get_badge()->has_completion_criteria_with_course($course)) {
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
            $mform->addHelpButton('header_criterion_fields', 'readmeenablecompletion', 'local_obf');

            // There aren't any courses that aren't already in this badge's criteria
            if ($validcourses === 0) {
                $mform->addElement('html',
                        $OUTPUT->notification(get_string('novalidcourses', 'local_obf')));
            }

            // There are courses that can be selected -> show course selection
            else {
                $mform->addElement('html',
                        html_writer::tag('p', get_string('selectcourses_help', 'local_obf')));
                $select = $mform->addElement('selectgroups', 'course',
                        get_string('selectcourses', 'local_obf'), $courselist,
                        array('multiple' => true));
                $select->setSelected($existingcourseids);
                $mform->addRule('course', get_string('courserequired', 'local_obf'), 'required');

                $buttons[] = $mform->createElement('submit', 'savecriteria',
                        get_string('addcourses', 'local_obf'));
                $mform->addElement('hidden', 'addcourse', 'addcourse');
                $mform->setType('addcourse', PARAM_TEXT);
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
    public static function add_coursefields(&$mform, obf_criterion_item $course) {
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
