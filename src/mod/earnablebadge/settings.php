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
 * earnablebadge module admin settings and defaults
 *
 * @package    mod_earnablebadge
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_AUTO,
                                                           RESOURCELIB_DISPLAY_EMBED,
                                                           RESOURCELIB_DISPLAY_FRAME,
                                                           RESOURCELIB_DISPLAY_OPEN,
                                                           RESOURCELIB_DISPLAY_NEW,
                                                           RESOURCELIB_DISPLAY_POPUP,
                                                          ));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('earnablebadge/framesize',
        get_string('framesize', 'earnablebadge'), get_string('configframesize', 'earnablebadge'), 130, PARAM_INT));
    $settings->add(new admin_setting_configpasswordunmask('earnablebadge/secretphrase', get_string('password'),
        get_string('configsecretphrase', 'earnablebadge'), ''));
    $settings->add(new admin_setting_configcheckbox('earnablebadge/rolesinparams',
        get_string('rolesinparams', 'earnablebadge'), get_string('configrolesinparams', 'earnablebadge'), false));
    $settings->add(new admin_setting_configmultiselect('earnablebadge/displayoptions',
        get_string('displayoptions', 'earnablebadge'), get_string('configdisplayoptions', 'earnablebadge'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('earnablebadgemodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('earnablebadge/printintro',
        get_string('printintro', 'earnablebadge'), get_string('printintroexplain', 'earnablebadge'), 1));
    $settings->add(new admin_setting_configselect('earnablebadge/display',
        get_string('displayselect', 'earnablebadge'), get_string('displayselectexplain', 'earnablebadge'), RESOURCELIB_DISPLAY_AUTO, $displayoptions));
    $settings->add(new admin_setting_configtext('earnablebadge/popupwidth',
        get_string('popupwidth', 'earnablebadge'), get_string('popupwidthexplain', 'earnablebadge'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('earnablebadge/popupheight',
        get_string('popupheight', 'earnablebadge'), get_string('popupheightexplain', 'earnablebadge'), 450, PARAM_INT, 7));
}
