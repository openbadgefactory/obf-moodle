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
 * Private earnablebadge module utility functions
 *
 * @package    mod_earnablebadge
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @copyright  2017 Discendum Oy {@link http://www.discendum.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/earnablebadge/lib.php");

/**
 * This methods does weak earnablebadge validation, we are looking for major problems only,
 * no strict RFE validation.
 *
 * @param $earnablebadge
 * @return bool true is seems valid, false if definitely not valid earnablebadge
 */
function earnablebadge_appears_valid_earnablebadge($earnablebadge) {
    return true;
}

/**
 * Fix common earnablebadge problems that we want teachers to see fixed
 * the next time they edit the resource.
 *
 * This function does not include any XSS protection.
 *
 * @param string $earnablebadge
 * @return string
 */
function earnablebadge_fix_submitted_earnablebadge($earnablebadge) {
    // note: empty earnablebadges are prevented in form validation
    $earnablebadge = trim($earnablebadge);

    // remove encoded entities - we want the raw URI here
    $earnablebadge = html_entity_decode($earnablebadge, ENT_QUOTES, 'UTF-8');

   return $earnablebadge;
}

/**
 * Return full earnablebadge with all extra parameters
 *
 * This function does not include any XSS protection.
 *
 * @param string $earnablebadge
 * @param object $cm
 * @param object $course
 * @param object $config
 * @return string earnablebadge with & encoded as &amp;
 */
function earnablebadge_get_full_earnablebadge($earnablebadge, $cm, $course, $config=null) {

    $parameters = empty($earnablebadge->parameters) ? array() : unserialize($earnablebadge->parameters);

    // make sure there are no encoded entities, it is ok to do this twice
    $fullearnablebadge = html_entity_decode($earnablebadge->externalearnablebadge, ENT_QUOTES, 'UTF-8');

    // add variable earnablebadge parameters
    if (!empty($parameters)) {
        if (!$config) {
            $config = get_config('earnablebadge');
        }
        $paramvalues = earnablebadge_get_variable_values($earnablebadge, $cm, $course, $config);

        foreach ($parameters as $parse=>$parameter) {
            if (isset($paramvalues[$parameter])) {
                $parameters[$parse] = rawurlencode($parse).'='.rawurlencode($paramvalues[$parameter]);
            } else {
                unset($parameters[$parse]);
            }
        }

        if (!empty($parameters)) {
            if (stripos($fullearnablebadge, 'teamspeak://') === 0) {
                $fullearnablebadge = $fullearnablebadge.'?'.implode('?', $parameters);
            } else {
                $join = (strpos($fullearnablebadge, '?') === false) ? '?' : '&';
                $fullearnablebadge = $fullearnablebadge.$join.implode('&', $parameters);
            }
        }
    }

    // encode all & to &amp; entity
    $fullearnablebadge = str_replace('&', '&amp;', $fullearnablebadge);

    return $fullearnablebadge;
}

/**
 * Unicode encoding helper callback
 * @internal
 * @param array $matches
 * @return string
 */
function earnablebadge_filter_callback($matches) {
    return rawurlencode($matches[0]);
}

/**
 * Print earnablebadge header.
 * @param object $earnablebadge
 * @param object $cm
 * @param object $course
 * @return void
 */
function earnablebadge_print_header($earnablebadge, $cm, $course) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname.': '.$earnablebadge->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($earnablebadge);
    echo $OUTPUT->header();
}

/**
 * Print earnablebadge heading.
 * @param object $earnablebadge
 * @param object $cm
 * @param object $course
 * @param bool $notused This variable is no longer used.
 * @return void
 */
function earnablebadge_print_heading($earnablebadge, $cm, $course, $notused = false) {
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($earnablebadge->name), 2);
}

/**
 * Print earnablebadge introduction.
 * @param object $earnablebadge
 * @param object $cm
 * @param object $course
 * @param bool $ignoresettings print even if not specified in modedit
 * @return void
 */
