<?php

global $CFG;

require_once __DIR__ . '/../badge.php';
require_once __DIR__ . '/course.php';
require_once $CFG->dirroot . '/grade/querylib.php';
require_once $CFG->libdir . '/gradelib.php';
require_once $CFG->libdir . '/completionlib.php';

/**
 * Class representing a criterion which the student has to complete to earn
 * a badge. One criterion can contain multiple courses.
 */
class obf_criterion {

    /**
     * Student has to complete all courses to earn a badge.
     */
    const CRITERIA_COMPLETION_ALL = 1;

    /**
     * Student has to complete any course in this criterion to earn a badge.
     */
    const CRITERIA_COMPLETION_ANY = 2;

    /**
     * @var obf_badge The badge that can be earned by completing this criterion.
     */
    private $badge = null;

    /**
     * @var int The id of this criterion.
     */
    private $id = -1;

    /**
     * @var int Whether the student has to complete all or any of the courses
     *      to earn a badge.
     */
    private $completion_method = null;

    /**
     * @var obf_criterion_course[] The courses in this criterion
     */
    private $items = null;

    /**
     * @var string The id of the badge that can be earned by completing this
     *      criterion.
     */
    private $badgeid = null;

    /**
     * @var stdClass[] A simple cache for Moodle's courses.
     */
    private static $coursecache = array();

    /**
     * Returns the criterion instance identified by $id
     *
     * @global moodle_database $DB
     * @param int $id The id of the criterion
     * @return obf_criterion|boolean Returns the criterion instance and false
     *      if it doesn't exist.
     */
    public static function get_instance($id) {
        global $DB;
        $record = $DB->get_record('obf_criterion', array('id' => $id));

        if (!$record) {
            return false;
        }

        $obj = new self();
        $obj->set_badgeid($record->badge_id);
        $obj->set_id($record->id);
        $obj->set_completion_method($record->completion_method);

        return $obj;
    }

    /**
     * Updates this criterion object in database.
     *
     * @global moodle_database $DB
     */
    public function update() {
        global $DB;

        $obj = new stdClass();
        $obj->id = $this->id;
        $obj->badge_id = $this->get_badgeid();
        $obj->completion_method = $this->completion_method;

        $DB->update_record('obf_criterion', $obj);
    }

    /**
     * Saves this criterion object to database.
     *
     * @global moodle_database $DB
     * @return boolean Returns true on success, false otherwise.
     */
    public function save() {
        global $DB;

        $obj = new stdClass();
        $obj->badge_id = $this->get_badgeid();
        $obj->completion_method = $this->completion_method;

        $id = $DB->insert_record('obf_criterion', $obj, true);

        if ($id === false) {
            return false;
        }

        $this->set_id($id);

        return true;
    }

    /**
     * Checks whether this instance exists in database.
     *
     * @return boolean Returns true on success, false otherwise.
     */
    public function exists() {
        return $this->id > 0;
    }

    /**
     * Checks, whether this criterion has already been met at least once.
     *
     * @global moodle_database $DB
     * @return boolean Returns true if the criterion has been met, false otherwise.
     */
    public function is_met() {
        global $DB;

        return ($DB->count_records('obf_criterion_met',
                        array('obf_criterion_id' => $this->id)) > 0);
    }

    /**
     * Deletes this criterion from the database.
     *
     * @global moodle_database $DB
     * @return boolean Returns true if the criterion was successfully deleted, false otherwise.
     */
    public function delete() {
        global $DB;

        if ($this->exists()) {
            $this->delete_items();
            $this->delete_met();
            $DB->delete_records('obf_criterion', array('id' => $this->id));
            return true;
        }

        return false;
    }

    /**
     * Deletes all criteria from the database that don't have any
     * related courses.
     */
    public static function delete_empty(moodle_database $db) {
        $subquery = 'SELECT obf_criterion_id FROM {obf_criterion_courses}';
        $db->delete_records_select('obf_criterion',
                'id NOT IN (' . $subquery . ')');
        $db->delete_records_select('obf_criterion_met',
                'obf_criterion_id NOT IN (' . $subquery . ')');
    }

