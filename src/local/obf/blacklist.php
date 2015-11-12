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
 * Page for blacklisting individual badges from being displayed on the profile page.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/blacklist.php');
require_once(__DIR__ . '/form/blacklist.php');
require_once(__DIR__ . '/class/user_preferences.php');

$error = optional_param('error', '', PARAM_TEXT);
$msg = optional_param('msg', '', PARAM_TEXT);
$action = optional_param('action', 'edit', PARAM_TEXT);
$context = context_system::instance();

require_login();
require_capability('local/obf:configureuser', $context);
$url = new moodle_url('/local/obf/blacklist.php', array('action' => $action));

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$content = $OUTPUT->header();
$obfuserpreferences = new obf_user_preferences($USER->id);
$formurl = new moodle_url('/local/obf/blacklist.php', array('action' => 'update'));
$form = new obf_blacklist_form($formurl,
        array('user' => $USER,
              'blacklist' => new obf_blacklist($USER->id)));

switch ($action) {
    case 'edit':
        if (!empty($msg)) {
            $content .= $OUTPUT->notification($msg, 'notifysuccess');
        }
        $content .= $PAGE->get_renderer('local_obf')->render_blacklistconfig($form, $error);
        break;
    case 'addbadge':
        $badgeid = required_param('badgeid', PARAM_ALPHANUM);
        require_sesskey();
        $blacklist = new obf_blacklist($USER->id);
        $blacklist->add_to_blacklist($badgeid);
        $blacklist->save();
        $redirecturl = $url;
        $redirecturl->param('msg', get_string('blacklistsaved', 'local_obf'));
        $redirecturl->param('action', 'edit');
        cache_helper::invalidate_by_event('obf_blacklist_changed', array($USER->id));

        redirect($redirecturl);
        break;
    case 'update':
        if ($data = $form->get_data()) {
            $newblacklist = property_exists($data, 'blacklist') ? array_keys(array_filter($data->blacklist)) : array();
            $blacklist = new obf_blacklist($USER->id);
            $blacklist->save($newblacklist);
            cache_helper::invalidate_by_event('obf_blacklist_changed', array($USER->id));

            $redirecturl = $url;
            $redirecturl->param('msg', get_string('blacklistsaved', 'local_obf'));
            $redirecturl->param('action', 'edit');
            redirect($redirecturl);
        }
        break;
}

$content .= $OUTPUT->footer();
echo $content;
