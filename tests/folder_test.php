<?php

require_once __DIR__ . '/../class/folder.php';
require_once __DIR__ . '/../class/badge.php';

/**
 * @group obf
 */
class local_obf_folder_testcase extends advanced_testcase {

    private $badge = null;

    protected function setUp() {
        $this->resetAfterTest();
        $this->badge = $this->getMock('obf_badge');
    }

    public function test_name() {
        $folder = new obf_badge_folder(null);
        $this->assertFalse($folder->has_name());
    }

    public function test_badgecount() {
        $folder = new obf_badge_folder('Test folder');
        $badge2 = clone $this->badge;
        $folder->add_badge($this->badge);
        $folder->add_badge($badge2);
        $this->assertEquals(2, count($folder->get_badges()));
    }
}

?>
