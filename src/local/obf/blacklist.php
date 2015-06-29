<?php
/**
 * Page for blacklisting individual badges from being displayed on the profile page.
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/class/blacklist.php';
require_once __DIR__ . '/form/blacklist.php';
require_once __DIR__ . '/class/user_preferences.php';

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
        array('userpreferences' => $obfuserpreferences,
              'user' => $USER,
              'blacklist' => new obf_blacklist($USER->id)));

switch ($action) {
    case 'edit':
        if (!empty($msg)) {
            $content .= $OUTPUT->notification($msg,'notifysuccess');
        }
        $content .= $PAGE->get_renderer('local_obf')->render_blacklistconfig($form, $error);
        break;
    case 'update':
        if ($data = $form->get_data()) {
            $newblacklist = array_keys(array_filter($data->blacklist));
            $blacklist = new obf_blacklist($USER->id);
            $blacklist->save($newblacklist);
            cache_helper::invalidate_by_event('new_obf_assertion', array($USER->id));

            $redirecturl = $url;
            $redirecturl->param('msg', get_string('blacklistsaved', 'local_obf'));
            $redirecturl->param('action', 'edit');
            redirect($redirecturl);
        }
        break;
}

$content .= $OUTPUT->footer();
echo $content;
