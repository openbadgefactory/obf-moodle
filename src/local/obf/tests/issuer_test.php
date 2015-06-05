<?php
defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../class/issuer.php';

/**
 * @group obf
 */
class local_obf_issuer_testcase extends advanced_testcase {
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
