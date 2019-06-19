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
 * Criterion.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;

require_once(__DIR__ . '/../badge.php');
require_once(__DIR__ . '/item_base.php');
require_once(__DIR__ . '/course.php');

require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Criterion -class.
 *
 * Class representing a criterion which the student has to complete to earn
 * a badge. One criterion can contain multiple courses or other items.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
    private $completionmethod = null;

    /**
     * @var obf_criterion_item[] The courses/activities in this criterion
     */
    private $items = null;

    /**
     * @var string The id of the badge that can be earned by completing this
     *      criterion.
     */
    private $badgeid = null;

    /**
     * @var bool Use criteria addendum
     */
    private $useaddendum = false;
    
    /**
     * @var string The criteria addendum
     */
    private $criteriaaddendum = '';
    
    /**
     * @var stdClass[] A simple cache for Moodle's courses.
     */
    private static $coursecache = array();
    

    /**
     * Returns the criterion instance identified by $id
     *
     * @param int $id The id of the criterion
     * @return obf_criterion|boolean Returns the criterion instance and false
     *      if it doesn't exist.
     */
    public static function get_instance($id) {
        global $DB;
        $record = $DB->get_record('local_obf_criterion', array('id' => $id));

        if (!$record) {
            return false;
        }

        $obj = new self();
        $obj->set_badgeid($record->badge_id);
        $obj->set_id($record->id);
        $obj->set_completion_method($record->completion_method);
        $obj->set_criteria_addendum($record->addendum);
        $obj->set_use_addendum((bool)$record->use_addendum);

        return $obj;
    }

    /**
     * Updates this criterion object in database.
     *
     */
    public function update() {
        global $DB;

        $obj = new stdClass();
        $obj->id = $this->id;
        $obj->badge_id = $this->get_badgeid();
        $obj->completion_method = $this->completionmethod;
        $obj->addendum = $this->criteriaaddendum;
        $obj->use_addendum = $this->useaddendum;

        $DB->update_record('local_obf_criterion', $obj);
    }

    /**
     * Saves this criterion object to database.
     *
     * @return boolean Returns true on success, false otherwise.
     */
    public function save() {
        global $DB;

        $obj = new stdClass();
        $obj->badge_id = $this->get_badgeid();
        $obj->completion_method = $this->completionmethod;
        $obj->addendum = $this->criteriaaddendum;
        $obj->use_addendum = $this->useaddendum;

        $id = $DB->insert_record('local_obf_criterion', $obj, true);

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
     * @return boolean Returns true if the criterion has been met, false otherwise.
     */
    public function is_met() {
        global $DB;

        return ($DB->count_records('local_obf_criterion_met',
                        array('obf_criterion_id' => $this->id)) > 0);
    }
    
    public function issue_and_set_met($user, $recipients = null) {
        global $DB;

        $badge = $this->get_badge();
        if (is_null($recipients)) {
            $passport = obf_backpack::get_instance($user);

            // If the user has configured a passport connection,
            // use that instead of the default email.
            $recipients = array($passport === false ? $user->email : $passport->get_email());
        }
        $email = is_null($badge->get_email()) ? new obf_email() : $badge->get_email();

        $criteriaaddendum = $this->get_use_addendum() ? $this->get_criteria_addendum() : '';

        $eventid = $badge->issue($recipients, time(), $email, $criteriaaddendum, $this->items);
        $this->set_met_by_user($user->id);

        if ($eventid && !is_bool($eventid)) {
            $issuevent = new obf_issue_event($eventid, $DB);
            $issuevent->set_criterionid($this->get_id());
            $issuevent->save($DB);
        }
        cache_helper::invalidate_by_event('new_obf_assertion', array($user->id));
        return $eventid;
    }

    /**
     * Deletes this criterion from the database.
     *
     * @return boolean Returns true if the criterion was successfully deleted, false otherwise.
     */
    public function delete() {
        global $DB;

        if ($this->exists()) {
            $this->delete_items();
            $this->delete_met();
            $DB->delete_records('local_obf_criterion', array('id' => $this->id));
            return true;
        }

        return false;
    }

    /**
     * Deletes all criteria from the database that don't have any
     * related courses.
     * @param moodle_database $db
     */
    public static function delete_empty(moodle_database $db) {
        $subquery = 'SELECT obf_criterion_id FROM {local_obf_criterion_courses}';
        $db->delete_records_select('local_obf_criterion',
                'id NOT IN (' . $subquery . ')');
        $db->delete_records_select('local_obf_criterion_params',
                'obf_criterion_id NOT IN (' . $subquery .')');
        $db->delete_records_select('local_obf_criterion_met',
                'obf_criterion_id NOT IN (' . $subquery . ')');
    }

    /**
     * Deletes all history about meeting this criterion.
     *
     */
    public function delete_met() {
        global $DB;

        if ($this->exists()) {
            $DB->delete_records('local_obf_criterion_met',
                    array('obf_criterion_id' => $this->id));
        }
    }

    /**
     * Deletes all related courses from the database.
     *
     */
    public function delete_items() {
        global $DB;
        $DB->delete_records('local_obf_criterion_courses',
                array('obf_criterion_id' => $this->id));
        $DB->delete_records('local_obf_criterion_params',
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
     * @param bool $force Get from the database bypassing the cache.
     * @param int $criteriatype Which types to return. Default: any
     * @return obf_criterion_course[] The related course criterions.
     */
    public function get_items($force = false, $criteriatype = obf_criterion_item::CRITERIA_TYPE_ANY) {
        if (is_null($this->items) || $force) {
            $this->items = obf_criterion_item::get_criterion_items($this);
        }
        // Filter by criteriatype.

        if ($criteriatype != obf_criterion_item::CRITERIA_TYPE_ANY) {
            return array_filter($this->items,
                    function (obf_criterion_item $i) use ($criteriatype) {
                        return ($i->get_criteriatype() == $criteriatype);
                    });
        }

        return $this->items;
    }
    /**
     * Set items. (Does not save)
     * @param obf_criterion_item[] $items Array of items
     */
    public function set_items($items) {
        $this->items = $items;
        return $this;
    }
    /**
     * Set course criterions for this criterion.
     *
     * @param int[] $courseids
     * @param int $criteriatype Type of criteria to add
     * @return $this
     */
    public function set_items_by_courseids($courseids, $criteriatype = obf_criterion_item::CRITERIA_TYPE_COURSE) {
        $courses = $this->get_items(true);
        if (is_null($courseids) || count($courseids) <= 0) {
            throw new Exception("Invalid or missing course ids.");
        }
        if ($this->is_met()) {
            throw new Exception("Cannot edit met criterion.");
        }
        foreach ($courseids as $courseid) {
            if (!$this->has_course($courseid)) {
                $courseobj = obf_criterion_item::build_type($criteriatype);
                $courseobj->set_courseid($courseid);
                $courseobj->set_criterionid($this->get_id());
                $courseobj->save();
            }
        }
        foreach ($courses as $criterioncourse) {
            if ($criterioncourse->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_COURSE) {
                $courseid = $criterioncourse->get_courseid();
                if (!in_array($courseid, $courseids)) {
                    $criterioncourse->delete();
                }
            } else {
                $criterioncourse->delete();
            }

        }

        return $this;
    }

    /**
     * Returns all related Moodle courses.
     *
     * @return stdClass[] The related Moodle courses
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
                . 'FROM {local_obf_criterion_courses} '
                . 'WHERE courseid = ' . intval($courseid) . ')';
        return self::get_criteria($where);
    }

    /**
     * Returns all criteria matching $conditions.
     *
     * @param array|string $conditions The conditions after WHERE clause in SQL.
     * @return obf_criterion[] The matching criteria.
     */
    public static function get_criteria($conditions = '') {
        global $DB;

        $sql = 'SELECT cc.*, c.id AS criterionid, ' .
                'c.badge_id, c.completion_method AS crit_completion_method, c.use_addendum, c.addendum FROM {local_obf_criterion_courses} cc ' .
                'LEFT JOIN {local_obf_criterion} c ON cc.obf_criterion_id = c.id';
        $params = array();
        $cols = array();

        if (is_array($conditions) && count($conditions) > 0) {
            foreach ($conditions as $column => $value) {
                $cols[] = $column . ' = ?';
                $params[] = $value;
            }

            $sql .= ' WHERE ' . implode(' AND ', $cols);
        } else if (is_string($conditions) && !empty($conditions)) {
            $sql .= ' WHERE ' . $conditions;
        }

        $records = $DB->get_records_sql($sql, $params);
        $ret = array();

        foreach ($records as $record) {
            // Group by criterion.
            if (!isset($ret[$record->criterionid])) {
                $obj = new self();
                $obj->set_badgeid($record->badge_id);
                $obj->set_id($record->criterionid);
                $obj->set_completion_method($record->crit_completion_method);
                $obj->set_use_addendum($record->use_addendum);
                $obj->set_criteria_addendum($record->addendum);

                $ret[$record->criterionid] = $obj;
            }

            $courseobj = obf_criterion_item::build(array('criteriatype' => $record->criteria_type));
            $ret[$record->criterionid]->add_criterion_item($courseobj->populate_from_record($record));
        }

        return $ret;
    }

    /**
     * Whether the user $user has already met this criterion.
     *
     * @param stdClass $user The Moodle's user
     * @return boolean Returns true if the user has met this criterion and
     *      false otherwise.
     */
    public function is_met_by_user(stdClass $user) {
        global $DB;

        return ($DB->count_records('local_obf_criterion_met',
                        array('obf_criterion_id' => $this->id,
                    'user_id' => $user->id)) > 0);
    }

    /**
     * Set this criterion met by user identified by $userid.
     *
     * @param int $userid The id of the user
     */
    public function set_met_by_user($userid) {
        global $DB;

        $obj = new stdClass();
        $obj->obf_criterion_id = $this->id;
        $obj->user_id = $userid;
        $obj->met_at = time();

        $DB->insert_record('local_obf_criterion_met', $obj, true);
    }

    /**
     * Returns the badge related to this criterion.
     *
     * @return obf_badge The badge.
     */
    public function get_badge() {
        return (!empty($this->badge) ? $this->badge : (!empty($this->badgeid) ? obf_badge::get_instance($this->badgeid) : null));
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
        global $DB;
        require_once(__DIR__ . '/../event.php');
        // Just in case this operation takes ages, raise the limits a bit.
        set_time_limit(0);
        raise_memory_limit(MEMORY_EXTRA);

        $criterioncourses = $this->get_items(false);
        $courses = $this->get_related_courses();
        $recipientids = array();
        $recipients = array();
        $recipientemails = array();

        // Let items check their own completion.
        $selfreviewextra = array();
        $selfreviewusers = array();
        $selfreviewsupported = true;

        foreach ($criterioncourses as $crit) {
            $reviewresult = $crit->review($this, $criterioncourses,
                    $selfreviewextra);

            if (count($selfreviewusers) == 0 || !$requireall) {
                $selfreviewusers = array_merge($selfreviewusers, $reviewresult);
            } else { // Require all courses complete.
                $selfreviewusers = array_intersect_key($selfreviewusers, $reviewresult);
            }
        }
        foreach ($selfreviewusers as $user) {
            if (!$this->is_met_by_user($user)) {
                $recipients[] = $user;
                $recipientids[] = $user->id;
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
                $recipientemails[] = isset($backpackemails[$user->id]) ? $backpackemails[$user->id] : $user->email;
            }
            // Check if criterion wants to override badges expries settings.
            $expiresoverride = null;
            foreach ($criterioncourses as $crit) {
                $expiresoverride = max(array($crit->get_issue_expires_override(), $expiresoverride));
            }
            if (!is_null($expiresoverride)) {
                $badge->set_expires($expiresoverride);
            }

            $criteriaaddendum = $this->get_use_addendum() ? $this->get_criteria_addendum() : '';
            $eventid = $badge->issue($recipientemails, time(), $email, $criteriaaddendum);

            if ($eventid && !is_bool($eventid)) {
                $issuevent = new obf_issue_event($eventid, $DB);
                $issuevent->set_criterionid($this->get_id());
                $issuevent->save($DB);
            }

            // Update the database.
            foreach ($recipientids as $userid) {
                $this->set_met_by_user($userid);
            }
            cache_helper::invalidate_by_event('new_obf_assertion', $recipientids);
        }

        return count($recipientemails);
    }

    /**
     * Reviews this criterion to check whether if has been completed.
     *
     * @param int $userid The id of the user.
     * @param int $courseid The course the user has completed.
     * @param obf_criterion_course[] $criterioncourses The course criteria
     *      in this criterion to prevent extra database queries.
     *      Optional, retrieved automatically if the parameter isn't given.
     * @return boolean Whether this criterion is complete.
     */
    public function review($userid, $courseid, $criterioncourses = null) {

        if (is_null($criterioncourses)) {
            $criterioncourses = $this->get_items(true);
        }

        $requireall = $this->get_completion_method() == self::CRITERIA_COMPLETION_ALL;
        // The completed course doesn't exist in this criterion, no need to continue.
        if (!$this->has_course($courseid)) {
            return false;
        }

        $criterioncompleted = false;

        foreach ($criterioncourses as $criterioncourse) {
            $coursecompleted = $this->review_course($criterioncourse, $userid);

            // All of the courses have to be completed.
            if ($requireall) {
                if (!$coursecompleted) {
                    return false;
                } else {
                    $criterioncompleted = true;
                }
            } else { // Any of the courses has to be completed.
                if ($coursecompleted) {
                    return true;
                } else {
                    $criterioncompleted = false;
                }
            }
        }

        return $criterioncompleted;
    }

    /**
     * Reviews a single course.
     *
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

        if ($criterioncourse->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_COURSE) {
            if (!$completioninfo->is_course_complete($userid)) {
                return false;
            }
        } else if ($criterioncourse->get_criteriatype() == obf_criterion_item::CRITERIA_TYPE_ACTIVITY) {
            $params = $criterioncourse->get_params();
            $modules = array();
            foreach ($params as $param) {
                if (array_key_exists('module', $param)) {
                    $modules[] = $param['module'];
                }
            }
            $completedmodulecount = 0;
            $requireall = $this->get_completion_method() == self::CRITERIA_COMPLETION_ALL;
            foreach ($modules as $modid) {
                $cm = $DB->get_record('course_modules', array('id' => $modid));
                $completiondata = $completioninfo->get_data($cm, false, $userid);
                if (in_array($completiondata->completionstate, array(COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS))) {
                    $completedmodulecount += 1;
                } else if ($requireall) {
                    return false;
                }

            }
            if ($completedmodulecount < 1) {
                return false;
            }
        }

        $datepassed = false;
        $gradepassed = false;
        $completion = new completion_completion(array('userid' => $userid, 'course' => $courseid));
        $completedat = $completion->timecompleted;

        // Check completion date.
        if ($criterioncourse->has_completion_date()) {
            if ($completedat <= $criterioncourse->get_completedby()) {
                $datepassed = true;
            }
        } else {
            $datepassed = true;
        }

        // Check grade.
        if ($criterioncourse->has_grade()) {
            $grade = grade_get_course_grade($userid, $courseid);

            if (!is_null($grade->grade) && $grade->grade >= $criterioncourse->get_grade()) {
                $gradepassed = true;
            }
        } else {
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
    /**
     * Get id.
     * @return mixed
     */
    public function get_id() {
        return $this->id;
    }
    /**
     * Get completion method.
     * @return int
     */
    public function get_completion_method() {
        return $this->completionmethod;
    }
    /**
     * Set id
     * @param int $id
     * @return $this
     */
    public function set_id($id) {
        $this->id = $id;
        return $this;
    }
    /**
     * Add obf_criterion_item to list of items.
     * @param obf_criterion_item $item
     */
    public function add_criterion_item(obf_criterion_item $item) {
        $this->items[] = $item;
    }
    /**
     * Set completion method.
     * @param int $completionmethod
     * @see self::CRITERIA_COMPLETION_ALL
     * @see self::CRITERIA_COMPLETION_ANY
     * @return $this
     */
    public function set_completion_method($completionmethod) {
        $this->completionmethod = $completionmethod;
        return $this;
    }

    /**
     * Get badge id.
     * @return mixed Badge id
     */
    public function get_badgeid() {
        return (empty($this->badgeid) ? $this->get_badge()->get_id() : $this->badgeid);
    }
    /**
     * Set badge id
     * @param mixed $badgeid
     * @return $this
     */
    public function set_badgeid($badgeid) {
        $this->badgeid = $badgeid;
        return $this;
    }
    /**
     * Get criteria for which badge cannot be retrieved from OBF.
     *
     * @return obf_criterion[] Criteria for badges deleted from OBF.
     */
    public static function get_criteria_with_deleted_badges() {
        global $DB;
        $client = obf_client::get_instance();
        $ret = array();
        $okbadges = array();
        $failbadges = array();
        $records = $DB->get_records('local_obf_criterion');
        foreach ($records as $record) {
            $obj = new self();
            $obj->set_id($record->id);
            $obj->set_badgeid($record->badge_id);
            $obj->set_completion_method($record->completion_method);

            if (in_array($obj->get_badgeid(), $okbadges) || in_array($obj->get_badgeid(), $failbadges)) {
                if (in_array($obj->get_badgeid(), $failbadges)) {
                    $ret[] = $obj;
                }
                continue;
            }
            try {
                $badge = $client->get_badge($obj->get_badgeid());
                $okbadges[] = $obj->get_badgeid();
            } catch (Exception $e) {
                if ($e->getCode() == 404) {
                    $failbadges[] = $obj->get_badgeid();
                    $ret[] = $obj;
                } else {
                    debugging('Criteria with badge_id: ' . $obj->get_badgeid() .
                            ' caused an error, possible connection issue. Error code: '. $client->get_http_code());
                }
            }
        }
        return $ret;
    }
    public function get_use_addendum() {
        return $this->useaddendum;
    }

    public function get_criteria_addendum() {
        return $this->criteriaaddendum;
    }

    public function set_use_addendum($useaddendum) {
        $this->useaddendum = $useaddendum;
        return $this;
    }

    public function set_criteria_addendum($criteriaaddendum) {
        $this->criteriaaddendum = $criteriaaddendum;
        return $this;
    }
}
