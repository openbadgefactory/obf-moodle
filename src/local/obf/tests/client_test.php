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
 * Client tests.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * OBF Client testcase.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_obf_client_testcase extends advanced_testcase {
    /**
     * Test API request.
     */
    public function test_request() {
        $this->resetAfterTest();

        // Create the mock object.
        $curl = $this->getMock('curl', array('post', 'get', 'delete'));

        // Mock HTTP POST.
        $curl->expects($this->once())->method(
                'post')->with($this->stringEndsWith('/test/'), $this->anything(),
                        $this->anything())->will(
                                $this->returnValue(json_encode(array('post' => 'works!'))));

        // Mock HTTP GET.
        $curl->expects($this->any())->method('get')->with($this->logicalOr(
                                $this->stringEndsWith('/test/'),
                                $this->stringEndsWith('/doesnotexist/')),
                        $this->anything(), $this->anything())->will($this->returnCallback(
                        function ($path, $arg1, $arg2) {
                            // This url exists, return a success message.
                            if ($path == "/test/") {
                                return json_encode(array('get' => 'works!'));
                            }

                            return false; // Return false on failure (invalid url).
                        }));

        // Mock HTTP DELETE.
        $curl->expects($this->once())->method('delete')->with($this->stringEndsWith('/test/'), $this->anything(),
                        $this->anything())->will($this->returnValue(json_encode(array('delete' => 'works!'))));

        $client = obf_client::get_instance($curl);

        // Test HTTP POST.
        $response = $client->request('/test/', 'post');
        $this->assertArrayHasKey('post', $response);

        // Test HTTP GET.
        $response = $client->request('/test/');
        $this->assertArrayHasKey('get', $response);

        // Test HTTP DELETE.
        $response = $client->request('/test/', 'delete');
        $this->assertArrayHasKey('delete', $response);

        // Test preformatter.
        $response = $client->request('/test/', 'get', array(),
                function () {
                    return json_encode(array('preformatted' => 'i am!'));
                });
        $this->assertArrayHasKey('preformatted', $response);

        // Test invalid url.
        $curl->info = array('http_code' => 404);

        try {
            $client->request('/doesnotexist/');
            $this->fail('An expected exception has not been raised.');
        } catch (Exception $e) {
            // We should end up here.
            0 + 0; // Suppressing PHP_CodeSniffer error messages.
        }
    }
    /**
     * Test Deauthentication.
     */
    public function test_deauthentication() {
        $this->resetAfterTest();

        $client = obf_client::get_instance();
        $certfile = $client->get_cert_filename();
        $pkeyfile = $client->get_pkey_filename();

        $pkidir = $client->get_pki_dir();

        if (!is_dir($pkidir)) {
            global $CFG;
            mkdir($pkidir, $CFG->directorypermissions, true);
        }

        if (!file_exists($certfile)) {
            touch($certfile);
        }

        if (!file_exists($pkeyfile)) {
            touch($pkeyfile);
        }

        set_config('obfclientid', 'test', 'local_obf');

        $client->deauthenticate();

        $this->assertFileNotExists($certfile);
        $this->assertFileNotExists($pkeyfile);
        $this->assertFalse(get_config('local_obf', 'obfclientid'));
    }

    /**
     * Test missing clinet id.
     */
    public function test_missing_client_id() {
        $this->resetAfterTest();

        set_config('obfclientid', 'test', 'local_obf');

        $client = obf_client::get_instance();

        try {
            $client->require_client_id();
        } catch (Exception $ex) {
            $this->fail('Client id required but not found.');
        }

        unset_config('obfclientid', 'local_obf');

        try {
            $client->require_client_id();
            $this->fail('Missing client id should throw an exception.');
        } catch (Exception $ex) {
            // We should end up here.
            0 + 0; // Suppressing PHP_CodeSniffer error messages.
        }
    }
}
