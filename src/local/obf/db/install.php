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
 * OBF Install script. See https://docs.moodle.org/dev/Upgrade_API for details.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Install function. Makes sure path for certificates exists.
 *
 * @return boolean
 **/
function xmldb_local_obf_install() {
    global $CFG, $DB;
    $newpkidir = $CFG->dataroot . '/local_obf/pki/';

    if (!is_dir($newpkidir)) {
        mkdir($newpkidir, $CFG->directorypermissions, true);
    }

    // Set default backpack sources
    $backpacksources = array();

    $obj = new stdClass();
    
    /*
    $obj->url = 'https://backpack.openbadges.org/displayer/';
    $obj->fullname = 'Backpack';
    $obj->shortname = 'moz';
    $obj->configureableaddress = 1;
    $backpacksources[] = clone($obj);
     */
    $obj->url = 'https://openbadgepassport.com/displayer/';
    $obj->fullname = 'Open Badge Passport';
    $obj->shortname = 'obp';
    $obj->configureableaddress = 1;
    $backpacksources[] = clone($obj);
    $newids = array();
    foreach($backpacksources as $key => $backpacksource) {
        $newids[$obj->shortname] = $DB->insert_record('local_obf_backpack_sources', $backpacksource);
    }


    return true;
}
