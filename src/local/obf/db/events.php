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
 * See https://docs.moodle.org/dev/Events_API for details.
 *
 * @package    local_obf
 * @copyright  2013-2016, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$handlers = array(
    /* Deprecated in 2.7 */
);

$observers = array(
    array(
        'eventname'   => '\core\event\course_completed',
        'callback'    => 'local_obf_observer::course_completed',
    ),
    array(
        'eventname'   => '\core\event\course_module_completion_updated',
        'callback'    => 'local_obf_observer::course_module_completion_updated',
    ),
    array(
        'eventname'   => '\core\event\course_deleted',
        'callback'    => 'local_obf_observer::course_deleted',
    ),
    array(
        'eventname'   => 'core\event\user_updated',
        'callback'    => 'local_obf_observer::profile_criteria_review',
    ),
    array(
        'eventname'   => '\core\event\course_reset_started',
        'callback'    => 'local_obf_observer::course_reset_start'
    )
);