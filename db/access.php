<?php
$capabilities = array(
    'local/obf:configure' => array(
        'riskbitmask'   => RISK_CONFIG,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'manager'   => CAP_ALLOW
        )
    ),
    'local/obf:viewallbadges' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'manager'   => CAP_ALLOW
        )
    ),
    'local/obf:issuebadge' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'manager'   => CAP_ALLOW,
            'teacher'   => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        )
    )
);
?>