    /**
     * Deletes all history about meeting this criterion.
     *
     * @global moodle_database $DB
     */
    public function delete_met() {
        global $DB;

        if ($this->exists()) {
            $DB->delete_records('obf_criterion_met',
                    array('obf_criterion_id' => $this->id));
        }
    }

    /**
     * Deletes all related courses from the database.
     *
     * @global moodle_database $DB
     */
    public function delete_items() {
        global $DB;
        $DB->delete_records('obf_criterion_courses',
                array('obf_criterion_id' => $this->id));
        $this->items = array();
    }

    /**
     * Returns all criteria related to $badge.
     *
     * @param obf_badge $badge
     * @return obf_criterion[] An array of criteria.
     */
    public static function get_badge_criteria(obf_badge $badge) {
        $conditions = array('c.badge_id' => $badge->get_id());

        return self::get_criteria($conditions, $badge);
    }

    /**
     * Returns all course criterions related to this criterion.
     *
     * @param type $force Get from the database bypassing the cache.
     * @return type obf_criterion_course[] The related course criterions.
     */
    public function get_items($force = false) {
        if (is_null($this->items) || $force) {
            $this->items = obf_criterion_course::get_criterion_courses($this);
        }

        return $this->items;
    }
    /**
     * Set course criterions for this criterion.
     *
     * @param array $courseids.
     * @return obf_criterion
     */
    public function set_items_by_courseids($courseids) {
        $this->items = $this->get_items(true);
        if (is_null($courseids) || count($courseids) <= 0) {
            throw new Exception("Invalid or missing course ids.");
        }
        if ($this->is_met()) {
            throw new Exception("Cannot edit met criterion.");
        }
        foreach ($courseids as $courseid) {
            if (!$this->has_course($courseid)) {
                $courseobj = new obf_criterion_course();
                $courseobj->set_courseid($courseid)
                    ->set_criterionid($this->get_id())
                    ->save();
            }
        }
        foreach ($this->items as $criterioncourse) {
            $courseid = $criterioncourse->get_courseid();
            if (!in_array($courseid,$courseids)) {
                $criterioncourse->delete();
            }
        }

        return $this;
    }

    /**
     * Returns all related Moodle courses.
     *
     * @global moodle_database $DB
     * @return type stdClass[] The related Moodle courses
     */
    public function get_related_courses() {
        global $DB;

        $courses = $this->get_items();
        $courseids = array();

        foreach ($courses as $course) {
            $courseids[] = $course->get_courseid();
        }

        $records = $DB->get_records_list('course', 'id', $courseids);
        $ret = array();

        foreach ($records as $record) {
            $ret[$record->id] = $record;
            self::$coursecache[$record->id] = $record;
        }

        return $ret;
    }

    /**
     * Returns the Moodle course object matching $courseid.
     *
     * @param int $courseid
     * @global moodle_database $DB
     * @return stdClass The Moodle's course object.
     */
    public function get_course($courseid) {
        global $DB;
        if (!isset(self::$coursecache[$courseid])) {
            $params = array('id' => $courseid);
            self::$coursecache[$courseid] = $DB->get_record('course', $params);
        }

        return self::$coursecache[$courseid];
    }

    /**
     * Returns all criteria containing course identified by $courseid.
     *
     * @param int $courseid The id of the course.
     * @return obf_criterion[] The matching criteria.
     */
    public static function get_course_criterion($courseid) {
        $where = 'c.id IN (SELECT obf_criterion_id '
                . 'FROM {obf_criterion_courses} '
                . 'WHERE courseid = ' . intval($courseid) . ')';
        return self::get_criteria($where);
    }

