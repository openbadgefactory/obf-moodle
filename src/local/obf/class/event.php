<?php

class obf_issue_event {

    private $id = -1;
    private $eventid = null;
    private $userid = -1;
    private $criterionid = -1;

    /**
     * Returns the issue event by id.
     *
     * @param integer $eventid Event id.
     * @param moodle_database $db The db instance.
     * @return \self|null Returns this object on success, null otherwise.
     */
    public function __construct($eventid, moodle_database $db) {
        $record = $db->get_record('obf_issue_events',
                array('event_id' => $eventid));

        if ($record !== false) {
            $this->set_id($record->id)
                    ->set_eventid($record->event_id)
                    ->set_criterionid($record->obf_criterion_id)
                    ->set_userid($record->user_id);
        } else {
            $this->set_eventid($eventid);
        }

        return null;
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

        if ($this->id > 0) {
            $obj->id = $this->id;
            $db->update_record('obf_issue_events', $obj);
        } else {
            $db->insert_record('obf_issue_events', $obj);
        }
    }

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function get_eventid() {
        return $this->event_id;
    }

    public function set_eventid($eventid) {
        $this->eventid = $eventid;
        return $this;
    }

    public function get_userid() {
        return $this->userid;
    }

    public function set_userid($userid) {
        if (!empty($userid) && $userid > 0) {
            $this->criterionid = null;
        }
        $this->userid = $userid;
        return $this;
    }

    public function has_userid() {
        return !empty($this->userid) && $this->userid > 0;
    }

    public function get_criterionid() {
        return $this->criterionid;
    }

    public function set_criterionid($criterionid) {
        if (!empty($criterionid) && $criterionid > 0) {
            $this->userid = null;
        }
        $this->criterionid = $criterionid;
        return $this;
    }

    public function has_criterionid() {
        return !empty($this->criterionid) && $this->criterionid > 0;
    }

}
