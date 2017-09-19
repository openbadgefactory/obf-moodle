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
 * Strings for component 'earnablebadge', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    mod_earnablebadge
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @copyright  2017 Discendum Oy {@link http://www.discendum.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['clicktoopen'] = 'Click {$a} link to open resource.';
$string['configdisplayoptions'] = 'Select all options that should be available, existing settings are not modified. Hold CTRL key to select multiple fields.';
$string['configframesize'] = 'When a web page or an uploaded file is displayed within a frame, this value is the height (in pixels) of the top frame (which contains the navigation).';
$string['configrolesinparams'] = 'Enable if you want to include localized role names in list of available parameter variables.';
$string['configsecretphrase'] = 'This secret phrase is used to produce encrypted code value that can be sent to some servers as a parameter.  The encrypted code is produced by an md5 value of the current user IP address concatenated with your secret phrase. ie code = md5(IP.secretphrase). Please note that this is not reliable because IP address may change and is often shared by different computers.';
$string['contentheader'] = 'Content';
$string['createearnablebadge'] = 'Create a earnable badge';
$string['displayoptions'] = 'Available display options';
$string['displayselect'] = 'Display';
$string['displayselect_help'] = 'This setting, together with the earnable badge file type and whether the browser allows embedding, determines how the earnable badge is displayed. Options may include:

* Automatic - The best display option for the earnable badge is selected automatically
* Embed - The earnable badge is displayed within the page below the navigation bar together with the earnable badge description and any blocks
* Open - Only the earnable badge is displayed in the browser window
* In pop-up - The earnable badge is displayed in a new browser window without menus or an address bar
* In frame - The earnable badge is displayed within a frame below the navigation bar and earnable badge description
* New window - The earnable badge is displayed in a new browser window with menus and an address bar';
$string['displayselectexplain'] = 'Choose display type, unfortunately not all types are suitable for all earnable badges.';
$string['externalearnablebadge'] = 'External earnable badge';
$string['framesize'] = 'Frame height';
$string['invalidstoredearnablebadge'] = 'Cannot display this resource, earnable badge is invalid.';
$string['chooseavariable'] = 'Choose a variable...';
$string['invalidearnablebadge'] = 'Entered earnable badge is invalid';
$string['modulename'] = 'earnable badge';
$string['modulename_help'] = 'The earnable badge module enables a teacher to provide a web link as a course resource. Anything that is freely available online, such as documents or images, can be linked to; the earnable badge doesn’t have to be the home page of a website. The earnable badge of a particular web page may be copied and pasted or a teacher can use the file picker and choose a link from a repository such as Flickr, YouTube or Wikimedia (depending upon which repositories are enabled for the site).

There are a number of display options for the earnable badge, such as embedded or opening in a new window and advanced options for passing information, such as a student\'s name, to the earnable badge if required.

Note that earnable badges can also be added to any other resource or activity type through the text editor.';
$string['modulename_link'] = 'mod/earnablebadge/view';
$string['modulenameplural'] = 'earnable badges';
$string['page-mod-earnablebadge-x'] = 'Any earnable badge module page';
$string['parameterinfo'] = '&amp;parameter=variable';
$string['parametersheader'] = 'earnable badge variables';
$string['parametersheader_help'] = 'Some internal Totara variables may be automatically appended to the earnable badge. Type your name for the parameter into each text box(es) and then select the required matching variable.';
$string['pluginadministration'] = 'earnable badge module administration';
$string['pluginname'] = 'earnable badge';
$string['pickearnableheader'] = 'Earnable';
$string['popupheight'] = 'Pop-up height (in pixels)';
$string['popupheightexplain'] = 'Specifies default height of popup windows.';
$string['popupwidth'] = 'Pop-up width (in pixels)';
$string['popupwidthexplain'] = 'Specifies default width of popup windows.';
$string['printintro'] = 'Display earnable badge description';
$string['printintroexplain'] = 'Display earnable badge description below content? Some display types may not display description even if enabled.';
$string['rolesinparams'] = 'Include role names in parameters';
$string['serverearnablebadge'] = 'Server earnable badge';
$string['earnablebadge:addinstance'] = 'Add a new earnable badge resource';
$string['earnablebadge:view'] = 'View earnable badge';
