<?php
$definitions = array(
    'obf_assertions' => array( // Note that obf_assertions refers to obf_assertion class, and this cache may contain Mozilla Backpack badges as well
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => (24 * 60 * 60), // TODO: Remove ttl? -- It's recommended to use event driven cache invalidation
        'invalidationevents' => array('new_obf_assertion')
    )
);
