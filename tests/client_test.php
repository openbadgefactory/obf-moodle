<?php

/**
 */
class local_obf_client_testcase extends advanced_testcase {

    public function test_request() {

        // Create the mock object.
        $curl = $this->getMock('curl', array('post', 'get', 'delete'));

        // Mock HTTP POST
        $curl->expects($this->once())
                ->method('post')
                ->with($this->stringEndsWith('/test/'), $this->anything(),
                        $this->anything())
                ->will($this->returnValue(json_encode(array('post' => 'works!'))));

        // Mock HTTP GET
        $curl->expects($this->any())
                ->method('get')
                ->with($this->logicalOr(
                        $this->stringEndsWith('/test/'),
                        $this->stringEndsWith('/doesnotexist/')),
                        $this->anything(), $this->anything())
                ->will($this->returnCallback(function ($path, $arg1, $arg2) {
                    // This url exists, return a success message.
                    if ($path == "/test/") {
                        return json_encode(array('get' => 'works!'));
                    }
                    
                    return false; // Return false on failure (invalid url).
                }));

        // Mock HTTP DELETE
        $curl->expects($this->once())
                ->method('delete')
                ->with($this->stringEndsWith('/test/'), $this->anything(),
                        $this->anything())
                ->will($this->returnValue(json_encode(array('delete' => 'works!'))));

        $client = obf_client::get_instance($curl);

        // Test HTTP POST
        $response = $client->request('/test/', 'post');
        $this->assertArrayHasKey('post', $response);

        // Test HTTP GET
        $response = $client->request('/test/');
        $this->assertArrayHasKey('get', $response);

        // Test HTTP DELETE
        $response = $client->request('/test/', 'delete');
        $this->assertArrayHasKey('delete', $response);

        // Test preformatter
        $response = $client->request('/test/', 'get', array(),
                function () {
            return json_encode(array('preformatted' => 'i am!'));
        });
        $this->assertArrayHasKey('preformatted', $response);

        // Test invalid url
        $curl->info = array('http_code' => 404);

        try {
            $client->request('/doesnotexist/'); 
            $this->fail('An expected exception has not been raised.');
        }
        catch (Exception $e) {
            // We should end up here.
        }
        
    }
    
}
