<?php
/**
 * Description of criteria_base
 *
 * @author olli
 */
abstract class obf_criteria_base {
    public static function get_instance() {
        return new static();
    }
    
    abstract public function render(obf_badge $badge);
}

?>
