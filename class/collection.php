<?php
require_once(__DIR__ . '/badge.php');
require_once(__DIR__ . '/client.php');

class obf_badge_collection {

    private $badges = array();

    public function get_badge($badgeid) {
        if (!isset($this->badges[$badgeid])) {
            $this->populate();
        }

        return isset($this->badges[$badgeid]) ? $this->badges[$badgeid] : null;
    }

    private function populate() {
        $badges = obf_client::get_instance()->get_badges();

        foreach ($badges as $badge) {
            $this->badges[$badge['id']] = obf_badge::get_instance_from_array($badge);
        }
    }

}