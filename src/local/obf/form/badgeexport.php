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
 * Badge export form.
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once($CFG->libdir . '/formslib.php');
/**
 * Badge export form.
 *
 * Form for exporting Moodle's badges to Open Badge Factory.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_badge_export_form extends local_obf_form_base {
    /**
     * Defines forms elements
     */
    protected function definition() {
        global $OUTPUT;
        $mform = $this->_form;
        $badges = $this->_customdata['badges'];
        $obfbadges = $this->_customdata['obfbadges'];

        $mform->addElement('header', 'header_badgeselect',
                get_string('selectbadgestoexport', 'local_obf'));

        if (count($badges) === 0) {
            $mform->addElement('html', '<p>' . get_string('nobadgestoexport', 'local_obf') . '</p>');
        }
        $exportablecount = 0;
        foreach ($badges as $badge) {
            if (!self::moodle_badge_in_obf_badge_array($badge, $obfbadges)) {
                $label = print_badge_image($badge, $badge->get_context()) . ' ' . s($badge->name);
                $mform->addElement('advcheckbox', 'toexport[' . $badge->id . ']', '',
                        $label, array('group' => 1));
                $exportablecount += 1;
            }
        }
        $this->add_checkbox_controller(1);
        if ($exportablecount == 0) {
            $mform->addElement('html',
                    $OUTPUT->notification(get_string('badgeexportzeroexportable', 'local_obf'), 'notifymessage'));
        } else if (count($badges) > 0) {
            $mform->addElement('html',
                    $OUTPUT->notification(get_string('badgeexportdescription', 'local_obf'), 'notifymessage'));
        }

        $mform->addElement('header', 'header_disablebadges',
                get_string('exportextrasettings', 'local_obf'));

        if (count($badges) > 0) {
            $mform->addElement('hidden', 'makedrafts', 0);
            $mform->setType('makedrafts', PARAM_INT);
        }

        $mform->addElement('advcheckbox', 'disablemoodlebadges', '',
                get_string('disablemoodlebadges', 'local_obf'));
        $mform->addHelpButton('disablemoodlebadges', 'disablemoodlebadges', 'local_obf'); 
        $mform->setDefault('disablemoodlebadges', (boolean)get_config('local_obf', 'disablemoodlebadges'));

        $mform->addElement('advcheckbox', 'displaymoodlebadges', '',
                get_string('displaymoodlebadges', 'local_obf'));
        $mform->addHelpButton('displaymoodlebadges', 'displaymoodlebadges', 'local_obf');
        $mform->setDefault('displaymoodlebadges', (boolean)get_config('local_obf', 'displaymoodlebadges'));

        $this->add_action_buttons(false,
                get_string('saveconfiguration', 'local_obf'));
    }
    /**
     * Check if moodle badge is already exported as an OBF badge.
     * @param badge $moodlebadge
     * @param obf_badge[] $obfbadges
     * @return boolean OBF with the same name already exists.
     */
    private static function moodle_badge_in_obf_badge_array($moodlebadge, $obfbadges) {
        foreach ($obfbadges as $obfbadge) {
            if ($moodlebadge->name == $obfbadge->get_name()) {
                return true;
            }
        }
        return false;
    }

}
