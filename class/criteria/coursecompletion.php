<?php
require_once __DIR__ . '/base.php';

/**
 * Description of coursecompletion
 *
 * @author olli
 */
class obf_criteria_coursecompletion extends obf_criteria_base {
    public function render(obf_badge $badge) {
        return '<p>' . $badge->get_name() . '</p>';
    }
}

?>
