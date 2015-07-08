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
 * Issuer tests.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../class/issuer.php');

/**
 * Issuer testcase.
 *
 * @group obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_obf_issuer_testcase extends advanced_testcase {
    /**
     * Test issuer population from array.
     */
    public function test_population_from_array() {
        $data = array(
            'id' => 'testissuer',
            'description' => 'Issuer description',
            'email' => 'issuer@example.com',
            'url' => 'http://example.com/',
            'name' => 'Test Issuer');
        $issuer = obf_issuer::get_instance_from_arr($data);

        $this->assertInstanceOf('obf_issuer', $issuer);
        $this->assertEquals('testissuer', $issuer->get_id());
        $this->assertEquals('Issuer description', $issuer->get_description());
        $this->assertEquals('issuer@example.com', $issuer->get_email());
        $this->assertEquals('http://example.com/', $issuer->get_url());
        $this->assertEquals('Test Issuer', $issuer->get_name());
    }
}
