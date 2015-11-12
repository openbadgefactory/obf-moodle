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
 * Manage criteria associated to deleted badges.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/form/criteriondeletion.php');
require_once(__DIR__ . '/class/client.php');
require_once(__DIR__ . '/class/criterion/criterion.php');

$context = context_system::instance();
$url = new moodle_url('/local/obf/deleted.php');
$msg = optional_param('msg', '', PARAM_TEXT);
$action = optional_param('action', 'list', PARAM_TEXT);
$client = obf_client::get_instance();

require_login();
require_capability('local/obf:configure', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$content = '';

switch ($action) {
    // List criteria that needs to be deleted.
    case 'list':

        $criteria = obf_criterion::get_criteria_with_deleted_badges();
        foreach ($criteria as $criterion) {
            $deleteurl = new moodle_url('/local/obf/criterion.php',
            array('badgeid' => $criterion->get_badgeid(),
                    'action' => 'delete', 'id' => $criterion->get_id()));
            $deleteform = new obf_criterion_deletion_form($deleteurl, array('criterion' => $criterion));
            $content .= html_writer::tag('strong', $criterion->get_badgeid());
            $content .= $deleteform->render();
        }

        break;
}
echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();
