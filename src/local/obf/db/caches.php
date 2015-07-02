<?php
$definitions = array(
    'obf_assertions' => array( // Note that obf_assertions refers to obf_assertion class, and this cache may contain Mozilla Backpack badges as well
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => (24 * 60 * 60), // TODO: Remove ttl? -- It's recommended to use event driven cache invalidation
        'invalidationevents' => array('new_obf_assertion', 'obf_blacklist_changed')
    ),
    'obf_assertions_moz' => array( // Mozilla backpack badges
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => (24 * 60 * 60), // TODO: Remove ttl? -- It's recommended to use event driven cache invalidation
        'invalidationevents' => array('new_obf_assertion')
    ),
    'obf_assertions_obp' => array( // Open Badge Passport backpack badges
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => (24 * 60 * 60), // TODO: Remove ttl? -- It's recommended to use event driven cache invalidation
        'invalidationevents' => array('new_obf_assertion')
    )
);
