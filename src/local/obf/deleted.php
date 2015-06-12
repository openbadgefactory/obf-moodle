<?php
/**
 * Plugin configuration page.
 */
require_once __DIR__ . '/../../config.php';
require_once $CFG->libdir . '/adminlib.php';
require_once __DIR__ . '/form/criteriondeletion.php';
require_once __DIR__ . '/class/client.php';
require_once __DIR__ . '/class/criterion/criterion.php';

$context = context_system::instance();
$url = new moodle_url('/local/obf/deleted.php');
$msg = optional_param('msg', '', PARAM_TEXT);
$action = optional_param('action', 'list', PARAM_TEXT);
$client = obf_client::get_instance();

require_login();
require_capability('local/obf:configure', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$content = '';

switch ($action) {
    // List criteria that needs to be deleted
    case 'list':


        $criteria = obf_criterion::get_criteria_with_deleted_badges();
        foreach ($criteria as $criterion) {
            $deleteurl = new moodle_url('/local/obf/criterion.php',
            array('badgeid' => $criterion->get_badgeid(),
                    'action' => 'delete', 'id' => $criterion->get_id()));
            $deleteform = new obf_criterion_deletion_form($deleteurl, array('criterion' => $criterion));
            $content .= html_writer::tag('strong', $criterion->get_badgeid());
            $content .= $deleteform->render();
        }

        break;
}
echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();
