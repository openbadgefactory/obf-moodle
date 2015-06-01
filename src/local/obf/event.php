<?php
/**
 * Displays the details of a single event from OBF.
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/local/obf/class/assertion.php';

require_login();
require_capability('local/obf:viewallevents', context_system::instance()); // TODO: more specific capabilities?

$client = obf_client::get_instance();
$eventid = required_param('id', PARAM_ALPHANUM);
$assertion = obf_assertion::get_instance_by_id($eventid, $client);
$badge = $assertion->get_badge();

$PAGE->set_url(new moodle_url('/blocks/obf/view.php', array('id' => $eventid)));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('obf', 'local_obf'));

navigation_node::override_active_url(new moodle_url('/local/obf/badge.php',
        array('action' => 'history')));

$PAGE->navbar->add(get_string('issuancedetails', 'local_obf'));

$content = $OUTPUT->header();
$content .= $PAGE->get_renderer('local_obf')->render_assertion($assertion);
$content .= $OUTPUT->footer();

echo $content;
