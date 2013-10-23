<?php

// HACK: change this when we're not symlinking the plugin anymore
require_once('/var/www/moodle/config.php'); // __DIR__ . '/../../config.php';
require_once(__DIR__ . '/class/badge.php');
require_once($CFG->libdir . '/adminlib.php');

$badgeid = optional_param('id', '', PARAM_ALPHANUM);
$action = optional_param('action', 'list', PARAM_ALPHANUM);
$courseid = optional_param('courseid', null, PARAM_INT);
$message = optional_param('msg', '', PARAM_TEXT);
$context = empty($courseid) ? context_system::instance() : context_course::instance($courseid);

$url = new moodle_url('/local/obf/badge.php', array('action' => $action));
$badge = empty($badgeid) ? null : obf_badge::get_instance($badgeid);

if (!empty($badgeid)) {
    $url->param('id', $badgeid);
}

if (empty($courseid)) {
    require_login();
} else {
    $url->param('courseid', $courseid);
    require_login($courseid);
}

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout(empty($courseid) ? 'admin' : 'course');
$PAGE->set_title(get_string('obf', 'local_obf'));

$content = '';
$hasissuecapability = has_capability('local/obf:issuebadge', $context);

switch ($action) {

    // Show issuance history.
    case 'history':
        require_capability('local/obf:viewhistory', $context);

        $page = optional_param('page', 0, PARAM_INT);
        $content .= $PAGE->get_renderer('local_obf')->page_history($badge, $page);
        break;

    // Show the list of badges.
    case 'list':
        require_capability('local/obf:viewallbadges', $context);

//        $reload = optional_param('reload', false, PARAM_BOOL);

        try {
            $countbefore = 0;

//            if ($reload) {
//                $cachedtree = obf_badge_tree::get_from_cache();
//                $countbefore = $cachedtree !== false ? $cachedtree->get_badgecount() : 0;
//            }

//            $tree = obf_badge_tree::get_instance($reload);
            $tree = obf_badge_tree::get_instance();
//            $msg = '';

//            if ($reload) {
//                $countafter = $tree->get_badgecount();
//                $countdiff = max(array(0, $countafter - $countbefore));
//                $msg = get_string('badgesupdated', 'local_obf', $countdiff);
//            }

//            $content .= $PAGE->get_renderer('local_obf')->render_badgelist($tree,
//                    $hasissuecapability, $msg);
            $content .= $PAGE->get_renderer('local_obf')->render_badgelist($tree,
                    $hasissuecapability);
        } catch (Exception $e) {
            $content .= $OUTPUT->notification($e->getMessage(), 'notifyproblem');
        }

        break;

    // Display badge info.
    case 'show':
        require_capability('local/obf:viewdetails', $context);

        $page = optional_param('page', 0, PARAM_INT);
        $show = optional_param('show', 'details', PARAM_ALPHANUM);
        $baseurl = new moodle_url('/local/obf/badge.php',
                array('action' => 'show', 'id' => $badgeid));

        navigation_node::override_active_url(new moodle_url('/local/obf/badge.php',
                array('action' => 'list')));
        $PAGE->navbar->add($badge->get_name(), $baseurl);

        $renderer = $PAGE->get_renderer('local_obf', 'badge');

        switch ($show) {
            case 'email':
                $emailurl = new moodle_url('/local/obf/badge.php',
                        array('id' => $badge->get_id(),
                    'action' => 'show', 'show' => 'email'));

                $PAGE->navbar->add(get_string('badgeemail', 'local_obf'), $emailurl);
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
                // Small hack here, we'll beautify this later.
                if ($show != 'details') {
                    $taburl = clone $baseurl;
                    $taburl->param('show', $show);
                    $PAGE->navbar->add(get_string('badge' . $show, 'local_obf'), $taburl);
                }
                $content .= $PAGE->get_renderer('local_obf')->page_badgedetails($badge, $show,
                        $page, $message);
        }

        break;
}

echo $OUTPUT->header();
echo $content;
echo $OUTPUT->footer();