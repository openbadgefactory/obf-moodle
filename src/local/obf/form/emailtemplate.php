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
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/obfform.php');

class obf_email_template_form extends local_obf_form_base {

    private $badge = null;

    protected function definition() {
        $mform = $this->_form;
        $this->badge = $this->_customdata['badge'];

        $mform->addElement('html', html_writer::tag('p', get_string('emailtemplatedescription', 'local_obf')));
        self::add_email_fields($mform, $this->badge->get_email());
        $this->add_action_buttons(false);
    }

    /**
     *
     * @param MoodleQuickForm $mform
     * @param obf_email $email
     */
    public static function add_email_fields(MoodleQuickForm &$mform, obf_email $email = null) {
        $mform->addElement('text', 'emailsubject', get_string('emailsubject', 'local_obf'));
        $mform->setType('emailsubject', PARAM_TEXT);
        $mform->addElement('textarea', 'emailbody', get_string('emailbody', 'local_obf'),
                array('rows' => 10));
        $mform->setType('emailbody', PARAM_TEXT);
        $mform->addElement('textarea', 'emailfooter', get_string('emailfooter', 'local_obf'),
                array('rows' => 5));
        $mform->setType('emailfooter', PARAM_TEXT);

        if ($email) {
            $mform->setDefaults(array('emailsubject' => $email->get_subject(),
                'emailbody' => $email->get_body(), 'emailfooter' => $email->get_footer()));
        }
    }

}
