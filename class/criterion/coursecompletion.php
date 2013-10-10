<?php

require_once __DIR__ . '/criterion.php';

/**
 * Description of coursecompletion
 *
 * @author olli
 */
class obf_criteria_coursecompletion extends obf_criterion {

    /**
     * 
     * @global moodle_database $DB
     * @global type $PAGE
     * @param obf_badge $badge
     * @return type
     * @throws Exception
     */
    public function render(obf_badge $badge) {
        $html = '';
        $action = optional_param('action', 'add', PARAM_ALPHA);

        switch ($action) {
            case 'add':
                $html .= $this->handle_courseselection($badge);
                break;

            case 'edit':
                $criterionid = required_param('criterionid', PARAM_INT);
                $html .= $this->handle_courseediting($badge, $criterionid);
                break;
        }
        return $html;
    }

    /**
     * 
     * @global moodle_database $DB
     * @param obf_badge $badge
     * @return type
     * @throws Exception
     */
    protected function handle_courseselection(obf_badge $badge) {
        global $DB;

        $html = '';
        $courses = $DB->get_records('course', array('enablecompletion' => COMPLETION_ENABLED));
        $form = new obf_criteria_courseselection_form(null, array('courses' => $courses, 'badge' => $badge), 'post', '', array('id' => 'coursecompletionform'));

        // Form submission was cancelled
        if ($form->is_cancelled()) {
            redirect(new moodle_url('/local/obf/badgedetails.php', array('id' => $badge->get_id(), 'show' => 'criteria')));
        }
        // Form was successfully submitted
        else if (!is_null($data = $form->get_data())) {
            $criteriontypeid = $this->get_criterion_type_id();

            if ($criteriontypeid === false)
                throw new Exception(get_string('missingcriteriontype', 'local_obf'));

            $criterion = new stdClass();
            $criterion->badge_id = $badge->get_id();
            $criterion->obf_criterion_type_id = $criteriontypeid;
            $criterion->completion_method = obf_criterion::CRITERIA_COMPLETION_ALL;
            $criterionid = $DB->insert_record('obf_criterion', $criterion, true);

            if ($criterionid === false)
                throw new Exception(get_string('creatingcriteriafailed', 'local_obf'));

            $courseids = $data->course;

            foreach ($courseids as $courseid) {
                $course = $DB->get_record('course', array('id' => $courseid,
                    'enablecompletion' => COMPLETION_ENABLED));

                if ($course !== false) {
                    $this->add_criterion_attribute($criterionid, 'course_' . $courseid, $courseid);
                }
            }

            redirect(new moodle_url('/local/obf/criterion.php',
                    array('badgeid' => $badge->get_id(), 'action' => 'edit', 'criterionid' => $criterionid)));
        }
        // Display the form normally
        else {
            $html .= $form->render();
        }

        return $html;
    }

    public function get_attributes($criterionid) {
        global $DB;
        
        $attributes = $DB->get_records('obf_criterion_attributes', array('obf_criterion_id' => $criterionid));
        return $this->parse_attributes($attributes);
    }
    
    /**
     * 
     * @param obf_badge $badge
     * @param type $criterionid
     * @global moodle_database $DB
     * @return type
     */
    public function handle_courseediting(obf_badge $badge, $criterionid) {
        global $DB;

        $html = '';
        $url = new moodle_url('/local/obf/criterion.php', array('badgeid' =>
            $badge->get_id(), 'action' => 'edit', 'type' => 'coursecompletion'));
        $attributes = $this->get_attributes($criterionid);
        $form = new obf_criteria_edit_form($url, array('criterionid' => $criterionid,
            'attributes' => $attributes));
        
        // Form was cancelled
        if ($form->is_cancelled()) {
            die('CANCEL');
        }
        // Form was successfully submitted, save data
        else if (!is_null($data = $form->get_data())) {
            // TODO: wrap into a transaction
            $completionmethod = $data->completion_method;
            $criterionid = (int) $data->criterionid;
            $group = $DB->get_record('obf_criterion', array('id' => $criterionid));

            if ($group === false)
                throw new Exception(get_string('invalidcriterionid', 'local_obf'));

            // update the criterion group first...
            $group->completion_method = $completionmethod == obf_criterion::CRITERIA_COMPLETION_ALL ? obf_criterion::CRITERIA_COMPLETION_ALL : obf_criterion::CRITERIA_COMPLETION_ANY;
            $DB->update_record('obf_criterion', $group);

            // ... delete old attributes ...
            $DB->delete_records('obf_criterion_attributes', array('obf_criterion_id' => $group->id));

            // ... and then add the criterion attributes
            foreach ($data->mingrade as $courseid => $grade) {
                $grade = (int) $grade;
                $completedby = $data->{'completedby_' . $courseid};

                // first add the course...
                $attribute = new stdClass();
                $attribute->obf_criterion_id = $group->id;
                $attribute->name = 'course_' . $courseid;
                $attribute->value = $courseid;

                $DB->insert_record('obf_criterion_attributes', $attribute, false, true);

                // ... then the grade-attribute if selected...
                if ($grade > 0) {
                    $this->add_criterion_attribute($group->id, 'grade_' . $courseid, $grade);
                }

                // ... and finally completion date -attribute if selected
                if ($completedby > 0) {
                    $this->add_criterion_attribute($group->id, 'completedby_' . $courseid, $completedby);
                }
            }

            redirect(new moodle_url('/local/obf/badgedetails.php', array('id' => $badge->get_id(), 'show' => 'criteria')));
        } else {
            $html .= $form->render();
        }

        return $html;
    }

