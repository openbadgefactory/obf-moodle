<?php

/**
 * Description of assertion_collection_test
 *
 * @author olli
 */
class local_obf_assertion_collection_testcase extends advanced_testcase {

    public function test_collection() {
        $description = 'Description';
        $issuer = obf_issuer::get_instance()->set_name('Issuer');

        $badge1 = obf_badge::get_instance()->set_image('image1')->set_name('name1')->set_description($description)->set_issuer($issuer);
        $badge2 = obf_badge::get_instance()->set_image('image2')->set_name('name2')->set_description($description)->set_issuer($issuer);
        $badge3 = obf_badge::get_instance()->set_image('image3')->set_name('name3')->set_description($description)->set_issuer($issuer);
        $badge4 = obf_badge::get_instance()->set_image('image4')->set_name('name4')->set_description($description)->set_issuer($issuer);

        $assertion1 = obf_assertion::get_instance()->set_badge($badge1);
        $assertion2 = obf_assertion::get_instance()->set_badge($badge2);
        $assertion3 = obf_assertion::get_instance()->set_badge($badge3);
        $assertion4 = obf_assertion::get_instance()->set_badge($badge4);

        $assertions = array($assertion1, $assertion2);
        $collection = new obf_assertion_collection($assertions);
        $collection2 = new obf_assertion_collection(array($assertion1, $assertion4));

        $this->assertEquals(2, $collection->count());

        $collection->add_assertion($assertion3);

        $this->assertEquals(3, $collection->count());
        $this->assertCount(3, $collection->toArray());

        $collection->add_collection($collection2);

        $this->assertEquals(4, $collection->count());
        $this->assertTrue($collection->has_assertion($assertion4));
        $this->assertFalse($collection2->has_assertion($assertion2));
    }

}
