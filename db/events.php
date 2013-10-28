<?php
$handlers = array(
    'course_completed' => array(
        'handlerfile' => '/local/obf/lib.php',
        'handlerfunction' => 'local_obf_course_completed',
        'schedule' => 'instant',
        'internal' => 1
    ),
    'course_deleted' => array(
        'handlerfile' => '/local/obf/lib.php',
        'handlerfunction' => 'local_obf_course_deleted',
        'schedule' => 'instant',
        'internal' => 1
    )
);