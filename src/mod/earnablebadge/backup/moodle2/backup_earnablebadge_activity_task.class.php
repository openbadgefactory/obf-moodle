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
 * Defines backup_earnablebadge_activity_task class
 *
 * @package     mod_earnablebadge
 * @category    backup
 * @copyright   2010 onwards Andrew Davis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/earnablebadge/backup/moodle2/backup_earnablebadge_stepslib.php');

/**
 * Provides all the settings and steps to perform one complete backup of the activity
 */
class backup_earnablebadge_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the earnablebadge.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_earnablebadge_activity_structure_step('earnablebadge_structure', 'earnablebadge.xml'));
    }

    /**
     * Encodes earnablebadges to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains earnablebadges to the activity instance scripts
     * @return string the content with the earnablebadges encoded
     */
    static public function encode_content_links($content, backup_task $task = null) {

        if (!self::has_scripts_in_content($content, 'mod/earnablebadge', ['index.php', 'view.php'])) {
            // No scripts present in the content, simply continue.
            return $content;
        }

        if (empty($task)) {
            // No task has been provided, lets just encode everything, must be some old school backup code.
            $content = self::encode_content_link_basic_id($content, "/mod/earnablebadge/index.php?id=", 'earnablebadgeINDEX');
            $content = self::encode_content_link_basic_id($content, "/mod/earnablebadge/view.php?id=", 'earnablebadgeVIEWBYID');
            $content = self::encode_content_link_basic_id($content, "/mod/earnablebadge/view.php?u=", 'earnablebadgeVIEWBYU');
        } else {
            // OK we have a valid task, we can translate just those links belonging to content that is being backed up.
            $content = self::encode_content_link_basic_id($content, "/mod/earnablebadge/index.php?id=", 'earnablebadgeINDEX', $task->get_courseid());
            foreach ($task->get_tasks_of_type_in_plan('backup_earnablebadge_activity_task') as $task) {
                /** @var backup_earnablebadge_activity_task $task */
                $content = self::encode_content_link_basic_id($content, "/mod/earnablebadge/view.php?id=", 'earnablebadgeVIEWBYID', $task->get_moduleid());
                $content = self::encode_content_link_basic_id($content, "/mod/earnablebadge/view.php?u=", 'earnablebadgeVIEWBYU', $task->get_activityid());
            }
        }

        return $content;
    }
}
