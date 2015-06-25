<?php
/**
 * Page for handling user's backpack settings.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/form/userconfig.php';
require_once __DIR__ . '/class/backpack.php';
require_once __DIR__ . '/class/user_preferences.php';

$error = optional_param('error', '', PARAM_TEXT);
$action = optional_param('action', 'edit', PARAM_TEXT);
$context = context_system::instance();
$url = new moodle_url('/local/obf/userconfig.php', array('action' => $action));

require_login();
require_capability('local/obf:configureuser', $context);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$content = $OUTPUT->header();
$backpack = obf_backpack::get_instance($USER);
$obpbackpack = obf_backpack::get_instance($USER,obf_backpack::BACKPACK_PROVIDER_OBP);
$obfuserpreferences = new obf_user_preferences($USER->id);
$formurl = new moodle_url('/local/obf/userconfig.php', array('action' => 'update'));
/*
$form = new obf_userconfig_form($formurl,
        array('backpack' => ($backpack === false ? new obf_backpack() : $backpack),
            'obpbackpack' => ($obpbackpack === false ? new obf_backpack(null,obf_backpack::BACKPACK_PROVIDER_OBP) : $obpbackpack),
              'userpreferences' => $obfuserpreferences));
*/
$backpacks = array(
    $backpack !== false ? $backpack : new obf_backpack(null),
    $obpbackpack !== false ? $obpbackpack : new obf_backpack(null, obf_backpack::BACKPACK_PROVIDER_OBP));
$form = new obf_userconfig_form($formurl,
        array('backpacks' => $backpacks,
              'userpreferences' => $obfuserpreferences));

switch ($action) {
    case 'edit':
        $content .= $PAGE->get_renderer('local_obf')->render_userconfig($form, $error);
        break;

    case 'update':
        // Disconnect-button was pressed
        if ($form->is_cancelled()) {
            $submitteddata = $form->get_submitted_data();
            foreach ($backpacks as $backpack) {
                if (property_exists($submitteddata,'cancelbackpack' . $backpack->get_providershortname())) {
                    if ($backpack->exists()) {
                        $backpack->disconnect();
                    }
                }
            }
            redirect($url);
        }

        // User configuration was saved.
        else if (($data = $form->get_data())) {
            $obfuserpreferences->save_preferences($data);
            $redirecturl = new moodle_url('/local/obf/userconfig.php', array('action' => 'edit'));
            // If were saving backpack data, we can safely assume that the backpack exists, because it
            // had to be created before (via verifyemail.php)
            foreach ($backpacks as $backpack) {
                if ($backpack->exists()) {
                    $propertyname = $backpack->get_providershortname() . 'backpackgroups';
                    if (isset($data->{$propertyname})) {
                        $backpack->set_groups(array_keys($data->{$propertyname}));
                    }

                    $redirecturl = new moodle_url('/local/obf/userconfig.php', array('action' => 'edit'));

                    try {
                        $backpack->save();
                    } catch (Exception $e) {
                        $redirecturl->param('error', $e->getMessage());
                    }
                }
            }

            redirect($redirecturl);
        }
        $content .= $PAGE->get_renderer('local_obf')->render_userconfig($form, $error);
        break;
    case 'backpack':

        break;

}

$content .= $OUTPUT->footer();
echo $content;
