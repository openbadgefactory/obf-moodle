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
 * @package    local_obf
 * @copyright  2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$definitions = array(
    'obf_assertions' => array( // Note that obf_assertions refers to obf_assertion class, and this cache may contain Mozilla Backpack badges as well.
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => (24 * 60 * 60), // TODO: Remove ttl? -- It's recommended to use event driven cache invalidation.
        'invalidationevents' => array('new_obf_assertion', 'obf_blacklist_changed')
    ),
    'obf_assertions_moz' => array( // Mozilla backpack badges.
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => (24 * 60 * 60), // TODO: Remove ttl? -- It's recommended to use event driven cache invalidation.
        'invalidationevents' => array('new_obf_assertion')
    ),
    'obf_assertions_obp' => array( // Open Badge Passport backpack badges.
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => (24 * 60 * 60), // TODO: Remove ttl? -- It's recommended to use event driven cache invalidation.
        'invalidationevents' => array('new_obf_assertion')
    )
);
