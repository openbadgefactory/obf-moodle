<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . "/lib.php");

$badgeid = required_param('id', PARAM_ALPHANUM);
$context = context_system::instance();
$title = get_string('issuebadge', 'local_obf');

require_login();
require_capability('local/obf:issuebadge', $context);

$badge = obf_badge::get_instance($badgeid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/obf/issue.php', array('id' => $badgeid)));
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('admin');

$output = $PAGE->get_renderer('local_obf');

echo $OUTPUT->header();
echo $output->print_issuer_wizard($badge);

$PAGE->requires->yui_module('moodle-local_obf-issuerwizard', 'M.local_obf.init');
$PAGE->requires->strings_for_js(array(
        'emailsubject'
    ), 'local_obf');

/*
echo <<<JS
<script type="text/javascript">
// Create a new YUI instance and populate it with the required modules.
YUI({ fetchCSS: false }).use('tabview', function (Y) {
    var tabview = new Y.TabView({ srcNode: '#obf-issuerwizard' });
    tabview.render();

    // We need to apply Moodle CSS-class to selected tab
    // to make the tabs look like they should in Moodle.

    var changeClass = function () {
        var node = tabview.get('srcNode');
        node.all('.yui3-tab').removeClass('active');
        node.all('.yui3-tab-selected').addClass('active');
    };

    changeClass(tabview);
    tabview.after('selectionChange', function (e) {
        // selectionChange fires too early, so we need a tiny hack
        Y.later(0, null, function () {
            changeClass();

            // HACK: Isn't there a way to find out, which tab has been selected?
            // Like tabview.get('activeDescendant').get('id') == 'idofmytab'
            var lasttabselected = tabview.get('activeDescendant').get('index') == tabview._items.length - 1;
            
            if (lasttabselected) {
                console.log('Confirm!');
            }
        }, e);
    });
});
</script>
JS;
*/
echo $OUTPUT->footer();
?>
