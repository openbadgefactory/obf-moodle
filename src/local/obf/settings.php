<?php
defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/class/client.php';

if ($hassiteconfig) {
    // obf-category in site admin
    $obf = new admin_category('obf', get_string('obf', 'local_obf'));

    // obf-settings
    $settings = new admin_externalpage('obfconfig', get_string('settings', 'local_obf'),
            new moodle_url('/local/obf/config.php'));

    // add pages to navigation
    $ADMIN->add('root', $obf, 'location');
    $ADMIN->add('obf', $settings);

    // badge list -page
    $badgelist = new admin_externalpage('badgelist', get_string('badgelist', 'local_obf'),
            new moodle_url('/local/obf/badge.php', array('action' => 'list')));

    // issuance history -page
    $history = new admin_externalpage('badgehistory', get_string('history', 'local_obf'),
            new moodle_url('/local/obf/badge.php', array('action' => 'history')));

    // category settings page
    $client = obf_client::get_instance();

    if ($client->test_connection() === -1) {
        $categories = obf_client::get_instance()->get_categories();
        $categorysettings = new admin_settingpage('badgecategories', get_string('categorysettings', 'local_obf'),
                'local/obf:configure');
        $categorysettings->add(new admin_setting_configmultiselect('local_obf/availablecategories',
                get_string('availablecategories', 'local_obf'), get_string('availablecategorieshelp', 'local_obf'),
                array(), array_combine($categories, $categories)));

        $ADMIN->add('obf', $categorysettings);

        // badge export settings
        $export = new admin_externalpage('obfexportbadges', get_string('exportsettings', 'local_obf'),
                        new moodle_url('/local/obf/config.php', array('action' => 'exportbadges')));
        $ADMIN->add('obf', $export);
    }

    $ADMIN->add('obf', $badgelist);
    $ADMIN->add('obf', $history);
}
