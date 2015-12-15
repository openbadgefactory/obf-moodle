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
 * Email templates.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Email templates -class.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_email {
    /**
     * @var int ID in the database
     */
    private $id = -1;
    /**
     * @var string badgeid in OBF
     */
    private $badgeid = null;
    /**
     * @var string Email subject
     */
    private $subject = '';
    /**
     * @var string Email body
     */
    private $body = '';
    /**
     * @var string Email footer.
     */
    private $footer = '';
    
    /**
     * @var string Email link text
     */
    private $linktext = '';

    /**
     * Returns the email instance related to a badge.
     *
     * @param obf_badge $badge The related badge.
     * @param moodle_database $db The db instance.
     * @return \self|null Returns this object on success, null otherwise.
     */
    public static function get_by_badge(obf_badge $badge, moodle_database $db) {
        $record = $db->get_record('local_obf_email_templates',
                array('badge_id' => $badge->get_id()));

        if ($record !== false) {
            $obj = new self();
            $obj->set_badge_id($badge->get_id())->set_id($record->id);
            $obj->set_subject($record->subject)->set_body($record->body)->set_footer($record->footer);
            $obj->set_link_text($record->link_text);
            return $obj;
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
        $obj->subject = $this->subject;
        $obj->body = $this->body;
        $obj->footer = $this->footer;
        $obj->link_text = $this->linktext;
        $obj->badge_id = $this->badgeid;

        if ($this->id > 0) {
            $obj->id = $this->id;
            $db->update_record('local_obf_email_templates', $obj);
        } else {
            $db->insert_record('local_obf_email_templates', $obj);
        }
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
     * @return $this
     */
    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Get badge id
     * @return string Badge id
     */
    public function get_badge_id() {
        return $this->badgeid;
    }

    /**
     * Set badge id.
     * @param string $badgeid Badge id
     */
    public function set_badge_id($badgeid) {
        $this->badgeid = $badgeid;
        return $this;
    }

    /**
     * Get email subject.
     * @return string Subject
     */
    public function get_subject() {
        return $this->subject;
    }

    /**
     * Set email subject
     * @param string $subject
     */
    public function set_subject($subject) {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Get email body.
     * @return string Email body
     */
    public function get_body() {
        return $this->body;
    }

    /**
     * Set email body.
     * @param string $body Email body
     */
    public function set_body($body) {
        $this->body = $body;
        return $this;
    }

    /**
     * Get email footer.
     * @return string Email footer
     */
    public function get_footer() {
        return $this->footer;
    }

    /**
     * Set email footer.
     * @param string $footer Email footer
     */
    public function set_footer($footer) {
        $this->footer = $footer;
        return $this;
    }
    
    public function get_link_text() {
        return $this->linktext;
    }

    public function set_link_text($linktext) {
        $this->linktext = $linktext;
        return $this;
    }


}