    /**
     * Returns all criteria matching $conditions.
     *
     * @global moodle_database $DB
     * @param array|string $conditions The conditions after WHERE clause in SQL.
     * @return obf_criterion[] The matching criteria.
     */
    public static function get_criteria($conditions = '') {
        global $DB;

        $sql = 'SELECT cc.*, c.id AS criterionid, c.badge_id, c.completion_method ' .
                'FROM {obf_criterion_courses} cc ' .
                'LEFT JOIN {obf_criterion} c ON cc.obf_criterion_id = c.id';
        $params = array();
        $cols = array();

        if (is_array($conditions) && count($conditions) > 0) {
            foreach ($conditions as $column => $value) {
                $cols[] = $column . ' = ?';
                $params[] = $value;
            }

            $sql .= ' WHERE ' . implode(' AND ', $cols);
        }
        else if (is_string($conditions) && !empty($conditions)) {
            $sql .= ' WHERE ' . $conditions;
        }

        $records = $DB->get_records_sql($sql, $params);
        $ret = array();

        foreach ($records as $record) {
            // Group by criterion
            if (!isset($ret[$record->criterionid])) {
                $obj = new self();
                $obj->set_badgeid($record->badge_id);
                $obj->set_id($record->criterionid);
                $obj->set_completion_method($record->completion_method);

                $ret[$record->criterionid] = $obj;
            }

            $courseobj = new obf_criterion_course();
            $ret[$record->criterionid]->add_criterion_item($courseobj->populate_from_record($record));
        }

        return $ret;
    }

    /**
     * Whether the user $user has already met this criterion.
     *
     * @global moodle_database $DB
     * @param stdClass $user The Moodle's user
     * @return boolean Returns true if the user has met this criterion and
     *      false otherwise.
     */
    public function is_met_by_user(stdClass $user) {
        global $DB;

        return ($DB->count_records('obf_criterion_met',
                        array('obf_criterion_id' => $this->id,
                    'user_id' => $user->id)) > 0);
    }

    /**
     * Set this criterion met by user identified by $userid.
     *
     * @global moodle_database $DB
     * @param type $userid The id of the user
     */
    public function set_met_by_user($userid) {
        global $DB;

        $obj = new stdClass();
        $obj->obf_criterion_id = $this->id;
        $obj->user_id = $userid;
        $obj->met_at = time();

        $DB->insert_record('obf_criterion_met', $obj, true);
    }

    /**
     * Returns the badge related to this criterion.
     *
     * @return obf_badge The badge.
     */
    public function get_badge() {
        return (!empty($this->badge) ? $this->badge : (!empty($this->badgeid) ? obf_badge::get_instance($this->badgeid)
                                    : null));
    }

