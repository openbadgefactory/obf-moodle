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
$badgehascss = !empty($badge->get_criteria_css());
$xhrrequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
?>

<?php if (!$xhrrequest): ?>
    <html>
        <head>
            <title><?php echo get_string('criteriapreview', 'local_obf') ?></title>
            <?php if ($badgehascss): ?>
                <style type="text/css">
                    <?php echo $badge->get_criteria_css(); ?>
                </style>
            <?php else: ?>
                <style type="text/css">
                    body { background-color: #FFF; font-family: "Source Sans Pro",sans-serif; color: #333; margin: 75px auto; width: 800px; border: 1px solid #CCC; padding: 10px; border-radius: 3px; box-shadow: 4px 4px 10px 2px rgba(80, 80, 80, 0.4); }
                </style>
            <?php endif; ?>
        </head>

        <body class='local-obf criteria-page'>
            <?php echo $badge->get_criteria_html(); ?>
        </body>
    </html>
<?php else: ?>
    <?php echo $badge->get_criteria_html(); ?>
<?php endif; ?>
