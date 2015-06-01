<?php
$definitions = array(
    'assertions' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => (24 * 60 * 60) // TODO: It's recommended to use event driven cache invalidation
    )
);
