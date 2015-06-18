<?php

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');
require_once(__DIR__ . '/../renderer.php');

class obf_blacklist_form extends obfform {
    private $blacklist;
    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $userpreferences = $this->_customdata['userpreferences'];
        $this->blacklist = $this->_customdata['blacklist'];
        $user = $this->_customdata['user'];
        $client = new obf_client();
        $assertions = obf_assertion::get_assertions($client, null, $user->email);
        $unique_assertions = new obf_assertion_collection();
        $unique_assertions->add_collection($assertions);

        $this->render_badges($unique_assertions, $mform);

        $this->add_action_buttons();
        //$mform->closeHeaderBefore('buttonar');
    }

    private function render_badges(obf_assertion_collection $assertions, &$mform) {
        global $PAGE, $OUTPUT;

        $items = array();
        $renderer = $PAGE->get_renderer('local_obf');
        $size = local_obf_renderer::BADGE_IMAGE_SIZE_NORMAL;


        for ($i = 0; $i < count($assertions); $i++) {
            $badge = $assertions->get_assertion($i)->get_badge();
            //$items[] = obf_html::div($renderer->print_badge_image($badge, $size) .
            //                html_writer::tag('p', s($badge->get_name())));
            $html = $OUTPUT->box(obf_html::div($renderer->print_badge_image($badge, $size) .
                    html_writer::tag('p', s($badge->get_name()))));
            $items[] = $mform->createElement('advcheckbox', $badge->get_id(),
                    '', $html);
        }
        if (count($items) > 0) {
            $mform->addGroup($items, 'blacklist', '', array(' '), false);
        }

        $badgeids = $this->blacklist->get_blacklist();
        foreach ($badgeids as $badgeid) {
            $mform->setDefault('blacklist['.$badgeid.']', 1);
        }
        //return html_writer::alist($items, array('class' => 'badgelist'));
    }

}
