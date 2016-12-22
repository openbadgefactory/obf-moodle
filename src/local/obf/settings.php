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
 * The settings page and settings navigation definitions.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/class/client.php');

$hasobfconfig = $hassiteconfig || has_capability('local/obf:configure', context_system::instance());
if ($hasobfconfig) {
    // OBF-category in site admin.
    $obf = new admin_category('obf', get_string('obf', 'local_obf'));

    // OBF-settings.
    $settings = new admin_externalpage('obfconfig', get_string('settings', 'local_obf'),
            new moodle_url('/local/obf/config.php'), 'local/obf:configure');
    
    // Backpack-settings.
    $backpacksettings = new admin_externalpage('personalbadgecloudservices', get_string('personalbadgecloudservices', 'local_obf'),
            new moodle_url('/local/obf/backpackconfig.php'), 'local/obf:configure');

    // Add pages to navigation.
    $ADMIN->add('root', $obf, 'location');
    $ADMIN->add('obf', $settings);
    $ADMIN->add('obf', $backpacksettings);

    // Badge list -page.
    $badgelist = new admin_externalpage('badgelist', get_string('badgelist', 'local_obf'),
            new moodle_url('/local/obf/badge.php', array('action' => 'list')), 'local/obf:viewallbadges');

    // Awarding history -page.
    $history = new admin_externalpage('badgehistory', get_string('history', 'local_obf'),
            new moodle_url('/local/obf/badge.php', array('action' => 'history')), 'local/obf:viewhistory');

    // Category settings page.
    $client = obf_client::get_instance();

    // Badge export settings.
    $export = new admin_externalpage('obfexportbadges', get_string('exportsettings', 'local_obf'),
                    new moodle_url('/local/obf/config.php', array('action' => 'exportbadges')), 'local/obf:configure');
    $ADMIN->add('obf', $export);

    $ADMIN->add('obf', $badgelist);
    $ADMIN->add('obf', $history);
}
