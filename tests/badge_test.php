<?php

require_once __DIR__ . '/../class/badge.php';
require_once __DIR__ . '/../class/client.php';

/**
 * @group obf
 */
class local_obf_badge_testcase extends advanced_testcase {

    private $client_mock = null;
    private $existingid = 'MTMLIQDDZ2';
    private $nonexistingid = 'MTMLIQDDZ3';
    private $badgeclassname = null;
    
    public function setUp() {
        $this->resetAfterTest();
        
        $existingbadgedata = array(
            'criteria' => 'https://localhost/obf/c/criteria/' . $this->existingid . '.html',
            'description' => 'Test description',
            'expires' => '36',
            'id' => $this->existingid,
            'draft' => '0',
            'tags' => array('foo', 'bar'),
            'image' => '',
            'ctime' => '1380697491',
            'name' => 'Test Badge'
        );

        $badgeclassname = $this->getMockClass('obf_badge', array('get_badge_from_tree'));
        $badgeclassname::staticExpects($this->any())->method('get_badge_from_tree')
                ->will($this->returnValue(false));
        $this->badgeclassname = $badgeclassname;
        
        $this->client_mock = $this->getMock('obf_client', array('get_badge'));
        $this->client_mock->expects($this->any())
                ->method('get_badge')
                ->with($this->equalTo($this->existingid))
                ->will($this->returnValue($existingbadgedata));
    }
    
    public function test_get_empty_instance() {
        $this->setExpectedException('Exception', 'Invalid or missing badge id');
        $badgeclassname = $this->badgeclassname;
        $badge = $badgeclassname::get_instance();
        $badge->issue(array(), null, null, null, null);
    }
    
    public function test_get_existing_instance() {
        $badgeclassname = $this->badgeclassname;
        $badge = $badgeclassname::get_instance($this->existingid, $this->client_mock);
        
        $this->assertEquals('Test description', $badge->get_description());
    }

}

?>
