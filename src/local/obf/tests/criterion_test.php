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
 * Criterion tests.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../class/criterion/criterion.php');
require_once(__DIR__ . '/../class/badge.php');
require_once(__DIR__ . '/../class/criterion/activity.php');

/**
 * Criterion testcase.
 *
 * @group obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_obf_criterion_testcase extends advanced_testcase {
    /**
     * Test creation of criterion.
     */
    public function test_create() {
        $this->resetAfterTest(true);

        $badge = new obf_badge();
        $badge->set_id('TESTBADGE');

        $rule = new obf_criterion();
        $rule->set_completion_method(obf_criterion::CRITERIA_COMPLETION_ALL);
        $rule->set_badge($badge);

        $this->assertFalse($rule->exists());

        $rule->save();

        $this->assertTrue($rule->exists());
        $this->assertFalse($rule->is_met());

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $courserule1 = new obf_criterion_course();
        $courserule1->set_courseid($course1->id);
        $courserule1->set_criterionid($rule->get_id());
        $courserule1->set_completedby(strtotime('+6 months'));

        $courserule2 = new obf_criterion_course();
        $courserule2->set_courseid($course2->id);
        $courserule2->set_grade(5);
        $courserule2->set_criterionid($rule->get_id());

        $this->assertFalse($courserule1->exists());

        $courserule1->save();
        $courserule2->save();

        // Test, whether the course rules were saved.
        $this->assertCount(2, $rule->get_items());
        $this->assertTrue($courserule1->exists());
        $this->assertTrue($courserule2->exists());
        $this->assertEquals($courserule1->get_criterion()->get_badgeid(), $rule->get_badgeid());
        $this->assertEquals($course1->fullname, $courserule1->get_coursename());

        // Test completion data validity.
        $this->assertTrue($courserule1->has_completion_date());
        $this->assertFalse($courserule1->has_grade());
        $this->assertTrue($courserule2->has_grade());

        // Test related courses.
        $relatedcourses = $rule->get_related_courses();
        $this->assertArrayHasKey($course1->id, $relatedcourses);
        $this->assertArrayHasKey($course2->id, $relatedcourses);
        $this->assertTrue($rule->has_course($course1->id));
        $this->assertTrue($rule->has_course($course2->id));

        // Test if rule has been met.
        $user1 = $this->getDataGenerator()->create_user();
        $this->assertFalse($rule->is_met_by_user($user1));
        $rule->set_met_by_user($user1->id);
        $this->assertTrue($rule->is_met_by_user($user1));
        $this->assertTrue($rule->is_met());

        // Test course-related rules.
        $coursecriterion = obf_criterion::get_course_criterion($course1->id);
        $this->assertCount(1, $coursecriterion);
        $this->assertArrayHasKey($rule->get_id(), $coursecriterion);
        $this->assertCount(2, $coursecriterion[$rule->get_id()]->get_items());

        // Delete course rules.
        $rule->delete_items();
        $this->assertCount(0, $rule->get_items());
    }
    
    /**
     * @group profile
     */
    public function test_profile_criterion() {
        require_once(__DIR__ . '/../class/event.php');
        require_once(__DIR__ .'/lib/obf_mock_curl.php');
        $this->resetAfterTest();
        $curl = obf_mock_curl::get_mock_curl($this);
        set_config('obfclientid', 'PHPUNIT', 'local_obf');
        $client = obf_client::get_instance($curl);
        $client->set_transport($curl);
        $user = $this->getDataGenerator()->create_user();
        
        
        
        $badge = new obf_badge();
        $badge->set_image(obf_mock_curl::$emptypngdata);
        $badge->set_id('TESTBADGE');
        obf_mock_curl::add_get_badge($this, $curl, 'PHPUNIT', $badge);
        $criterion = new obf_criterion();
        $criterion->set_badge($badge);
        $criterion->set_badgeid($badge->get_id());
        $criterion->set_completion_method(obf_criterion::CRITERIA_COMPLETION_ALL);
        
        $criterion->save();
        $rule = obf_criterion_item::build(array(
            'criteriatype' => obf_criterion_item::CRITERIA_TYPE_PROFILE,
            'criterionid' => $criterion->get_id()
                ));
        $rule->save();
        $params = array('field_phone1' => 'phone1', 'field_city' => 'city');
        $rule->save_params($params);
        
        $criterion2 = new obf_criterion();
        $criterion2->set_badge($badge);
        $criterion2->set_badgeid($badge->get_id());
        $criterion2->set_completion_method(obf_criterion::CRITERIA_COMPLETION_ANY);
        
        $criterion2->save();
        $rule2 = obf_criterion_item::build(array(
            'criteriatype' => obf_criterion_item::CRITERIA_TYPE_PROFILE,
            'criterionid' => $criterion2->get_id()
                ));
        $rule2->save();
        $params = array('field_phone1' => 'phone1', 'field_city' => 'city');
        $rule2->save_params($params);
        
        obf_mock_curl::add_issue_badge($this, $curl, 'PHPUNIT');
        $criterionevents = obf_issue_event::get_criterion_events($criterion);
        $this->assertCount(0, $criterionevents);
        
        $criterionevents = obf_issue_event::get_criterion_events($criterion2); // Any
        $this->assertCount(0, $criterionevents);
        
        $user->phone1 = '0401234567';
        user_update_user($user, false, true);
        
        $criterionevents = obf_issue_event::get_criterion_events($criterion);
        $this->assertCount(0, $criterionevents, 'All aggregation fired event');
        
        $criterionevents = obf_issue_event::get_criterion_events($criterion2); // Any
        $this->assertCount(1, $criterionevents, 'Any aggregation did not fire event');
        
        $user->city = 'Oulu';
        user_update_user($user, false, true);
        
        $criterionevents = obf_issue_event::get_criterion_events($criterion);
        $this->assertCount(1, $criterionevents, 'All aggregation did not fire event after all criteria met');

    }
}
