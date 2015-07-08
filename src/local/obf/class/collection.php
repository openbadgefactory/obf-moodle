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
 * A collection of badges.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/badge.php');
require_once(__DIR__ . '/client.php');

/**
 * Class for storing the badges fetched from Open Badge Factory.
 *
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_badge_collection {

    /**
     * @var obf_badge[] The badges in this collection.
     */
    private $badges = array();

    /**
     * @var obf_client The client communicating with OBF.
     */
    private $client = null;

    /**
     * Initalizes the collection.
     *
     * @param obf_client $client The client communicating with OBF
     */
    public function __construct(obf_client $client) {
        $this->client = $client;
    }

    /**
     * Returns a single badge from this collection.
     *
     * @param string $badgeid The id of the badge.
     * @return obf_badge Returns the badge if it exists in this collection and
     *      null otherwise.
     */
    public function get_badge($badgeid) {
        return isset($this->badges[$badgeid]) ? $this->badges[$badgeid] : null;
    }

    /**
     * Populates this collection by fetching the badges from the OBF.
     */
    public function populate() {
        $badges = $this->client->get_badges();

        foreach ($badges as $badge) {
            $this->badges[$badge['id']] = obf_badge::get_instance_from_array($badge);
        }
    }

}
