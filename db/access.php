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
    'local/obf:configureuser' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'user'      => CAP_ALLOW
        )
    ),
    'local/obf:seeparticipantbadges' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'student'           => CAP_ALLOW,
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    ),
    'local/obf:viewallbadges' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    ),
    'local/obf:issuebadge' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    ),
    'local/obf:viewhistory' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    ),
    'local/obf:viewdetails' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    ),
    'local/obf:earnbadge' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'student'   => CAP_ALLOW
        )
    )
);
