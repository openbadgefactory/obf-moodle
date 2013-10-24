<?php

$handlers = array(
    'course_completed' => array(
        'handlerfile' => '/local/obf/lib.php',
        'handlerfunction' => 'local_obf_course_completed',
        'schedule' => 'instant',
        'internal' => 1
    )
);
?>
