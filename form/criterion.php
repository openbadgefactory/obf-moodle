<?php
defined('MOODLE_INTERNAL') or die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class obf_criterion_form extends moodleform implements renderable {
    /**
     * @var obf_criterion_base
     */
    protected $criterion = null;
    
    protected function definition() {
        $mform = $this->_form;
        
        $this->criterion = $this->_customdata['criterion'];    
        $this->criterion->customizeform($this);
//        $this->add_action_buttons();
    }
    
    public function get_criterion() {
        return $this->criterion;
    }
    
    public function get_form() {
        return $this->_form;
    }
}
?>
