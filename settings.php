<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // obf-category in site admin
    $obf = new admin_category('obf', get_string('obf', 'local_obf'));

    // obf-settings
    $settings = new admin_externalpage('obfconfig', get_string('settings', 'local_obf'),
            new moodle_url('/local/obf/config.php'));

    // badge list -page
    $badgelist = new admin_externalpage('badgelist', get_string('badgelist', 'local_obf'),
            new moodle_url('/local/obf/badge.php', array('action' => 'list')));

    // issuance history -page
    $history = new admin_externalpage('badgehistory', get_string('history', 'local_obf'),
            new moodle_url('/local/obf/badge.php', array('action' => 'history')));
    
    // add pages to navigation
    $ADMIN->add('root', $obf, 'location');
    $ADMIN->add('obf', $settings);
    $ADMIN->add('obf', $badgelist);
    $ADMIN->add('obf', $history);
//    $ADMIN->add('obf', $issuebadge);
}