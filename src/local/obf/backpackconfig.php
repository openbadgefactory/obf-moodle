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
 * Plugin configuration page.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/class/backpack.php');
require_once(__DIR__ . '/form/backpackconfig.php');

$context = context_system::instance();
$msg = optional_param('msg', '', PARAM_TEXT);
$action = optional_param('action', 'list', PARAM_TEXT);
$providerid = optional_param('id', 0, PARAM_NUMBER);
$urlparams = $action == 'list' ? array() : array('action' => $action);
$url = new moodle_url('/local/obf/backpackconfig.php', $urlparams);

require_login();
require_capability('local/obf:configure', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$content = '';
$backpacks = array();
if (!empty($msg)) {
    $content .= $OUTPUT->notification($msg);
}
foreach (obf_backpack::get_providers() as $provider) {
    $existing = obf_backpack::get_instance($USER, $provider);
    $backpacks[] = $existing ? $existing : new obf_backpack(null, $provider);
}

function local_obf_backpackconfig_update_backpack_from_form($form, $backpack, &$content) {
    if (!$form->is_cancelled()) {
        if ($data = $form->get_data()) {
            if (!empty($data->deletebutton)) {
                obf_backpack::delete_provider_record($backpack);
                if (obf_backpack::save_provider_record($backpack)) {
                    $redirecturl = new moodle_url('/local/obf/backpackconfig.php');
                    redirect($redirecturl);
                }
            } else {
                $backpack = (object) array_merge((array) $backpack, (array) $data);
                if (obf_backpack::save_provider_record($backpack)) {
                    $redirecturl = new moodle_url('/local/obf/backpackconfig.php');
                    redirect($redirecturl);
                }
            }
        } else {
            $form->set_data($backpack);
        }
        $content .= $form->render();
    } else {
        $redirecturl = new moodle_url('/local/obf/backpackconfig.php');
        redirect($redirecturl);
    }
}

switch ($action) {
    case 'list':
        $content .= $PAGE->get_renderer('local_obf')->print_heading('personalbadgecloudservices');
        $content .= $PAGE->get_renderer('local_obf')->render_backpack_provider_list($backpacks);
        break;
    case 'create':
        $backpack = new stdClass();
        $formurl = new moodle_url('/local/obf/backpackconfig.php', array('action' => 'create'));
        $form = new obf_backpack_config($formurl, array('backpack' => $backpack));
        local_obf_backpackconfig_update_backpack_from_form($form, $backpack, $content);
        break;
    case 'edit':
        $backpack = obf_backpack::get_provider_record($providerid);
        $formurl = new moodle_url('/local/obf/backpackconfig.php', array('action' => 'edit', 'id' => $providerid));
        $form = new obf_backpack_config($formurl, array('backpack' => $backpack));
        local_obf_backpackconfig_update_backpack_from_form($form, $backpack, $content);
        $providername = isset($backpack->fullname) ? $backpack->fullname : '';
        $params = array(array('class' => 'delete',
                    'question' => get_string('confirmdelete', 'local_obf', $providername)));
        $PAGE->requires->yui_module('moodle-local_obf-submitconfirm',
                'M.local_obf.init_submitconfirm', $params);
        break;
}

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();