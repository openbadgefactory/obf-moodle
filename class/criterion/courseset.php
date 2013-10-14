<?php

defined('MOODLE_INTERNAL') or die();

global $CFG;

require_once __DIR__ . '/criterionbase.php';
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Description of coursecompletion
 *
 * @author olli
 */
class obf_criterion_courseset extends obf_criterion_base {

    /**
     * 
     * @param type $course
     * @return string
     */
    public function get_attribute_text($course) {
        $html = html_writer::tag('strong', $course->coursename);

        if (isset($course->attributes['completedby'])) {
            $html .= ' ' . get_string('completedbycriterion', 'local_obf',
                            userdate($course->attributes['completedby'], get_string('strftimedate')));
        }

        if (isset($course->attributes['grade'])) {
            $html .= ' ' . get_string('gradecriterion', 'local_obf', $course->attributes['grade']);
        }

        return $html;
    }

    /**
     * 
     * @global moodle_database $DB
     * @param array $attributes
     */
    public function get_parsed_attributes() {
        global $DB;

        $courseids = array();
        $ret = array();

        foreach ($this->get_attributes() as $attribute) {
            list($type, $courseid) = explode('_', $attribute->name);

            if (!in_array($courseid, $courseids)) {
                $courseids[] = $courseid;
            }

            if (!isset($ret[$courseid])) {
                $ret[$courseid] = new stdClass();
                $ret[$courseid]->attributes = array();
                $ret[$courseid]->coursename = '';
            }

            $ret[$courseid]->attributes[$type] = $attribute->value;
        }

        $courses = $DB->get_records_list('course', 'id', $courseids);

        foreach ($courses as $course) {
            $ret[$course->id]->coursename = $course->fullname;
        }

        return $ret;
    }

    public function customizeform(obf_criterion_form &$form) {
        global $DB, $OUTPUT;

        $mform = $form->get_form();

        // creating a new criterion
        if ($this->id <= 0) {
            $courses = $DB->get_records('course', array('enablecompletion' => COMPLETION_ENABLED));

            if (count($courses) > 0) {
                $categories = coursecat::make_categories_list();
                $courselist = array();

                // initialize categories for the select list's optgroups
                foreach ($categories as $category) {
                    $courselist[$category] = array();
                }

                foreach ($courses as $course) {
                    $categoryname = $categories[$course->category];
                    $courselist[$categoryname][$course->id] = format_string($course->fullname, true);
                }

                // Course selection
                $mform->addElement('header', 'header_criterion_fields',
                        get_string('selectcourses', 'local_obf'));
                $mform->setExpanded('header_criterion_fields');
                $mform->addElement('selectgroups', 'course',
                        get_string('selectcourses', 'local_obf'), $courselist,
                        array('multiple' => true));
                $mform->addRule('course', get_string('courserequired', 'local_obf'), 'required');

                $buttons[] = $mform->createElement('submit', 'savecriteria',
                        get_string('addcourses', 'local_obf'));
            }

            // No courses found with completion enabled
            else {
                $mform->addElement('html',
                        $OUTPUT->notification(get_string('nocourseswithcompletionenabled',
                                        'local_obf')));
            }

            $buttons[] = $mform->createElement('cancel', 'cancelbutton',
                    get_string('cancel', 'local_obf'));
            $mform->addGroup($buttons, 'buttonar', '', null, false);
        }

        // editing an existing criterion
        else {
            $attributes = $this->get_parsed_attributes();

            $mform->addElement('header', 'header_criteria_courses',
                    get_string('criteriacourses', 'local_obf'));

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
                    $mform->setDefault('mingrade[' . $courseid . ']',
                            $coursedata->attributes['grade']);

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
            $mform->setDefault('completion_method', $this->completion_method);

            $form->add_action_buttons();
        }
    }

    public function review($data) {
        $userid = $data->userid;
        $courseid = $data->course;
        $criterioncourses = $this->get_parsed_attributes();
        $requireall = $this->get_completion_method() == obf_criterion_base::CRITERIA_COMPLETION_ALL;

        // The completed course doesn't exist in this criterion, no need to continue
        if (!array_key_exists($courseid, $criterioncourses)) {
            return false;
        }

        $criterioncompleted = false;
        
        foreach ($criterioncourses as $id => $coursecriterion) {
            $coursecompleted = $this->review_course($id, $coursecriterion, $userid);

            // All of the courses have to be completed
            if ($requireall) {
                if (!$coursecompleted) {
                    return false;
                }
                else {
                    $criterioncompleted = true;
                }
            }

            // Any of the courses has to be completed
            else {
                if ($coursecompleted) {
                    return true;
                } else {
                    $criterioncompleted = false;
                }
            }
        }
        
        return $criterioncompleted;
    }

    protected function review_course($courseid, $criterion, $userid) {
        global $DB;
        
        $course = $DB->get_record('course', array('id' => $courseid));
        $completioninfo = new completion_info($course);

        if ($completioninfo->is_course_complete($userid)) {
            return false;
        }

        $datepassed = false;
        $gradepassed = false;
        $completion = new completion_completion(array('userid' => $userid, 'course' => $courseid));
        $completedat = $completion->timecompleted;
        
        // check completion date
        if (isset($criterion['completedby'])) {
            if ($completedat <= $criterion['completedby']) {
                $datepassed = true;
            }
        } else {
            $datepassed = true;
        }

        // check grade
        if (isset($criterion['grade'])) {
            $grade = grade_get_course_grade($userid, $courseid);

            if ($grade >= $criterion['grade']) {
                $gradepassed = true;
            }
        } else {
            $gradepassed = true;
        }

        return $datepassed && $gradepassed;
    }

//    public function get_yui_modules() {
//        return array(
//            'moodle-local_obf-coursecompletion' => array(
//                'init' => 'M.local_obf.init_coursecompletion',
//                'strings' => array()
//            )            
//        );
//    }
}
?>
