<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . '/class/criterion/criterionbase.php');
require_once(__DIR__ . "/lib.php");

$id = optional_param('id', null, PARAM_INT);
$action = optional_param('action', 'new', PARAM_ALPHANUM);
$badgeid = required_param('badgeid', PARAM_ALPHANUM);
$type = optional_param('type', 1, PARAM_INT);
$context = context_system::instance();
$content = '';

require_login();

$badge = obf_badge::get_instance($badgeid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/obf/criterion.php',
        array('action' => $action,
    'id' => $id, 'badgeid' => $badgeid, 'type' => $type)));
$PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . $badge->get_name());
$PAGE->set_heading(get_string('addcriteria', 'local_obf'));
$PAGE->set_pagelayout('admin');

switch ($action) {
    case 'new': case 'edit':
        $criterionobj = !empty($id) ? obf_criterion_base::get_instance($id) : obf_criterion_base::get_empty_instance($type,
                        $badge);
        $content = $PAGE->get_renderer('local_obf')->render($criterionobj);
        break;

    case 'delete':
        require_once __DIR__ . '/form/criteriondeletion.php';

        $criterionobj = obf_criterion_base::get_instance($id);
        $deletionform = new obf_criterion_deletion_form($FULLME, array('criterion' => $criterionobj));
        $url = new moodle_url('/local/obf/badge.php',
                array('action' => 'show', 'show' => 'criteria',
            'id' => $badgeid));

        // deletion cancelled
        if ($deletionform->is_cancelled()) {
            redirect($url);
        }
        // deletion confirmed                
        else if ($deletionform->is_submitted()) {
            $criterionobj->delete();
            redirect($url, get_string('criteriondeleted', 'local_obf'));
        } else {
            $content = $PAGE->get_renderer('local_obf')->render($deletionform);
        }

        break;
    case 'list':
        break;
}

echo $content;
?>
