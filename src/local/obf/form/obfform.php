<?php

defined('MOODLE_INTERNAL') or die();

require_once($CFG->libdir . '/formslib.php');

/**
 * This class is just to add support for older versions of Moodle.
 */
abstract class local_obf_form_base extends moodleform {

    public function render() {
        $oldclasses = $this->_form->getAttribute('class');
        $this->_form->updateAttributes(array('class' => $oldclasses.' local-obf'));
        ob_start();
        $this->display();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    public function setExpanded(&$mform, $header, $expand = true) {
        // Moodle 2.2 doesn't have setExpanded
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded($header, $expand);
        }
    }

}
