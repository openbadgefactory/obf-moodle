<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Badge issuer.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Badge issuer -class.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_issuer {

    /**
     * @var string The id of the issuer.
     */
    private $id = null;

    /**
     * @var string The name of the issuer.
     */
    private $name = '';

    /**
     * @var string Issuer description.
     */
    private $description = '';

    /**
     * @var string Issuer email address.
     */
    private $email = '';

    /**
     * @var string The URL of the issuer.
     */
    private $url = '';

    /**
     * @var string The organization name of the issuer.
     */
    private $organization = '';
    
    private static $defaultissuer = null;

    /**
     * Returns a new obf_issuer instance.
     *
     * @return \self
     */
    public static function get_instance() {
        return new self;
    }

    /**
     * Returns a new obf_issuer instance created from an array.
     *
     * @param array $arr An array with the issuer data.
     * @return obf_issuer The issuer instance.
     */
    public static function get_instance_from_arr($arr) {
        return self::get_instance()->populate_from_array($arr);
    }
    
    /**
     * Returns a new obf_issuer instance created from a moodle badge stdClass object.
     *
     * @param stdClass $moodle_badge An stdClass with the issuer data.
     * @return obf_issuer The issuer instance.
     */
    public static function get_instance_from_moodle_badge($moodle_badge) {
        return self::get_instance()->populate_from_moodle_badge($moodle_badge);
    }

    /**
     * Returns a new obf_issuer instance created from an array fetched from
     * the Mozilla Backpack.
     *
     * @param stdClass $obj The issuer data.
     * @return \self The issuer instance.
     */
    public static function get_instance_from_backpack_data(stdClass $obj) {
        $issuer = new self();
        $issuer->set_name($obj->name);
        $issuer->set_url($obj->origin);

        if (isset($obj->contact)) {
            $issuer->set_email($obj->contact);
        }
        if (isset($obj->email)) {
            $issuer->set_email($obj->email);
        }

        if (isset($obj->org)) {
            $issuer->set_organization($obj->org);
        }

        return $issuer;
    }
    
    public static function get_default_issuer($client) {
        if (!is_null(self::$defaultissuer)) {
            return self::$defaultissuer;
        }
        self::$defaultissuer = obf_issuer::get_instance_from_arr($client->get_issuer());
        return self::$defaultissuer;
    }

    
    /**
     * Populates this instance with the data from the array.
     *
     * @param array $arr The issuer data.
     * @return obf_issuer Returns this instance.
     */
    public function populate_from_array($arr) {
        $this->set_id($arr['id'])->set_description($arr['description']);
        $this->set_email($arr['email'])->set_url($arr['url'])->set_name($arr['name']);
        return $this;
    }
    
    /**
     * Populates this instance with the data from the moodle badge stdClass.
     *
     * @param stdClass $moodle_badge The issuer data.
     * @return obf_issuer Returns this instance.
     */
    public function populate_from_moodle_badge($moodle_badge) {
        $this->set_email($moodle_badge->issuercontact)->set_url($moodle_badge->issuerurl)->set_name($moodle_badge->issuername);
        return $this;
    }

    /**
     * Converts this issuer to an array.
     *
     * @return array This issuer's properties as an array.
     */
    public function toArray() {
        return array(
            'id' => $this->get_id(),
            'description' => $this->get_description(),
            'email' => $this->get_email(),
            'url' => $this->get_url(),
            'name' => $this->get_name()
        );
    }

    /**
     * Get id
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Set id.
     * @param int $id
     * @return $this
     */
    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Get name.
     * @return string Name
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Set name.
     * @param string $name
     * @return $this
     */
    public function set_name($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get description.
     * @return string Description
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Set Description.
     * @param string $description
     */
    public function set_description($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Get email.
     * @return string Email
     */
    public function get_email() {
        return $this->email;
    }

    /**
     * Set email.
     * @param string $email
     */
    public function set_email($email) {
        $this->email = $email;
        return $this;
    }

    /**
     * Get URL.
     * @return string
     */
    public function get_url() {
        return $this->url;
    }

    /**
     * Set URL.
     * @param string $url
     */
    public function set_url($url) {
        $this->url = $url;
        return $this;
    }

    /**
     * Get organization.
     * @return mixed Organization
     */
    public function get_organization() {
        return $this->organization;
    }

    /**
     * Set organization.
     * @param mixed $organization
     */
    public function set_organization($organization) {
        $this->organization = $organization;
        return $this;
    }

}
