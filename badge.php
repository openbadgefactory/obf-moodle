<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . '/class/badge.php');

$badgeid = optional_param('id', '', PARAM_ALPHANUM);
$action = optional_param('action', 'list', PARAM_ALPHANUM);
$badge = empty($badgeid) ? null : obf_badge::get_instance($badgeid);
$context = context_system::instance();
$url = new moodle_url('/local/obf/badge.php', array('id' => $badgeid, 'action' => $action));
$message = optional_param('msg', '', PARAM_TEXT);

require_login();

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

// TODO: fix breadcrumbs
$content = $OUTPUT->header();

switch ($action) {

    // show issuance history
    case 'history':
        require_capability('local/obf:viewhistory', $context);

        $page = optional_param('page', 0, PARAM_INT);
        $PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . get_string('history', 'local_obf'));
        $PAGE->set_heading(get_string('history', 'local_obf'));
        $content .= $PAGE->get_renderer('local_obf')->page_history($badge, $page);
        break;

    // show the list of badges
    case 'list':
        require_capability('local/obf:viewallbadges', $context);

        $reload = optional_param('reload', false, PARAM_BOOL);
        $PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . get_string('badgelist',
                        'local_obf'));
        $PAGE->set_heading(get_string('badgelisttitle', 'local_obf'));

        try {
            $countbefore = 0;
            
            if ($reload) {
                $cachedtree = obf_badge_tree::get_from_cache();
                $countbefore = $cachedtree !== false ? $cachedtree->get_badgecount() : 0;
            }
            
            $tree = obf_badge_tree::get_instance($reload);
            
            if ($reload) {
                $countafter = $tree->get_badgecount();
                $countdiff = max(array(0, $countafter-$countbefore));
                $content .= $OUTPUT->notification(get_string('badgesupdated', 'local_obf',
                        $countdiff), 'notifysuccess');
            }
            
            $content .= $PAGE->get_renderer('local_obf')->render_badgelist($tree);
        } catch (Exception $e) {
            $content .= $OUTPUT->notification($e->getMessage(), 'notifyproblem');
        }

        break;

    // display badge info
    case 'show':
        require_capability('local/obf:viewdetails', $context);

        $page = optional_param('page', 0, PARAM_INT);
        $show = optional_param('show', 'details', PARAM_ALPHANUM);
        $PAGE->set_title(get_string('obf', 'local_obf') . ' - ' . $badge->get_name());
        $PAGE->set_heading(get_string('badgedetails', 'local_obf'));
        $renderer = $PAGE->get_renderer('local_obf', 'badge');
        
        switch ($show) {
            case 'email':
                $emailurl = new moodle_url('/local/obf/badge.php',
                        array('id' => $badge->get_id(),
                    'action' => 'show', 'show' => 'email'));
                $form = new obf_email_template_form($emailurl, array('badge' => $badge));
                $html = '';

                if (!empty($message)) {
                    $html .= $OUTPUT->notification($message, 'notifysuccess');
                }

                if (!is_null($data = $form->get_data())) {
                    $email = is_null($badge->get_email()) ? new obf_email() : $badge->get_email();
                    $email->set_badge_id($badge->get_id());
                    $email->set_subject($data->emailsubject);
                    $email->set_body($data->emailbody);
                    $email->set_footer($data->emailfooter);
                    $email->save();

                    $redirecturl = clone $emailurl;
                    $redirecturl->param('msg', get_string('emailtemplatesaved', 'local_obf'));

                    redirect($redirecturl);
                }

                $html .= $form->render();
                $content .= $renderer->page($badge, 'email', $html);
                break;

            default:
                $content .= $PAGE->get_renderer('local_obf')->page_badgedetails($badge, $show, $page,
                        $message);
                break;
        }

        break;
}

$content .= $OUTPUT->footer();
echo $content;
?>