    /**
     * Checks whether this criterion contains the course identified
     * by $courseid.
     *
     * @param int $courseid
     * @return boolean True if this criterion contains the course and false
     *      otherwise.
     */
    public function has_course($courseid) {
        $courses = $this->get_items();

        foreach ($courses as $criterioncourse) {
            if ($criterioncourse->get_courseid() == $courseid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reviews all the previous completions related to courses in this criterion
     * and issues the badge if the criterion is met by the user.
     *
     * @return int To how many users the badge was issued automatically when
     *      reviewing.
     */
    public function review_previous_completions() {
        // Just in case this operation takes ages, raise the limits a bit.
        set_time_limit(0);
        raise_memory_limit(MEMORY_EXTRA);

        $criterioncourses = $this->get_items();
        $courses = $this->get_related_courses();
        $recipientids = array();
        $recipients = array();
        $recipientemails = array();

        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            // The all users that are (and were?) enrolled to this course with
            // the capability of earning badges.
            $users = get_enrolled_users($context, 'local/obf:earnbadge', 0,
                    'u.id, u.email');

            // Review all enrolled users of this course separately
            foreach ($users as $user) {
                if ($this->review($user->id, $course->id, $criterioncourses)) {
                    $recipients[] = $user;
                    $recipientids[] = $user->id;
                }
            }
        }

        // We found users that have completed this criterion. Let's issue some
        // badges, then!
        if (count($recipients) > 0) {
            $badge = $this->get_badge();
            $email = $badge->get_email();

            if (is_null($email)) {
                $email = new obf_email();
            }

            $backpackemails = obf_backpack::get_emails_by_userids($recipientids);

            foreach ($recipients as $user) {
                $recipientemails[] = isset($backpackemails[$user->id]) ? $backpackemails[$user->id]
                            : $user->email;
            }

            $badge->issue($recipientemails, time(), $email->get_subject(),
                    $email->get_body(), $email->get_footer());

            // Update the database
            foreach ($recipientids as $userid) {
                $this->set_met_by_user($userid);
            }
        }

        return count($recipientemails);
    }

    /**
     * Reviews this criterion to check whether if has been completed.
     *
     * @param int $userid The id of the user.
     * @param int $courseid The course the user has completed.
     * @param obf_criterion_course[] The course criteria in this criterion to
     *      prevent extra database queries. Optional, retrieved automatically
     *      if the parameter isn't given.
     * @return boolean Whether this criterion is complete.
     */
    public function review($userid, $courseid, $criterioncourses = null) {

        if (is_null($criterioncourses)) {
            $criterioncourses = $this->get_items(true);
        }

        $requireall = $this->get_completion_method() == obf_criterion::CRITERIA_COMPLETION_ALL;

        // The completed course doesn't exist in this criterion, no need to continue
        if (!$this->has_course($courseid)) {
            return false;
        }

        $criterioncompleted = false;

        foreach ($criterioncourses as $criterioncourse) {
            $coursecompleted = $this->review_course($criterioncourse, $userid);

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
                }
                else {
                    $criterioncompleted = false;
                }
            }
        }

        return $criterioncompleted;
    }

    /**
     * Reviews a single course.
     *
     * @global moodle_database $DB
     * @param obf_criterion_course $criterioncourse The course criterion.
     * @param int $userid The id of the user.
     * @return boolean If the course criterion is completed by the user.
     */
    protected function review_course(obf_criterion_course $criterioncourse,
            $userid) {
        global $DB;

        $courseid = $criterioncourse->get_courseid();
        $course = $this->get_course($courseid);
        $completioninfo = new completion_info($course);

        if (!$completioninfo->is_course_complete($userid)) {
            return false;
        }

        $datepassed = false;
        $gradepassed = false;
        $completion = new completion_completion(array('userid' => $userid, 'course' => $courseid));
        $completedat = $completion->timecompleted;

        // check completion date
        if ($criterioncourse->has_completion_date()) {
            if ($completedat <= $criterioncourse->get_completedby()) {
                $datepassed = true;
            }
        }
        else {
            $datepassed = true;
        }

        // check grade
        if ($criterioncourse->has_grade()) {
            $grade = grade_get_course_grade($userid, $courseid);

            if (!is_null($grade->grade) && $grade->grade >= $criterioncourse->get_grade()) {
                $gradepassed = true;
            }
        }
        else {
            $gradepassed = true;
        }

        return $datepassed && $gradepassed;
    }

    /**
     * Sets the badge related to this criterion.
     *
     * @param obf_badge $badge The badge.
     * @return \obf_criterion
     */
    public function set_badge(obf_badge $badge) {
        $this->badge = $badge;
        $this->badge_id = $badge->get_id();
        return $this;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_completion_method() {
        return $this->completion_method;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function add_criterion_item(obf_criterion_course $item) {
        $this->items[] = $item;
    }

    public function set_completion_method($completion_method) {
        $this->completion_method = $completion_method;
        return $this;
    }

    public function get_badgeid() {
        return (empty($this->badgeid) ? $this->get_badge()->get_id() : $this->badgeid);
    }

    public function set_badgeid($badgeid) {
        $this->badgeid = $badgeid;
        return $this;
    }

}
