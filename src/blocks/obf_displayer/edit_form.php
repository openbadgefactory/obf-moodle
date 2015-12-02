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
 * @package    block_obf_displayer
 * @copyright  2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_obf_displayer_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('advcheckbox', 'config_largebadges', get_string('largebadges', 'block_obf_displayer'));
        $mform->setDefault('config_largebadges', 0);

        $mform->addElement('header', 'config_providers_header', get_string('providerselect', 'block_obf_displayer'));

        $mform->addElement('advcheckbox', 'config_showobf', get_string('showobf', 'block_obf_displayer'));
        $mform->setDefault('config_showobf', 1);
        $mform->addElement('advcheckbox', 'config_showobp', get_string('showobp', 'block_obf_displayer'));
        $mform->setDefault('config_showobp', 0);
        $mform->addElement('advcheckbox', 'config_showmoz', get_string('showmoz', 'block_obf_displayer'));
        $mform->setDefault('config_showmoz', 0);
        $mform->addElement('advcheckbox', 'config_showmoodle', get_string('showmoodle', 'block_obf_displayer'));
        $mform->setDefault('config_showmoodle', 1);

        $this->setExpanded($mform, 'config_providers_header', true);
    }
    protected function setExpanded(&$mform, $header, $expanded) {
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded($header, $expanded);
        }
    }
}
