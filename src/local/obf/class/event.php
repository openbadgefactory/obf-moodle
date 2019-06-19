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
 * Issue events.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Issue events -class.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_issue_event {
    /**
     * @var int ID of event in the database.
     */
    private $id = -1;
    /**
     * @var string ID of event in OBF.
     */
    private $eventid = null;
    /**
     * @var int User ID, if a user manually issued the event.
     */
    private $userid = -1;
    /**
     * @var int Criterion id, if the event was issued automatically by a criterion.
     */
    private $criterionid = -1;

    /**
     * Returns the issue event by id.
     *
     * @param integer $eventid Event id.
     * @param moodle_database $db The db instance.
     * @return \self|null Returns this object on success, null otherwise.
     */
    public function __construct($eventid = null, moodle_database $db = null) {
        if (!is_null($eventid) && !is_null($db)) {
            $record = $db->get_record('local_obf_issue_events',
                    array('event_id' => $eventid));

            if ($record !== false) {
                $this->set_id($record->id)->set_eventid($record->event_id);
                $this->set_criterionid($record->obf_criterion_id)->set_userid($record->user_id);
            } else {
                $this->set_eventid($eventid);
            }
        }
    }
    /**
     * Populate object from a database record.
     * @param stdClass $record
     * @return $this
     */
    public function populate_from_record($record) {
        if ($record !== false) {
            $this->set_id($record->id)->set_eventid($record->event_id);
            $this->set_criterionid($record->obf_criterion_id)->set_userid($record->user_id);
        }
        return $this;
    }
    
    public static function get_criterion_events($criterion) {
        global $DB;
        $criterionid = is_number($criterion) ? $criterion : $criterion->get_id();
        $records = $DB->get_records('local_obf_issue_events', array('obf_criterion_id' => $criterionid ));
        if (false == $records) {
            return array();
        }
        $events = array();
        foreach($records as $record) {
            $obj = new self();
            $events[] = $obj->populate_from_record($record);
        }
        return $events;
    }
    /**
     * Get all events that were issued by a criterion that is related to a course.
     * @param int $courseid
     * @param moodle_database $db
     */
    public static function get_events_in_course($courseid, moodle_database $db) {
        $ret = array();
        $sql = 'SELECT evt.* FROM {local_obf_issue_events} AS evt ' .
                'LEFT JOIN {local_obf_criterion_courses} AS cc ' .
                'ON (evt.obf_criterion_id=cc.obf_criterion_id) ' .
                'WHERE cc.courseid = (?) AND evt.obf_criterion_id IS NOT NULL';
        $params = array($courseid);
        $records = $db->get_records_sql($sql, $params);
        foreach ($records as $record) {
            $obj = new self();
            $ret[] = $obj->populate_from_record($record);
        }
        return $ret;
    }

    /**
     * @param $events
     * @param moodle_database $db
     * @return array
     */
    public static function get_course_related_events($events, moodle_database $db) {
        $ret = array();
        try {
            list($insql, $inparams) = $db->get_in_or_equal($events);
            $sql = "SELECT evt.* FROM {local_obf_issue_events} 
                AS evt WHERE evt.event_id $insql";
            $records = $db->get_records_sql($sql, $inparams);
            foreach ($records as $record) {
                $obj = new self();
                $ret[] = $obj->populate_from_record($record);
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        return $ret;
    }

    /**
     * Saves this email instance.
     *
     * @param moodle_database $db
     */
    public function save(moodle_database $db) {
        $obj = new stdClass();
        $obj->user_id = $this->userid;
        $obj->obf_criterion_id = $this->criterionid;
        $obj->event_id = $this->eventid;

        $id = $this->id > 0 ? $this->id : false;
        if ($this->id > 0) {
            $obj->id = $this->id;
            $db->update_record('local_obf_issue_events', $obj);
        } else {
            $id = $db->insert_record('local_obf_issue_events', $obj, true);
        }
        
        if ($id === false) {
            return false;
        }

        $this->set_id($id);
    }

    /**
     * Get id.
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Set id.
     * @param int $id
     */
    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Get event id.
     * @return string The event id in Open Badge Factory
     */
    public function get_eventid() {
        return $this->eventid;
    }

    /**
     * Set event id.
     * @param string $eventid The event id in Open Badge Factory
     * @return $this
     */
    public function set_eventid($eventid) {
        $this->eventid = $eventid;
        return $this;
    }

    /**
     * Get user id.
     * @return int
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * Set user id.
     * @param int $userid
     * @return $this
     */
    public function set_userid($userid) {
        if (!empty($userid) && $userid > 0) {
            $this->criterionid = null;
        }
        $this->userid = $userid;
        return $this;
    }

    /**
     * Has user id?
     * @return boolean True if object has a userid.
     */
    public function has_userid() {
        return !empty($this->userid) && $this->userid > 0;
    }

    /**
     * Get criterion id.
     * @return int Criterion id
     */
    public function get_criterionid() {
        return $this->criterionid;
    }

    /**
     * Set criterion id.
     * @param int $criterionid Criterion id
     */
    public function set_criterionid($criterionid) {
        if (!empty($criterionid) && $criterionid > 0) {
            $this->userid = null;
        }
        $this->criterionid = $criterionid;
        return $this;
    }

    /**
     * Has criterion id?
     * @return boolean True if object has a criterion id set.
     */
    public function has_criterionid() {
        return !empty($this->criterionid) && $this->criterionid > 0;
    }

}
