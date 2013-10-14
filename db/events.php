<?php

$handlers = array(
    'course_completed' => array(
        'handlerfile' => '/local/obf/lib.php',
        'handlerfunction' => 'obf_course_completed',
        'schedule' => 'instant',
        'internal' => 1
    )
);

//$handlers = array(
//    'user_enrolled' => array(
//        'handlerfile' => '/mod/forum/lib.php',
//        'handlerfunction' => 'forum_user_enrolled',
//        'schedule' => 'instant',
//        'internal' => 1,
//    ),
//    'user_unenrolled' => array(
//        'handlerfile' => '/mod/forum/lib.php',
//        'handlerfunction' => 'forum_user_unenrolled',
//        'schedule' => 'instant',
//        'internal' => 1,
//    ),
//);
?>
