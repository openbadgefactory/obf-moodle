<?php
$plugin->version = 2015070200;  // YYYYMMDDHH (year, month, day, 24-hr time)
$plugin->requires = 2010112400; // YYYYMMDDHH (This is the release version for Moodle 2.0)
$plugin->component = 'block_obf_displayer';
$plugin->maturity = MATURITY_ALPHA;

$plugin->dependencies = array(
    'local_obf' => ANY_VERSION   // The Foo activity must be present (any version).
);
