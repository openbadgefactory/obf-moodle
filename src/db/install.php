<?php

defined('MOODLE_INTERNAL') || die();

/**
  * @global type $CFG
  * @return boolean
  **/
function xmldb_local_obf_install() {
    global $CFG;
    $newpkidir = $CFG->dataroot . '/local_obf/pki/';

    if (!is_dir($newpkidir)) {
        mkdir($newpkidir,$CFG->directorypermissions,true);
    }

    return true;
}
