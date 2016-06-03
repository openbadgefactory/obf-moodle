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
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$handlers = array(
    'course_completed' => array(
        'handlerfile' => '/local/obf/lib.php',
        'handlerfunction' => 'local_obf_course_completed',
        'schedule' => 'instant',
        'internal' => 1
    ),
    'activity_completion_changed' => array(
        'handlerfile' => '/local/obf/lib.php',
        'handlerfunction' => 'local_obf_activity_completion_changed',
        'schedule' => 'instant',
        'internal' => 1
    ),
    'course_deleted' => array(
        'handlerfile' => '/local/obf/lib.php',
        'handlerfunction' => 'local_obf_course_deleted',
        'schedule' => 'instant',
        'internal' => 1
    )
);
