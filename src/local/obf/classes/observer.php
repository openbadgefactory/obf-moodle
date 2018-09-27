<?php

/*
 * Copyright (c) 2016 Discendum Oy

 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.

 */

/**
 * local_obf event observers
 *
 * @author jsuorsa
 */
class local_obf_observer {
    
    
    /**
     * Require common libs.
     *
     * @param array $obflibs array of paths relative to local/obf.
     * @global type $CFG
     */
    private static function requires($obflibs = null) {
        global $CFG;
        require_once($CFG->dirroot . '/local/obf/lib.php');
        if (is_array($obflibs)) {
            foreach($obflibs as $obflib) {
                require_once($CFG->dirroot . '/local/obf' . $obflib);
            }
        }
    }


    /**
     * Course completed observer
     *
     * @param \core\event\course_completed $eventdata
     * @return boolean Returns true if everything went ok.
     * @see self::course_user_completion_review()
     */
        public static function course_completed(\core\event\course_completed $event) {
        $eventdata = new stdClass();
        $eventdata->userid = $event->relateduserid;
        $eventdata->course = $event->courseid;
        return self::course_user_completion_review($eventdata);
   }
   
    
   
  

    /**
     * Reviews the badge criteria and issues the badges (if necessary) when
     * a course is completed.
     *
     * @param \core\event\course_completed $eventdata
     * @return boolean Returns true if everything went ok.
     */
    private static function course_user_completion_review(stdClass $eventdata) {
        global $DB;
        self::requires(array('/class/event.php'));
        $user = $DB->get_record('user', array('id' => $eventdata->userid));
        $backpack = obf_backpack::get_instance($user);

        // If the user has configured the backpack settings, use the backpack email instead of the
        // default email.
        $recipients = array($backpack === false ? $user->email : $backpack->get_email());

        // No capability -> no badge.
        if (!has_capability('local/obf:earnbadge',
                       context_course::instance($eventdata->course),
                       $eventdata->userid)) {
           return true;
        }

        // Get all criteria related to course completion.
        $criteria = obf_criterion::get_course_criterion($eventdata->course);

        foreach ($criteria as $criterionid => $criterion) {
           // User has already met this criterion.
           if ($criterion->is_met_by_user($user)) {
               continue;
           }

           // Has the user completed all the required criteria (completion/grade/date)
           // in this criterion?
           $criterionmet = $criterion->review($eventdata->userid,
                   $eventdata->course);

           // Criterion was met, issue the badge.
           if ($criterionmet) {
               $criterion->issue_and_set_met($user, $recipients);
           }
       }
       return true;
    }

    /**
     * Review course completion when course module completion is updated.
     *
     * @param \core\event\course_module_completion_updated $event
     * @return boolean Returns true if everything went ok.
     */
    public static function course_module_completion_updated(\core\event\course_module_completion_updated $event) {
        self::requires();
        $eventdata = $event->get_record_snapshot('course_modules_completion', $event->objectid);
        $context = context_module::instance($eventdata->coursemoduleid);
        if ($context && $context->get_course_context()) {
            $eventdata->course = $context->get_course_context()->instanceid;
            return self::course_user_completion_review($eventdata);
        }
    }

    /**
     * When the course is deleted, this function deletes also the related badge
     * issuance criteria.
     *
     * @param \core\event\course_deleted $event
     * @return boolean
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;
        self::requires();
        $course = $event->get_record_snapshot('course', $event->objectid);
        $course->context = new stdClass();
        $course->context->id = $event->courseid;

        obf_criterion_course::delete_by_course($course, $DB);
        return true;
    }
    
     /**
     * When the course is reset, this function deletes the related badge 
     * issuance criteria.
     *
     * @param \core\event\course_reset_ended $event
     * returns boolean
     */
    public static function course_reset_start(\core\event\course_reset_started $event) { 
        global $DB;
        self::requires();
        
        if (get_config('local_obf','coursereset')) {
            $course = $event->get_record_snapshot('course', $event->courseid);
            $course->context = new stdClass();
            $course->context->id = $event->courseid;
            
            obf_criterion_course::delete_by_course($course, $DB);
        }
        return true;
   }

   
    
    /**
     * Triggered when 'user_updated' event happens.
     *
     * @param \core\event\user_updated $event event generated when user profile is updated.
     */
    public static function profile_criteria_review(\core\event\user_updated $event) {
        global $DB, $CFG;
        self::requires(array('/class/event.php', '/class/criterion/item_base.php'));
        
        $userid = $event->objectid;

        if ($rs = $DB->get_records('local_obf_criterion_courses', array('criteria_type' => obf_criterion_item::CRITERIA_TYPE_PROFILE))) {
            $user = $DB->get_record('user', array('id' => $userid));
            foreach($rs as $critres) {
                $critarr = (array)$critres;
                $crit = obf_criterion_item::build($critarr);
                $criterion = $crit->get_criterion();
                if ($criterion->is_met_by_user($user)) {
                    continue;
                }
                
                
                // Review & issue
                $criterionmet = $crit->review_for_user($user, $criterion);

                // Criterion was met, issue the badge.
                if ($criterionmet) {
                    $criterion->issue_and_set_met($user);
                }
            }
        }
    }
}
