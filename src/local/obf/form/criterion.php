<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Criterion form.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

global $CFG;

if (file_exists($CFG->libdir . '/coursecatlib.php')) {
    require_once($CFG->libdir . '/coursecatlib.php');
} else { // Moodle 2.2.
    require_once($CFG->dirroot . '/course/lib.php');
}

require_once(__DIR__ . '/obfform.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Criterion form.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_criterion_form extends local_obf_form_base implements renderable {

    /**
     * @var obf_criterion
     */
    protected $criterion = null;


    /**
     * Defines forms elements
     * @see obf_criterion_course::get_form_after_save_options
     * @see obf_criterion_course::get_form_completion_options
     * @see obf_criterion_course::get_form_config
     * @see obf_criterion_course::get_options
     */
    protected function definition() {
        global $DB, $OUTPUT;
        require_once(__DIR__ . '/../class/criterion/unknown.php');

        $mform = $this->_form;
        $this->criterion = $this->_customdata['criterion'];
        $addcourse = $this->_customdata['addcourse'];

        if (!empty($addcourse)) {
            $this->get_courses($mform);
        } else { // Editing an existing criterion.
            $criterioncourses = $this->criterion->get_items();
            $courses = $this->criterion->get_related_courses();
            $showaddcourse = true;
            $showoptions = true;
            $showbuttons = true;
            // Show options.
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
                    $course->get_options($mform, $this);
                    if (!$course->criteria_supports_multiple_courses()) {
                        $showaddcourse = false;
                    }
                }
            }
            if (count($criterioncourses) > 0) {
                $criterioncourses[0]->get_form_config($mform, $this);
                if ($showaddcourse) {
                    $mform->addElement('submit', 'addcourse',
                            get_string('criteriaaddcourse', 'local_obf'), array('class' => 'addcourse'));
                }
                $criterioncourses[0]->get_form_completion_options($mform, $this, $criterioncourses);
                $criterioncourses[0]->get_form_after_save_options($mform, $this);
                $criterioncourses[0]->get_form_criteria_addendum_options($mform, $this);
            }

            if ($showbuttons) {
                $this->add_action_buttons();
            }
        }

        $elementnames = array();;
        foreach ($mform->_elements as $elm) {
            if (property_exists($elm, '_attributes') && is_array($elm->_attributes) && array_key_exists('name', $elm->_attributes)) {
                $elementnames[] = $elm->_attributes['name'];
            }
        }
        $elementnames = array_merge(array('badgeid' => PARAM_ALPHANUM), $elementnames);
        $criteriatypeids = array_keys(obf_criterion_unknown::get_criteria_type_options());
        $toadd = array();
        foreach ($criteriatypeids as $typeid) {
            $obj = obf_criterion_item::build_type($typeid);
            $fields = $obj->get_form_fields();
            foreach ($fields as $field => $param_type) {
                if (!in_array($field, $elementnames)) {
                    $toadd[$field] = $param_type;
                }
            }
        }
        foreach ($toadd as $key => $param_type) {
            if (($pos = strpos($key, '[]')) === false) {
                $mform->addElement('hidden', $key, $_REQUEST[$key]);
            } else {
                $simplekey = substr($key,0,$pos);
                if (!in_array($simplekey, $elementnames) && !in_array($key, $elementnames)) {
                    $values = array_key_exists($simplekey, $_REQUEST) ? $_REQUEST[$simplekey] : array();
                    $values = array_filter($values);
                    $addedkeys = false;
                    foreach ($values as $key => $value) {
                        # code...
                        if (!empty($value)) {
                            $fullkey = $simplekey.'['.$key.']';
                            if (!in_array($fullkey, $elementnames)) {
                                if (is_array($value)) {
                                    // TODO: Handle date array("day" => "23", "month" => "12", "year" => "2015","enabled" => "1"})
                                } else {
                                    $mform->addElement('hidden', $fullkey, $value);
                                    $mform->setType($fullkey, $param_type);
                                }
                                $addedkeys = true;
                            }
                        }
                    }
                    if ($addedkeys) {
                        $mform->addElement('hidden', $key);
                        $mform->setType($key, $param_type);
                    }
                }
            }
            $mform->setType($key, $param_type);
        }
    }
    /**
     * Add courses to the form.
     * @param MoodleQuickForm $mform
     */
    private function get_courses($mform) {
        global $DB, $OUTPUT;
        // Get only courses with course completion enabled (= can be completed somehow).
        $courses = $DB->get_records('course', array('enablecompletion' => COMPLETION_ENABLED));

        if (count($courses) > 0) {
            $categories = array();

            if (method_exists('coursecat', 'make_categories_list')) {
                $categories = coursecat::make_categories_list();
            } else { // Moodle 2.2.
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

            // Check each course category, are there any courses.
            foreach ($courselist as $name => $courses) {
                $validcourses += count($courses);
            }

            $mform->addElement('header', 'header_criterion_fields',
                    get_string('selectcourses', 'local_obf'));
            $this->setExpanded($mform, 'header_criterion_fields');
            $mform->addHelpButton('header_criterion_fields', 'readmeenablecompletion', 'local_obf');

            // There aren't any courses that aren't already in this badge's criteria.
            if ($validcourses === 0) {
                $mform->addElement('html',
                        $OUTPUT->notification(get_string('novalidcourses', 'local_obf')));
            } else { // There are courses that can be selected -> show course selection.
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
        } else { // No courses found with completion enabled.
            $mform->addElement('html',
                    $OUTPUT->notification(get_string('nocourseswithcompletionenabled',
                                    'local_obf')));
        }

        $buttons[] = $mform->createElement('cancel', 'cancelbutton',
                get_string('back', 'local_obf'));
        $mform->addGroup($buttons, 'buttonar', '', null, false);
    }
    /**
     * Get criterion.
     * @return obf_criterion
     */
    public function get_criterion() {
        return $this->criterion;
    }
    /**
     * Get form.
     * @return MoodleQuickForm
     */
    public function get_form() {
        return $this->_form;
    }
    /**
     * Initialize categories.
     * @param array $categories
     * @return array Course list.
     */
    private function initialize_categories(array $categories) {
        $courselist = array();

        // Initialize categories for the select list's optgroups.
        foreach ($categories as $category) {
            $courselist[$category] = array();
        }

        return $courselist;
    }
}
