<?php

require_once __DIR__ . '/../class/folder.php';
require_once __DIR__ . '/../class/badge.php';

/**
 * @group obf
 */
class local_obf_folder_testcase extends advanced_testcase {

    private $badge = null;

    protected function setUp() {
        $this->badge = new obf_badge();
        $this->badge->set_created(time());
        $this->badge->set_description('Test description');
        $this->badge->set_id('MTPXU3O8W2');
        $this->badge->set_name('US OPEN');
    }

    public function test_name2() {
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
