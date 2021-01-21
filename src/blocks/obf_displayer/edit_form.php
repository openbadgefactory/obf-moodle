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
 * @package    block_obf_displayer
 * @copyright  2020, Open Badge Factory Oy Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_obf_displayer_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot . '/local/obf/class/backpack.php');

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('advcheckbox', 'config_largebadges', get_string('largebadges', 'block_obf_displayer'));
        
        $mform->addElement('advcheckbox', 'config_disableassertioncache', get_string('disableassertioncache', 'block_obf_displayer'));
        $mform->setDefault('config_largebadges', 0);
        $mform->setDefault('config_disableassertioncache', 0);
        
        // Logged in user or profile user
        $instance = $this->block->instance;
        $parentcontext = context::instance_by_id($instance->parentcontextid);
        $displaytypes = array();
        $isusercontext = $parentcontext->contextlevel == CONTEXT_USER ||
            (property_exists($instance, 'pagetypepattern') && $instance->pagetypepattern == 'user-profile');
        if ($isusercontext) {
            $displaytypes[] = $mform->createElement('radio', 'config_displaytype', '', get_string('displayloggedinuser', 'block_obf_displayer'), 'loggedinuser');
            $displaytypes[] = $mform->createElement('radio', 'config_displaytype', '', get_string('displaycontextuser', 'block_obf_displayer'), 'contextuser');
        } else {
            $displaytypes[] = $mform->createElement('radio', 'config_displaytype', '', get_string('displayloggedinuser', 'block_obf_displayer'), 'loggedinuser');
            $displaytypes[] = $mform->createElement('radio', 'config_displaytype', '', get_string('displaycontextuser', 'block_obf_displayer'), 'contextuser', array('disabled' => true));
        }
        $mform->addGroup($displaytypes, 'config_displaytype_array', get_string('displaytypegrouplabel', 'block_obf_displayer'), array(' '), false);
        $mform->addHelpButton('config_displaytype_array', 'displaytypegrouplabel', 'block_obf_displayer');
       // $mform->setDefault('config_displaytype', $isusercontext ? 'contextuser' : 'loggedinuser');
        $mform->setDefault('config_displaytype', 'loggedinuser');

        $mform->addElement('header', 'config_providers_header', get_string('providerselect', 'block_obf_displayer'));

        $connectionstatus = obf_client::get_instance()->test_connection();
        if ($connectionstatus >= 0) {
            $mform->addElement('html', $OUTPUT->notification(get_string('apierror' . $connectionstatus,
                                    'local_obf'), 'warning'));
        }

        $mform->addElement('advcheckbox', 'config_showobf', get_string('showobf', 'block_obf_displayer'));
        $mform->setDefault('config_showobf', 1);
        
        $providers = obf_backpack::get_providers();
        foreach ($providers as $provider) {
            $shortname = obf_backpack::get_providershortname_by_providerid($provider);
            $fullname = obf_backpack::get_providerfullname_by_providerid($provider);
            $mform->addElement('advcheckbox', 'config_show'.$shortname, get_string('showpbackpacksource', 'block_obf_displayer', $fullname));
            $mform->setDefault('config_show'.$shortname, 0);
        }
        
        $mform->addElement('advcheckbox', 'config_showmoodle', get_string('showmoodle', 'block_obf_displayer'));
        $mform->setDefault('config_showmoodle', 1);

        $this->setExpanded($mform, 'config_providers_header', true);
    }
    protected function setExpanded(&$mform, $header, $expanded) {
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded($header, $expanded);
        }
    }
}
