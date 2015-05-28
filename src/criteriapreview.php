<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/class/badge.php';

$badgeid = required_param('badge_id', PARAM_ALPHANUM);

require_login();
// TODO: capabilities?

$badge = obf_badge::get_instance($badgeid);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/obf/criteriapreview.php', array('badge_id' => $badgeid)));
$PAGE->set_title(get_string('criteriapreview', 'local_obf'));
$PAGE->set_pagelayout('popup');
?>

<html>
    <head>
        <title><?php echo get_string('criteriapreview', 'local_obf') ?></title>
        <style type="text/css">
            <?php echo $badge->get_criteria_css(); ?>
        </style>
    </head>

    <body>
        <?php echo $badge->get_criteria_html(); ?>
    </body>
</html>
