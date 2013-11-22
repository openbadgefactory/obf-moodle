<?php

class obf_issuer {

    private $id = null;
    private $name = '';
    private $description = '';
    private $email = '';
    private $url = '';
    private $organization = '';

    public static function get_instance() {
        return new self;
    }

    /**
     *
     * @param type $arr
     * @return obf_issuer
     */
    public static function get_instance_from_arr($arr) {
        return self::get_instance()->populate_from_array($arr);
    }

    public static function get_instance_from_backpack_data(stdClass $obj) {
        $issuer = new self();

        if ($obj->contact) {
            $issuer->set_email($obj->contact);
        }

        $issuer->set_name($obj->name);
        $issuer->set_url($obj->origin);

        if ($obj->org) {
            $issuer->set_organization($obj->org);
        }

        return $issuer;
    }

    public function get_organization() {
        return $this->organization;
    }

    public function set_organization($organization) {
        $this->organization = $organization;
        return $this;
    }

        /**
     *
     * @param type $arr
     * @return obf_issuer
     */
    public function populate_from_array($arr) {
        return $this->set_id($arr['id'])
                        ->set_description($arr['description'])
                        ->set_email($arr['email'])
                        ->set_url($arr['url'])
                        ->set_name($arr['name']);
    }

    public function toArray() {
        return array(
            'id' => $this->get_id(),
            'description' => $this->get_description(),
            'email' => $this->get_email(),
            'url' => $this->get_url(),
            'name' => $this->get_name()
        );
    }

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function get_name() {
        return $this->name;
    }

    public function set_name($name) {
        $this->name = $name;
        return $this;
    }

    public function get_description() {
        return $this->description;
    }

    public function set_description($description) {
        $this->description = $description;
        return $this;
    }

    public function get_email() {
        return $this->email;
    }

    public function set_email($email) {
        $this->email = $email;
        return $this;
    }

    public function get_url() {
        return $this->url;
    }

    public function set_url($url) {
        $this->url = $url;
        return $this;
    }

}