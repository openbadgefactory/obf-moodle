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
 * Base OBF Form to extend from.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once($CFG->libdir . '/formslib.php');

/**
 * This class is just to add support for older versions of Moodle.
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class local_obf_form_base extends moodleform {
    /**
     * Render form.
     * @return string HTML.
     */
    public function render() {
        $oldclasses = $this->_form->getAttribute('class');
        $this->_form->updateAttributes(array('class' => $oldclasses.' local-obf'));
        ob_start();
        $this->display();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }
    /**
     * Set expanded, if Moodle version supports the feature.
     * @param MoodleQuickForm& $mform
     * @param string $header
     * @param bool $expand
     */
    public function setExpanded(&$mform, $header, $expand = true) {
        // Moodle 2.2 doesn't have setExpanded.
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded($header, $expand);
        }
    }

}
