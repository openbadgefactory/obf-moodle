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
 * User preference tests.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../class/user_preferences.php');

/**
 * User preferences testcase.
 *
 * @group obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_obf_user_preferences_testcase extends advanced_testcase {
    /**
     * Test user preferences.
     */
    public function test_user_preferences() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $userprefs = new obf_user_preferences($user->id);
        $newprefs = new stdClass();
        $newprefs->badgesonprofile = 0;
        $userprefs->save_preferences($newprefs);
        $this->assertFalse($userprefs->get_preference('badgesonprofile') == 1);
        $userprefs->set_preference('badgesonprofile', 1);
        $this->assertTrue($userprefs->get_preference('badgesonprofile') == 1);
        $userprefs->set_preference('badgesonprofile', 0);
        $this->assertFalse($userprefs->get_preference('badgesonprofile') == 1);

    }
}
