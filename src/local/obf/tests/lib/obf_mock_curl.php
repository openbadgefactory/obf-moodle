<?php

/*
 * Copyright (c) 2017 Discendum Oy

 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.

 */

/**
 * Description of obf_mock_curl
 *
 * @author jsuorsa
 */
class obf_mock_curl {
    public static $emptypngdata = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAEACAIAAADTED8xAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4QYCCiE56ohHkQAAABl0RVh0Q29tbWVudABDcmVhdGVkIHdpdGggR0lNUFeBDhcAAAH7SURBVHja7dNBDQAACMQwwL/n440GWglL1kkKvhoJMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAyAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwAAYAA4ABwABgADAAGAAMAAYAA4ABwABgADAAGAAMAAYAA4ABwABgADAAGAAMAAYAA4ABwABgADAAGAAMAAYAA4ABwABgADAAGAAMAAYAA4ABwABgADAAGAAMAAYAA4ABMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABgADgAHAAGAAMAAYAAwABsAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAcAAYAAwABgADAAGAAOAAeBaq+gE/QpErIgAAAAASUVORK5CYII=';
    //put your code here
    public static function get_mock_curl($self) {
        // Create the mock object.
        $curl = $self->getMock('curl', array('post', 'get', 'delete'));

        // Mock HTTP POST.
        $curl->info = array('http_code' => 200);
        return $curl;
    }
    public static function add_client_test_methods($self, &$curl) {
        $curl->expects($self->once())->method(
                'post')->with($self->stringEndsWith('/test/'), $self->anything(),
                        $self->anything())->will(
                                $self->returnValue(json_encode(array('post' => 'works!'))));

        // Mock HTTP GET.
        $curl->expects($self->any())->method('get')->with($self->logicalOr(
                                $self->stringEndsWith('/test/'),
                                $self->stringEndsWith('/doesnotexist/')),
                        $self->anything(), $self->anything())->will($self->returnCallback(
                        function ($path, $arg1, $arg2) {
                            // This url exists, return a success message.
                            if ($path == "/test/") {
                                return json_encode(array('get' => 'works!'));
                            }

                            return false; // Return false on failure (invalid url).
                        }));

        // Mock HTTP DELETE.
        $curl->expects($self->once())->method('delete')->with($self->stringEndsWith('/test/'), $self->anything(),
                        $self->anything())->will($self->returnValue(json_encode(array('delete' => 'works!'))));
    }
    public static function add_get_badge($self, &$curl, $clientid, $badge) {
        $curl->expects($self->once())->method(
                'get')->with($self->stringEndsWith('/badge/'.$clientid.'/'.$badge->get_id()), $self->anything(),
                        $self->anything())->will(
                                $self->returnValue(json_encode(
                                        array('id' => $badge->get_id(), 'badge_id' => $badge->get_id(), 'description' => $badge->get_description(), 'image' => $badge->get_image(), 'name' => $badge->get_name()))
                                        ));
    }
    public static function add_issue_badge($self, &$curl, $clientid) {
        // Mock HTTP POST.
        $curl->info = array('http_code' => 200);
        $curl->expects($self->any())->method(
                'post')->with(
                        //$self->stringContains('/badge/'.$clientid.'/'), 
                        $self->anything(),
                        $self->anything(),
                        $self->anything())->will(
                                $self->returnValue(json_encode(array('post' => 'works!'))));
        $curl->rawresponse = array('Location: https://localhost.localdomain/v1/event/PHPUNIT/PHPUNITEVENTID');
        /*$curl->expects($self->any())
                ->method('get_raw_response')
                ->will(
                        $self->returnValue(
                                array('Location: https://localhost.localdomain/PHPUNIT/event/PHPUNITEVENTID/')
                        ));*/
        
    }
}
