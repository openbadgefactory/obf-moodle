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
 * User's badge blacklist form.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');
require_once(__DIR__ . '/../renderer.php');
/**
 * Badge blacklisting form.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_blacklist_form extends local_obf_form_base {
    /**
     * @var obf_blacklist The blacklist
     */
    private $blacklist;

    /**
     * Defines forms elements
     */
    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $this->blacklist = $this->_customdata['blacklist'];
        $user = $this->_customdata['user'];
        $client = new obf_client();
        $uniqueassertions = new obf_assertion_collection();
        if (obf_client::has_client_id()) {
            try {
                $assertions = obf_assertion::get_assertions($client, null, $user->email);
            } catch (Exception $ex) {
                $mform->addElement('html', $OUTPUT->notification($ex->getMessage(), 'warning') );
                return;
            }
            
            $uniqueassertions->add_collection($assertions);
        }
        

        $this->render_badges($uniqueassertions, $mform);

        $this->add_action_buttons();
    }
    /**
     * Render badges that are blacklistable.
     * @param obf_assertion_collection $assertions
     * @param MoodleQuickForm& $mform
     */
    private function render_badges(obf_assertion_collection $assertions, &$mform) {
        global $PAGE, $OUTPUT;

        $items = array();
        $renderer = $PAGE->get_renderer('local_obf');
        $size = local_obf_renderer::BADGE_IMAGE_SIZE_NORMAL;

        $mform->addElement('html', $OUTPUT->notification(get_string('blacklistdescription', 'local_obf'), 'notifymessage'));

        for ($i = 0; $i < count($assertions); $i++) {
            $assertion = $assertions->get_assertion($i);
            $badge = $assertion->get_badge();
            $html = $OUTPUT->box(local_obf_html::div($renderer->render_single_simple_assertion($assertion, true) ));
            $items[] = $mform->createElement('advcheckbox', 'blacklist['.$badge->get_id().']',
                    '', $html);
        }
        if (count($items) > 0) {
            $mform->addGroup($items, 'blacklist', '', array(' '), false);
        }

        $badgeids = $this->blacklist->get_blacklist();
        foreach ($badgeids as $badgeid) {
            $mform->setDefault('blacklist['.$badgeid.']', 1);
        }
    }

}
