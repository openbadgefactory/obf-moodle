<?php
require_once(__DIR__ . '/../class/blacklist.php');

/**
 * @group obf
 */
class local_obf_blacklist_testcase extends advanced_testcase {

    public function test_blacklist_preferences() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $blacklist = new obf_blacklist($user->id);
        $newbl = new stdClass();
        $newbl = array('ASF', 'DFG');
        $this->assertCount(0,$blacklist->get_blacklist());
        $blacklist->save($newbl);
        $this->assertCount(2,$blacklist->get_blacklist());
        $blacklist->add_to_blacklist('NEW1');
        $this->assertCount(3,$blacklist->get_blacklist());
        $blacklist->remove_from_blacklist('NEW1');
        $this->assertCount(2,$blacklist->get_blacklist());

    }
}
