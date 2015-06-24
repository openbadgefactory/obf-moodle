<?php
$capabilities = array(

    // Can configure plugin settings.
    'local/obf:configure' => array(
        'riskbitmask'   => RISK_CONFIG,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'manager'   => CAP_ALLOW
        )
    ),

    // Can configure own backpack settings.
    'local/obf:configureuser' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'user'      => CAP_ALLOW
        )
    ),

    // Can see the badges of the participants of the same course.
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

    // Can view all badges.
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

    // Can issue a badge.
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

    // Can revoke a badge issued by an unknown party or another user.
    'local/obf:revokeallevents' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    ),

    // Can revoke a badge, which was automatically issued based on course criteria.
    'local/obf:revokecourseevents' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    ),

    // Can view issuance history.
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

    // Can view badge details.
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

    // Can earn a badge.
    'local/obf:earnbadge' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'student'   => CAP_ALLOW
        )
    ),

    // Can view backpack.
    'local/obf:viewbackpack' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_USER,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW,
            'user'              => CAP_ALLOW
        )
    ),

    // Can view own backpack.
    'local/obf:viewownbackpack' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_USER,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW,
            'student'           => CAP_ALLOW,
            'user'              => CAP_ALLOW
        )
    ),

    // Can view all events.
    'local/obf:viewallevents' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    ),

    // Can revoke a badge, which was automatically issued based on course criteria.
    'local/obf:viewcourseevents' => array(
        'captype'       => 'read',
        'contextlevel'  => CONTEXT_COURSE,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    ),

    // Can edit badge criterion.
    'local/obf:editcriterion' => array(
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'archetypes'    => array(
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'manager'           => CAP_ALLOW
        )
    )
);