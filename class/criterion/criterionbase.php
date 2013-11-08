<?php

require_once __DIR__ . '/../badge.php';

/**
 * Description of criteria_base
 *
 * @author olli
 */
abstract class obf_criterion_base {

    protected $id = -1;
    protected $criterionid = -1;

    public function exists() {
        return $this->id > 0;
    }

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function get_criterionid() {
        return $this->criterionid;
    }

    public function set_criterionid($criterionid) {
        $this->criterionid = $criterionid;
        return $this;
    }

    abstract public function get_text();

    abstract public function populate_from_record(stdClass $record);
}