    /**
     * 
     * @global moodle_database $DB
     */
    protected function get_criterion_type_id() {
        global $DB;
        $obj = $DB->get_record_select('obf_criterion_types', $DB->sql_compare_text('name') .
                ' = ?', array('coursecompletion'));

        return ($obj === false ? false : $obj->id);
    }

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
    public function parse_attributes(array $attributes) {
        global $DB;
        
        $courseids = array();
        $ret = array();
        
        foreach ($attributes as $attribute) {
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

//    public function get_yui_modules() {
//        return array(
//            'moodle-local_obf-coursecompletion' => array(
//                'init' => 'M.local_obf.init_coursecompletion',
//                'strings' => array()
//            )            
//        );
//    }
}

class obf_criteria_edit_form extends moodleform {

    /**
     * 
     * @global moodle_database $DB
     * @global core_renderer $OUTPUT
     */
    protected function definition() {
        global $DB, $OUTPUT;

        $mform = $this->_form;
        $criterionid = $this->_customdata['criterionid'];
        $attributes = $this->_customdata['attributes'];
        $criterion = $DB->get_record('obf_criterion', array('id' => $criterionid));

        if ($criterion === false)
            throw new Exception(get_string('invalidcriterionid'));

        $mform->addElement('header', 'header_criteria_courses', get_string('criteriacourses', 'local_obf'));
        $mform->addElement('hidden', 'criterionid', $criterionid);


        foreach ($attributes as $courseid => $coursedata) {
            $mform->addElement('html', $OUTPUT->heading($coursedata->coursename, 3));

            // Minimum grade -field
            $mform->addElement('text', 'mingrade[' . $courseid . ']', get_string('minimumgrade', 'local_obf'));
            $mform->addRule('mingrade[' . $courseid . ']', null, 'numeric');
            
            if (isset($coursedata->attributes['grade']))
                $mform->setDefault('mingrade[' . $courseid . ']' , $coursedata->attributes['grade']);

            // Course completion date -selector. We could try naming the element
            // using array (like above), but it's broken with date_selector.
            // Instead of returning an array like it should, $form->get_data()
            // returns something like array["completedby[60]"] which is fun.
            $mform->addElement('date_selector', 'completedby_' . $courseid . '', get_string('coursecompletedby', 'local_obf'), array('optional' => true, 'startyear' => date('Y')));
            
            if (isset($coursedata->attributes['completedby']))
                $mform->setDefault('completedby_' . $courseid, $coursedata->attributes['completedby']);
        }

        // Radiobuttons to select whether this criterion is completed
        // when any of the courses are completed or all of them
        $radiobuttons = array();
        $radiobuttons[] = $mform->createElement('radio', 'completion_method', '', get_string('criteriacompletionmethodall', 'local_obf'), obf_criterion::CRITERIA_COMPLETION_ALL);
        $radiobuttons[] = $mform->createElement('radio', 'completion_method', '', get_string('criteriacompletionmethodany', 'local_obf'), obf_criterion::CRITERIA_COMPLETION_ANY);

        $mform->addElement('header', 'header_completion_method', get_string('criteriacompletedwhen', 'local_obf'));
        $mform->setExpanded('header_completion_method');
        $mform->addGroup($radiobuttons, 'radioar', '', '<br />', false);
        $mform->setDefault('completion_method', obf_criterion::CRITERIA_COMPLETION_ALL);

        $this->add_action_buttons();
    }

    /**
     * 
     * @global moodle_database $DB
     * @param array $courseattributes
     */
    protected function get_related_courses(array $courseattributes) {
        global $DB;

        $courseids = array();

        foreach ($courseattributes as $attribute) {
            list($type, $courseid) = explode('_', $attribute->name);
            $courseids[] = $courseid;
        }

        $courses = $DB->get_records_list('course', 'id', $courseids, null, 'id,fullname');

        return $courses;
    }

}

class obf_criteria_courseselection_form extends moodleform {

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
            $mform->setType('id', PARAM_ALPHANUM);
            $mform->addElement('hidden', 'type', 'coursecompletion');
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
