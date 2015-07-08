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
 * Blacklist tests.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../class/blacklist.php');

/**
 * Blacklist testcase
 *
 * @group obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_obf_blacklist_testcase extends advanced_testcase {
    /**
     * Test blacklist saving, adding and removing.
     */
    public function test_blacklist_preferences() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $blacklist = new obf_blacklist($user->id);
        $newbl = new stdClass();
        $newbl = array('ASF', 'DFG');
        $this->assertCount(0, $blacklist->get_blacklist());
        $blacklist->save($newbl);
        $this->assertCount(2, $blacklist->get_blacklist());
        $blacklist->add_to_blacklist('NEW1');
        $this->assertCount(3, $blacklist->get_blacklist());
        $blacklist->remove_from_blacklist('NEW1');
        $this->assertCount(2, $blacklist->get_blacklist());

    }
}