function earnablebadge_print_intro($earnablebadge, $cm, $course, $ignoresettings=false) {
    global $OUTPUT;

    $options = empty($earnablebadge->displayoptions) ? array() : unserialize($earnablebadge->displayoptions);
    if ($ignoresettings or !empty($options['printintro'])) {
        if (trim(strip_tags($earnablebadge->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'earnablebadgeintro');
            echo format_module_intro('earnablebadge', $earnablebadge, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Display earnablebadge frames.
 * @param object $earnablebadge
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function earnablebadge_display_frame($earnablebadge, $cm, $course) {
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        earnablebadge_print_header($earnablebadge, $cm, $course);
        earnablebadge_print_heading($earnablebadge, $cm, $course);
        earnablebadge_print_intro($earnablebadge, $cm, $course);
        echo $OUTPUT->footer();
        die;

    } else {
        $config = get_config('earnablebadge');
        $context = context_module::instance($cm->id);
        $exteearnablebadge = earnablebadge_get_full_earnablebadge($earnablebadge, $cm, $course, $config);
        $navearnablebadge = "$CFG->wwwroot/mod/earnablebadge/view.php?id=$cm->id&amp;frameset=top";
        $coursecontext = context_course::instance($course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
        $title = strip_tags($courseshortname.': '.format_string($earnablebadge->name));
        $framesize = $config->framesize;
        $modulename = s(get_string('modulename','earnablebadge'));
        $contentframetitle = s(format_string($earnablebadge->name));
        $dir = get_string('thisdirection', 'langconfig');

        $extframe = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navearnablebadge" title="$modulename"/>
    <frame src="$exteearnablebadge" title="$contentframetitle"/>
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $extframe;
        die;
    }
}

/**
 * Print earnablebadge info and link.
 * @param object $earnablebadge
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function earnablebadge_print_workaround($earnablebadge, $cm, $course) {
    global $OUTPUT, $PAGE, $CFG;
    $PAGE->requires->jquery();

    earnablebadge_print_header($earnablebadge, $cm, $course);
    earnablebadge_print_heading($earnablebadge, $cm, $course, true);
    earnablebadge_print_intro($earnablebadge, $cm, $course, true);

    $fullearnablebadge = earnablebadge_get_full_earnablebadge($earnablebadge, $cm, $course);

    $display = earnablebadge_get_final_display_type($earnablebadge);
    if ($display == RESOURCELIB_DISPLAY_POPUP) {
        $jsfullearnablebadge = addslashes_js($fullearnablebadge);
        $options = empty($earnablebadge->displayoptions) ? array() : unserialize($earnablebadge->displayoptions);
        $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $extra = "onclick=\"window.open('$jsfullearnablebadge', '', '$wh'); return false;\"";

    } else if ($display == RESOURCELIB_DISPLAY_NEW) {
        $extra = "onclick=\"this.target='_blank';\"";

    } else {
        $extra = '';
    }

    echo '<div class="earnablebadgeworkaround">';
    //print_string('clicktoopen', 'earnablebadge', "<a href=\"$fullearnablebadge\" $extra>$fullearnablebadge</a>");
    /* @var $obfrenderer local_obf_renderer */
    $obfrenderer = $PAGE->get_renderer('local_obf');
    require_once($CFG->dirroot . '/local/obf/class/earnable_badge.php');
    //var_dump($earnablebadge->externalearnablebadge);
    $earnable = obf_earnable_badge::get_instance($earnablebadge->externalearnablebadge);
    echo $obfrenderer->print_earnable_badge_form($earnable);
    echo '</div>';

    $module = array('name' => 'mod_earnablebadge', 'fullpath' => '/mod/earnablebadge/module.js');
    $PAGE->requires->js_init_call('M.mod_earnablebadge.init_view_bootstrap_form', null, false, $module);

    echo $OUTPUT->footer();
    die;
}

/**
 * Display embedded earnablebadge file.
 * @param object $earnablebadge
 * @param object $cm
 * @param object $course
 * @return does not return
 */
function earnablebadge_display_embed($earnablebadge, $cm, $course) {
    global $CFG, $PAGE, $OUTPUT;

    $mimetype = resourcelib_guess_earnablebadge_mimetype($earnablebadge->externalearnablebadge);
    $fullearnablebadge  = earnablebadge_get_full_earnablebadge($earnablebadge, $cm, $course);
    $title    = $earnablebadge->name;

    $link = html_writer::tag('a', $fullearnablebadge, array('href'=>str_replace('&amp;', '&', $fullearnablebadge)));
    $clicktoopen = get_string('clicktoopen', 'earnablebadge', $link);
    $moodleurl = new moodle_url($fullearnablebadge);

    $extension = resourcelib_get_extension($earnablebadge->externalearnablebadge);

    $mediarenderer = $PAGE->get_renderer('core', 'media');
    $embedoptions = array(
        core_media::OPTION_TRUSTED => true,
        core_media::OPTION_BLOCK => true
    );

    if (in_array($mimetype, array('image/gif','image/jpeg','image/png'))) {  // It's an image
        $code = resourcelib_embed_image($fullearnablebadge, $title);

    } else if ($mediarenderer->can_embed_earnablebadge($moodleurl, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediarenderer->embed_earnablebadge($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($fullearnablebadge, $title, $clicktoopen, $mimetype);
    }

    earnablebadge_print_header($earnablebadge, $cm, $course);
    earnablebadge_print_heading($earnablebadge, $cm, $course);

    echo $code;

    earnablebadge_print_intro($earnablebadge, $cm, $course);

    echo $OUTPUT->footer();
    die;
}

/**
 * Decide the best display format.
 * @param object $earnablebadge
 * @return int display type constant
 */
function earnablebadge_get_final_display_type($earnablebadge) {
    global $CFG;

    if ($earnablebadge->display != RESOURCELIB_DISPLAY_AUTO) {
        return $earnablebadge->display;
    }

    static $download = array('application/zip', 'application/x-tar', 'application/g-zip',     // binary formats
                             'application/pdf', 'text/html');  // these are known to cause trouble for external links, sorry
    static $embed    = array('image/gif', 'image/jpeg', 'image/png', 'image/svg+xml',         // images
                             'application/x-shockwave-flash', 'video/x-flv', 'video/x-ms-wm', // video formats
                             'video/quicktime', 'video/mpeg', 'video/mp4',
                             'audio/mp3', 'audio/x-realaudio-plugin', 'x-realaudio-plugin',   // audio formats,
                            );

    $mimetype = 'text/html';

    if (in_array($mimetype, $download)) {
        return RESOURCELIB_DISPLAY_DOWNLOAD;
    }
    if (in_array($mimetype, $embed)) {
        return RESOURCELIB_DISPLAY_EMBED;
    }

    // let the browser deal with it somehow
    return RESOURCELIB_DISPLAY_OPEN;
}

/**
 * Get the parameters that may be appended to earnablebadge
 * @param object $config earnablebadge module config options
 * @return array array describing opt groups
 */
function earnablebadge_get_variable_options($config) {
    global $CFG;

    $options = array();
    $options[''] = array('' => get_string('chooseavariable', 'earnablebadge'));

    $options[get_string('course')] = array(
        'courseid'        => 'id',
        'coursefullname'  => get_string('fullnamecourse'),
        'courseshortname' => get_string('shortnamecourse'),
        'courseidnumber'  => get_string('idnumbercourse'),
        'coursesummary'   => get_string('summary'),
        'courseformat'    => get_string('format'),
    );

    $options[get_string('modulename', 'earnablebadge')] = array(
        'earnablebadgeinstance'     => 'id',
        'earnablebadgecmid'         => 'cmid',
        'earnablebadgename'         => get_string('name'),
        'earnablebadgeidnumber'     => get_string('idnumbermod'),
    );

    $options[get_string('miscellaneous')] = array(
        'sitename'        => get_string('fullsitename'),
        'serverearnablebadge'       => get_string('serverearnablebadge', 'earnablebadge'),
        'currenttime'     => get_string('time'),
        'lang'            => get_string('language'),
    );
    if (!empty($config->secretphrase)) {
        $options[get_string('miscellaneous')]['encryptedcode'] = get_string('encryptedcode');
    }

    $options[get_string('user')] = array(
        'userid'          => 'id',
        'userusername'    => get_string('username'),
        'useridnumber'    => get_string('idnumber'),
        'userfirstname'   => get_string('firstname'),
        'userlastname'    => get_string('lastname'),
        'userfullname'    => get_string('fullnameuser'),
        'useremail'       => get_string('email'),
        'usericq'         => get_string('icqnumber'),
        'userphone1'      => get_string('phone'),
        'userphone2'      => get_string('phone2'),
        'userinstitution' => get_string('institution'),
        'userdepartment'  => get_string('department'),
        'useraddress'     => get_string('address'),
        'usercity'        => get_string('city'),
        'usertimezone'    => get_string('timezone'),
        'userearnablebadge'         => get_string('webpage'),
    );

    if ($config->rolesinparams) {
        $roles = role_fix_names(get_all_roles());
        $roleoptions = array();
        foreach ($roles as $role) {
            $roleoptions['course'.$role->shortname] = get_string('yourwordforx', '', $role->localname);
        }
        $options[get_string('roles')] = $roleoptions;
    }

    return $options;
}

/**
 * Get the parameter values that may be appended to earnablebadge
 * @param object $earnablebadge module instance
 * @param object $cm
 * @param object $course
 * @param object $config module config options
 * @return array of parameter values
 */
function earnablebadge_get_variable_values($earnablebadge, $cm, $course, $config) {
    global $USER, $CFG;

    $site = get_site();

    $coursecontext = context_course::instance($course->id);

    $values = array (
        'courseid'        => $course->id,
        'coursefullname'  => format_string($course->fullname),
        'courseshortname' => format_string($course->shortname, true, array('context' => $coursecontext)),
        'courseidnumber'  => $course->idnumber,
        'coursesummary'   => $course->summary,
        'courseformat'    => $course->format,
        'lang'            => current_language(),
        'sitename'        => format_string($site->fullname),
        'serverearnablebadge'       => $CFG->wwwroot,
        'currenttime'     => time(),
        'earnablebadgeinstance'     => $earnablebadge->id,
        'earnablebadgecmid'         => $cm->id,
        'earnablebadgename'         => format_string($earnablebadge->name),
        'earnablebadgeidnumber'     => $cm->idnumber,
    );

    if (isloggedin()) {
        $values['userid']          = $USER->id;
        $values['userusername']    = $USER->username;
        $values['useridnumber']    = $USER->idnumber;
        $values['userfirstname']   = $USER->firstname;
        $values['userlastname']    = $USER->lastname;
        $values['userfullname']    = fullname($USER);
        $values['useremail']       = $USER->email;
        $values['usericq']         = $USER->icq;
        $values['userphone1']      = $USER->phone1;
        $values['userphone2']      = $USER->phone2;
        $values['userinstitution'] = $USER->institution;
        $values['userdepartment']  = $USER->department;
        $values['useraddress']     = $USER->address;
        $values['usercity']        = $USER->city;
        $now = new DateTime('now', core_date::get_user_timezone_object());
        $values['usertimezone']    = $now->getOffset() / 3600.0; // Value in hours for BC.
        $values['userearnablebadge']         = $USER->earnablebadge;
    }

    // weak imitation of Single-Sign-On, for backwards compatibility only
    // NOTE: login hack is not included in 2.0 any more, new contrib auth plugin
    //       needs to be createed if somebody needs the old functionality!
    if (!empty($config->secretphrase)) {
        $values['encryptedcode'] = earnablebadge_get_encrypted_parameter($earnablebadge, $config);
    }

    //hmm, this is pretty fragile and slow, why do we need it here??
    if ($config->rolesinparams) {
        $coursecontext = context_course::instance($course->id);
        $roles = role_fix_names(get_all_roles($coursecontext), $coursecontext, ROLENAME_ALIAS);
        foreach ($roles as $role) {
            $values['course'.$role->shortname] = $role->localname;
        }
    }

    return $values;
}

/**
 * BC internal function
 * @param object $earnablebadge
 * @param object $config
 * @return string
 */
function earnablebadge_get_encrypted_parameter($earnablebadge, $config) {
    global $CFG;

    if (file_exists("$CFG->dirroot/local/externserverfile.php")) {
        require_once("$CFG->dirroot/local/externserverfile.php");
        if (function_exists('extern_server_file')) {
            return extern_server_file($earnablebadge, $config);
        }
    }
    return md5(getremoteaddr().$config->secretphrase);
}

/**
 * Optimised mimetype detection from general earnablebadge
 * @param $fullearnablebadge
 * @param int $size of the icon.
 * @return string|null mimetype or null when the filetype is not relevant.
 */
function earnablebadge_guess_icon($fullearnablebadge, $size = null) {
    global $CFG;
    require_once("$CFG->libdir/filelib.php");

    if (substr_count($fullearnablebadge, '/') < 3 or substr($fullearnablebadge, -1) === '/') {
        // Most probably default directory - index.php, index.html, etc. Return null because
        // we want to use the default module icon instead of the HTML file icon.
        return null;
    }

    $icon = file_extension_icon($fullearnablebadge, $size);
    $htmlicon = file_extension_icon('.htm', $size);
    $unknownicon = file_extension_icon('', $size);

    // We do not want to return those icon types, the module icon is more appropriate.
    if ($icon === $unknownicon || $icon === $htmlicon) {
        return null;
    }

    return $icon;
}
