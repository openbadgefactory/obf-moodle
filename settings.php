<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // obf-category in site admin
    $obf = new admin_category('obf', get_string('obf', 'local_obf'));

    // obf-settings
    $settings = new admin_settingpage('local_obf', get_string('obfsettings', 'local_obf'));
    $settings->add(new admin_setting_configtext('obf_url', get_string('obfurl', 'local_obf'), get_string('obfurldescription', 'local_obf'), 'https://localhost/obf/v1', PARAM_URL));
    $settings->add(new admin_setting_configtext('obf_client_id', get_string('obfclientid', 'local_obf'), get_string('obfclientiddescription', 'local_obf'), '', PARAM_ALPHANUM));

    // badge list -page
    $badgelist = new admin_externalpage('obfbadgelist', get_string('obfbadgelist', 'local_obf'), $CFG->wwwroot . '/local/obf/badgelist.php');

    // add pages to navigation
    $ADMIN->add('root', $obf);
    $ADMIN->add('obf', $settings);
    $ADMIN->add('obf', $badgelist);
}
?>