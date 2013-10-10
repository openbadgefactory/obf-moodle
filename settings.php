<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // obf-category in site admin
    $obf = new admin_category('obf', get_string('obf', 'local_obf'));

    // obf-settings
    $settings = new admin_settingpage('local_obf', get_string('settings', 'local_obf'));
    $settings->add(new admin_setting_configtext('url', get_string('url', 'local_obf'),
            get_string('urldescription', 'local_obf'), 'https://localhost/obf/v1', PARAM_URL));
    $settings->add(new admin_setting_configtext('client_id', get_string('clientid', 'local_obf'),
            get_string('clientiddescription', 'local_obf'), '', PARAM_ALPHANUM));

    // badge list -page
    $badgelist = new admin_externalpage('badgelist', get_string('badgelist', 'local_obf'),
            new moodle_url('/local/obf/badge.php', array('action' => 'list')));

    // issue badge -page
//    $issuebadge = new admin_externalpage('issuebadge', get_string('issuebadge', 'local_obf'), $CFG->wwwroot . '/local/obf/issue.php');
    
    // issuance history -page
    $history = new admin_externalpage('badgehistory', get_string('history', 'local_obf'),
            new moodle_url('/local/obf/badge.php', array('action' => 'history')));
    
    // add pages to navigation
    $ADMIN->add('root', $obf);
    $ADMIN->add('obf', $settings);
    $ADMIN->add('obf', $badgelist);
    $ADMIN->add('obf', $history);
//    $ADMIN->add('obf', $issuebadge);
}
?>