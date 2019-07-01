<?php
$tasks = array(
    array(
        'classname' => 'local_obf\task\certificate_expiration_reminder',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '8',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'local_obf\task\user_email_changer',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '8',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    )
);
