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
 * Email template form.
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/obfform.php');
require_once(__DIR__ . '/../class/backpack.php');
/**
 * Email template form -class.
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obf_backpack_config extends local_obf_form_base {
    /**
     * @var obf_badge $badge The badge the template is for.
     */
    private $badge = null;

    /**
     * Defines forms elements
     */
    protected function definition() {
        global $OUTPUT;
        $mform = $this->_form;
        $backpack = $this->_customdata['backpack'];

        $mform->addElement('header', 'header_backpackconfig',
                get_string('personalbadgecloudservices', 'local_obf'));

        $backpackuserids = !empty($backpack->id) ? obf_backpack::get_user_ids_with_backpack($backpack->id) : array();

        if (!empty($backpackuserids)) {
            $mform->addElement('html',
                    $OUTPUT->notification(get_string('backpackprovideruserwarning',
                                    'local_obf', count($backpackuserids)), 'warning'));
        }


        $mform->addElement('text', 'shortname', get_string('backpackprovidershortname', 'local_obf'));
        $mform->setType('shortname', PARAM_ALPHA);
        $mform->addElement('text', 'fullname', get_string('backpackproviderfullname', 'local_obf'));
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addElement('text', 'url', get_string('backpackproviderurl', 'local_obf'));
        $mform->setType('url', PARAM_URL);
        $mform->addElement('advcheckbox', 'configureableaddress', get_string('backpackprovideremailconfigureable', 'local_obf'));
        $mform->setType('configureableaddress', PARAM_TEXT);

        if (!empty($backpack->id)) {
            $mform->addElement('submit', 'deletebutton', get_string("delete"), array('class' => 'delete'));
        }

        $this->add_action_buttons();
    }

    // Perform some extra validation
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (array_key_exists('url', $data)) {
            $urlparts = parse_url($data['url']);

            if (!isset($urlparts['scheme']) || !isset($urlparts['path'])) {
                $errors['url'] = get_string('backpackproviderurlinvalid', 'local_obf');
            } else {
                try {
                    $reachable = obf_backpack::test_api_url($data['url']);
                } catch (\Exception $ex) {
                    $errors['url'] = $ex->getMessage();
                }
            }
        }
        return $errors;
    }
}
