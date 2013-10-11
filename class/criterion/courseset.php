<?php

require_once __DIR__ . '/criterionbase.php';
require_once __DIR__ . '/../../form/courseselection.php';
require_once __DIR__ . '/../../form/courseset.php';

/**
 * Description of coursecompletion
 *
 * @author olli
 */
class obf_criterion_courseset extends obf_criterion_base implements renderable {

    /**
     * 
     * @global moodle_database $DB
     * @global type $PAGE
     * @param obf_badge $badge
     * @return type
     * @throws Exception
     */
    public function render() {
        $html = '';

        if ($this->id > 0)
            $html .= $this->handle_courseediting();
        else
            $html .= $this->handle_courseselection();

        return $html;
    }

    /**
     * 
     * @global moodle_database $DB
     * @param obf_badge $badge
     * @return type
     * @throws Exception
     */
    protected function handle_courseselection() {
        global $DB;

        $html = '';
        $courses = $DB->get_records('course', array('enablecompletion' => COMPLETION_ENABLED));
        $form = new obf_courseselection_form(null, array('courses' => $courses,
            'badge' => $this->badge), 'post', '', array('id' => 'coursecompletionform'));

        // Form submission was cancelled
        if ($form->is_cancelled()) {
            redirect(new moodle_url('/local/obf/badgedetails.php', array('id' => $this->badge->get_id(), 'show' => 'criteria')));
        }
        // Form was successfully submitted
        else if (!is_null($data = $form->get_data())) {
            $this->set_completion_method(obf_criterion_base::CRITERIA_COMPLETION_ALL);

            if ($this->save() === false) {
                throw new Exception(get_string('creatingcriterionfailed', 'local_obf'));
            }

            $courseids = $data->course;

            foreach ($courseids as $courseid) {
                $course = $DB->get_record('course', array('id' => $courseid,
                    'enablecompletion' => COMPLETION_ENABLED));

                if ($course !== false) {
                    $this->save_attribute('course_' . $courseid, $courseid);
                }
            }

            redirect(new moodle_url('/local/obf/criterion.php', array('badgeid' => $this->badge->get_id(), 'action' => 'edit',
                'id' => $this->id)));
        }
        // Display the form normally
        else {
            $html .= $form->render();
        }

        return $html;
    }

    /**
     * 
     * @param obf_badge $badge
     * @param type $criterionid
     * @global moodle_database $DB
     * @return type
     */
    public function handle_courseediting() {
        global $DB;

        $html = '';
        $criterionid = $this->id;
        $url = new moodle_url('/local/obf/criterion.php', array('badgeid' =>
            $this->badge->get_id(), 'action' => 'edit', 'type' => self::CRITERIA_TYPE_COURSESET));
        $attributes = $this->get_parsed_attributes();
        
        $form = new obf_courseset_form($url, array('id' => $criterionid,
            'attributes' => $attributes));

        // Form was cancelled
        if ($form->is_cancelled()) {
            die('CANCEL');
        }
        // Form was successfully submitted, save data
        else if (!is_null($data = $form->get_data())) {
            // TODO: wrap into a transaction
            if ($data->completion_method != $this->get_completion_method()) {
                $this->set_completion_method($data->completion_method);
                $this->update();
            }

            // ... delete old attributes ...
            $DB->delete_records('obf_criterion_attributes', array('obf_criterion_id' => $this->id));

            // ... and then add the criterion attributes
            foreach ($data->mingrade as $courseid => $grade) {
                $grade = (int) $grade;
                $completedby = $data->{'completedby_' . $courseid};

                // first add the course...
                $attribute = new stdClass();
                $attribute->obf_criterion_id = $this->id;
                $attribute->name = 'course_' . $courseid;
                $attribute->value = $courseid;

                $DB->insert_record('obf_criterion_attributes', $attribute, false, true);

                // ... then the grade-attribute if selected...
                if ($grade > 0) {
                    $this->save_attribute('grade_' . $courseid, $grade);
                }

                // ... and finally completion date -attribute if selected
                if ($completedby > 0) {
                    $this->save_attribute('completedby_' . $courseid, $completedby);
                }
            }

            redirect(new moodle_url('/local/obf/badge.php', array('id' => $this->badge->get_id(), 'action' => 'show',
                'show' => 'criteria')));
        } else {
            $html .= $form->render();
        }

        return $html;
    }

    /**
     * 
     * @param type $course
     * @return string
     */
    public function get_attribute_text($course) {
        $html = html_writer::tag('strong', $course->coursename);

        if (isset($course->attributes['completedby'])) {
            $html .= ' ' . get_string('completedbycriterion', 'local_obf', userdate($course->attributes['completedby'], get_string('strftimedate')));
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
