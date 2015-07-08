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
 * Assertion collection test.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Description of assertion_collection_test
 *
 * @author olli
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_obf_assertion_collection_testcase extends advanced_testcase {
    /**
     * Test assertion collection.
     */
    public function test_collection() {
        $description = 'Description';
        $issuer = obf_issuer::get_instance()->set_name('Issuer');

        $badge1 = obf_badge::get_instance()->set_image('image1')->set_name('name1');
        $badge1->set_description($description)->set_issuer($issuer);
        $badge2 = obf_badge::get_instance()->set_image('image2')->set_name('name2');
        $badge2->set_description($description)->set_issuer($issuer);
        $badge3 = obf_badge::get_instance()->set_image('image3')->set_name('name3');
        $badge3->set_description($description)->set_issuer($issuer);
        $badge4 = obf_badge::get_instance()->set_image('image4')->set_name('name4');
        $badge4->set_description($description)->set_issuer($issuer);

        $assertion1 = obf_assertion::get_instance()->set_badge($badge1);
        $assertion2 = obf_assertion::get_instance()->set_badge($badge2);
        $assertion3 = obf_assertion::get_instance()->set_badge($badge3);
        $assertion4 = obf_assertion::get_instance()->set_badge($badge4);

        $assertions = array($assertion1, $assertion2);
        $collection = new obf_assertion_collection($assertions);
        $collection2 = new obf_assertion_collection(array($assertion1, $assertion4));

        $this->assertEquals(2, $collection->count());

        $collection->add_assertion($assertion3);

        $this->assertEquals(3, $collection->count());
        $this->assertCount(3, $collection->toArray());

        $collection->add_collection($collection2);

        $this->assertEquals(4, $collection->count());
        $this->assertTrue($collection->has_assertion($assertion4));
        $this->assertFalse($collection2->has_assertion($assertion2));
    }

}
