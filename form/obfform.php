<?php
require_once($CFG->libdir . '/formslib.php');

abstract class obfform extends moodleform {

    public function render() {
        ob_start();
        $this->display();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

}
