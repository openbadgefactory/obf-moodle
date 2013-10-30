<?php

class obf_coursecriterion_form extends moodleform {

    protected function definition() {
        $mform = $this->_form;
        $coursedata = $this->_customdata['coursedata'];

        obf_criterion_courseset::add_coursefields($mform, $coursedata->id, $coursedata);

        $this->add_action_buttons(false);
    }

}
