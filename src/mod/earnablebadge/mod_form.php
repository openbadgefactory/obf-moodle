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
 * earnablebadge configuration form
 *
 * @package    mod_earnablebadge
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @copyright  2017 Discendum Oy {@link http://www.discendum.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/earnablebadge/locallib.php');

class mod_earnablebadge_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB, $PAGE, $OUTPUT;
        $mform = $this->_form;

        $config = get_config('earnablebadge');

        $mform->addElement('header', 'pickearnable', get_string('pickearnableheader', 'mod_earnablebadge'));
        require_once($CFG->dirroot . '/local/obf/class/client.php');
        require_once($CFG->dirroot . '/local/obf/class/earnable_badge.php');
        $earnables = obf_earnable_badge::get_earnable_badges();
        //var_dump($earnables);
        /* @var $obfrenderer local_obf_renderer */
        $obfrenderer = $PAGE->get_renderer('local_obf');
        $items = array();
        foreach ($earnables as $earnable) {
          $html = $OUTPUT->box(local_obf_html::div($obfrenderer->print_earnable_badge($earnable, false) ), 'generalbox obfearnablebadgebox');
          $items[] = $mform->createElement('radio', 'earnable',
                  '', $html, $earnable->get_id());
        }
        if (count($items) > 0) {
            $mform->addGroup($items, 'earnable', '', array(' '), false);
        }
        if (!empty($this->current->externalearnablebadge)) {
          $mform->setDefault('earnable', $this->current->externalearnablebadge);
        }


        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        //-------------------------------------------------------
        //$mform->addElement('header', 'content', get_string('contentheader', 'earnablebadge'));
        $mform->addElement('hidden', 'externalearnablebadge');
        $mform->setType('externalearnablebadge', PARAM_RAW_TRIMMED);
        //$mform->addRule('externalearnablebadge', null, 'required', null, 'client');
        //$mform->setExpanded('content');



        //-------------------------------------------------------
        $mform->addElement('header', 'optionssection', get_string('appearance'));

        $options = array(RESOURCELIB_DISPLAY_AUTO     => get_string('resourcedisplayauto'));
        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'earnablebadge'), $options);
            $mform->setDefault('display', $config->display);
            $mform->addHelpButton('display', 'displayselect', 'earnablebadge');
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'earnablebadge'), array('size'=>3));
            if (count($options) > 1) {
                $mform->disabledIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'earnablebadge'), array('size'=>3));
            if (count($options) > 1) {
                $mform->disabledIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_AUTO, $options) or
          array_key_exists(RESOURCELIB_DISPLAY_EMBED, $options) or
          array_key_exists(RESOURCELIB_DISPLAY_FRAME, $options)) {
            $mform->addElement('checkbox', 'printintro', get_string('printintro', 'earnablebadge'));
            $mform->disabledIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_POPUP);
            $mform->disabledIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_OPEN);
            $mform->disabledIf('printintro', 'display', 'eq', RESOURCELIB_DISPLAY_NEW);
            $mform->setDefault('printintro', $config->printintro);
        }



        // JS to handle picking the earnable

        $PAGE->requires->jquery();
        $module = array('name' => 'mod_earnablebadge', 'fullpath' => '/mod/earnablebadge/module.js');
        $PAGE->requires->js_init_call('M.mod_earnablebadge.init_view', null, false, $module);

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        if (!empty($default_values['displayoptions'])) {
            $displayoptions = unserialize($default_values['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $default_values['printintro'] = $displayoptions['printintro'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $default_values['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $default_values['popupheight'] = $displayoptions['popupheight'];
            }
        }
        if (!empty($default_values['parameters'])) {
            $parameters = unserialize($default_values['parameters']);
            $i = 0;
            foreach ($parameters as $parameter=>$variable) {
                $default_values['parameter_'.$i] = $parameter;
                $default_values['variable_'.$i]  = $variable;
                $i++;
            }
        }
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validating Entered earnablebadge, we are looking for obvious problems only,
        // teachers are responsible for testing if it actually works.

        // This is not a security validation!! Teachers are allowed to enter "javascript:alert(666)" for example.

        // NOTE: do not try to explain the difference between URL and URI, people would be only confused...

        /*if (!empty($data['externalearnablebadge'])) {
            $earnablebadge = $data['externalearnablebadge'];
            if (preg_match('|^/|', $earnablebadge)) {
                // links relative to server root are ok - no validation necessary

            } else if (preg_match('|^[a-z]+://|i', $earnablebadge) or preg_match('|^https?:|i', $earnablebadge) or preg_match('|^ftp:|i', $earnablebadge)) {
                // normal earnablebadge
                if (!earnablebadge_appears_valid_earnablebadge($earnablebadge)) {
                    $errors['externalearnablebadge'] = get_string('invalidearnablebadge', 'earnablebadge');
                }

            } else if (preg_match('|^[a-z]+:|i', $earnablebadge)) {
                // general URI such as teamspeak, mailto, etc. - it may or may not work in all browsers,
                // we do not validate these at all, sorry

            } else {
                // invalid URI, we try to fix it by adding 'http://' prefix,
                // relative links are NOT allowed because we display the link on different pages!
                if (!earnablebadge_appears_valid_earnablebadge('http://'.$earnablebadge)) {
                    $errors['externalearnablebadge'] = get_string('invalidearnablebadge', 'earnablebadge');
                }
            }
        }*/
        return $errors;
    }

}
