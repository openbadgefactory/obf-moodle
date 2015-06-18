<?php
require_once(__DIR__ . '/../class/user_preferences.php');

/**
 * @group obf
 */
class local_obf_user_preferences_testcase extends advanced_testcase {

    public function test_user_preferences() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $userprefs = new obf_user_preferences($user->id);
        $newprefs = new stdClass();
        $newprefs->badgesonprofile = 0;
        $userprefs->save_preferences($newprefs);
        $this->assertFalse($userprefs->get_preference('badgesonprofile') == 1);
        $userprefs->set_preference('badgesonprofile',1);
        $this->assertTrue($userprefs->get_preference('badgesonprofile') == 1);
        $userprefs->set_preference('badgesonprofile', 0);
        $this->assertFalse($userprefs->get_preference('badgesonprofile') == 1);

    }
}
