<?php

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @global moodle_database $DB
 * @param int $oldversion
 * @return boolean
 */
function xmldb_local_obf_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2013100701) {

        // Define table obf_criterion_types to be created.
        $table = new xmldb_table('obf_criterion_types');

        // Adding fields to table obf_criterion_types.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_criterion_types.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for obf_criterion_types.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table obf_criterion_attributes to be created.
        $table = new xmldb_table('obf_criterion_attributes');

        // Adding fields to table obf_criterion_attributes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('obf_criterion_type_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
                null, null);
        $table->add_field('badge_id', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_criterion_attributes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_type_id', XMLDB_KEY_FOREIGN,
                array('obf_criterion_type_id'), 'obf_criterion_types', array('id'));

        // Conditionally launch create table for obf_criterion_attributes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013100701, 'local', 'obf');
    }

    if ($oldversion < 2013100702) {

        // Define table obf_criterion_groups to be created.
        $table = new xmldb_table('obf_criterion_groups');

        // Adding fields to table obf_criterion_groups.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('obf_criterion_type_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
                null, null);
        $table->add_field('badge_id', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('completion_method', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null,
                null);

        // Adding keys to table obf_criterion_groups.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_type_id', XMLDB_KEY_FOREIGN,
                array('obf_criterion_type_id'), 'obf_criterion_types', array('id'));

        // Conditionally launch create table for obf_criterion_groups.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table obf_criterion_attributes to be created.
        $table = new xmldb_table('obf_criterion_attributes');

        // Adding fields to table obf_criterion_attributes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('obf_criterion_group_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
                null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_criterion_attributes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_group_id', XMLDB_KEY_FOREIGN,
                array('obf_criterion_group_id'), 'obf_criterion_groups', array('id'));

        // Conditionally launch create table for obf_criterion_attributes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013100702, 'local', 'obf');
    }

    if ($oldversion < 2013100900) {
        $criteriontype = new stdClass();
        $criteriontype->name = 'coursecompletion';

        // TODO: do not insert here (or at least check whether the type already
        // exists.
        $DB->insert_record('obf_criterion_types', $criteriontype);

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013100900, 'local', 'obf');
    }

    if ($oldversion < 2013100901) {
        // Define table obf_criterion_attributes to be dropped.
        $table = new xmldb_table('obf_criterion_attributes');

        // Conditionally launch drop table for obf_criterion_attributes.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table obf_criterion_attributes to be created.
        $table = new xmldb_table('obf_criterion_attributes');

        // Adding fields to table obf_criterion_attributes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('obf_criterion_group_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
                null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_criterion_attributes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_group_id', XMLDB_KEY_FOREIGN,
                array('obf_criterion_group_id'), 'obf_criterion_groups', array('id'));

        // Conditionally launch create table for obf_criterion_attributes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013100901, 'local', 'obf');
    }

    if ($oldversion < 2013101000) {
        // drop index and rename the foreign key column
        $attributetable = new xmldb_table('obf_criterion_attributes');
        $attributetable->deleteKey('fk_obf_criterion_group_id');
        $field = new xmldb_field('obf_criterion_group_id', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, null, 'id');

        $dbman->rename_field($attributetable, $field, 'obf_criterion_id');

        // rename criterion table
        $criteriontable = new xmldb_table('obf_criterion_groups');
        $dbman->rename_table($criteriontable, 'obf_criterion');

        // add the new index
        $attributetable->add_key('fk_obf_criterion_id', XMLDB_KEY_FOREIGN,
                array('obf_criterion_id'), 'obf_criterion', array('id'));

        upgrade_plugin_savepoint(true, 2013101000, 'local', 'obf');
    }

    if ($oldversion < 2013101001) {
        $table = new xmldb_table('obf_criterion');
        $table->deleteKey('fk_obf_criterion_type_id');
        $field = new xmldb_field('obf_criterion_type_id', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, null, 'id');

        $dbman->rename_field($table, $field, 'criterion_type_id');

        $table = new xmldb_table('obf_criterion_types');
        $dbman->drop_table($table);

        upgrade_plugin_savepoint(true, 2013101001, 'local', 'obf');
    }

    if ($oldversion < 2013101400) {

        // Define table obf_email_templates to be created.
        $table = new xmldb_table('obf_email_templates');

        // Adding fields to table obf_email_templates.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('badge_id', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('footer', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_email_templates.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for obf_email_templates.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013101400, 'local', 'obf');
    }

    if ($oldversion < 2013101401) {

        // Define table obf_criterion_met to be created.
        $table = new xmldb_table('obf_criterion_met');

        // Adding fields to table obf_criterion_met.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('obf_criterion_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,
                null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('met_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_criterion_met.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_id', XMLDB_KEY_FOREIGN, array('obf_criterion_id'),
                'obf_criterion', array('id'));
        $table->add_key('fk_user_id', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'));

        // Conditionally launch create table for obf_criterion_met.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013101401, 'local', 'obf');
    }

    if ($oldversion < 2013102405) {

        // Define table obf_backpack_emails to be created.
        $table = new xmldb_table('obf_backpack_emails');

        // Adding fields to table obf_backpack_emails.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('backpack_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table obf_backpack_emails.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_user_id', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'));

        // Conditionally launch create table for obf_backpack_emails.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013102405, 'local', 'obf');
    }

    if ($oldversion < 2013102500) {

        // Define field group_id to be added to obf_backpack_emails.
        $table = new xmldb_table('obf_backpack_emails');
        $field = new xmldb_field('group_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null,
                'backpack_id');

        // Conditionally launch add field group_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013102500, 'local', 'obf');
    }

    if ($oldversion < 2013102900) {

        // Define table obf_criterion_courses to be dropped.
        $table = new xmldb_table('obf_criterion_attributes');

        // Conditionally launch drop table for obf_criterion_courses.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table obf_criterion_courses to be created.
        $table = new xmldb_table('obf_criterion_courses');

        // Adding fields to table obf_criterion_courses.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('obf_criterion_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,
                null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('completed_by', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table obf_criterion_courses.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_id', XMLDB_KEY_FOREIGN, array('obf_criterion_id'),
                'obf_criterion', array('id'));
        $table->add_key('fk_course_id', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));

        // Conditionally launch create table for obf_criterion_courses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013102900, 'local', 'obf');
    }

    if ($oldversion < 2013103000) {

        // Define field criterion_type_id to be dropped from obf_criterion.
        $table = new xmldb_table('obf_criterion');
        $field = new xmldb_field('criterion_type_id');
        $key = new xmldb_key('obf_critgrou_obf_ix', XMLDB_KEY_UNIQUE, array('criterion_type_id'));

        $dbman->drop_key($table, $key);

        // Conditionally launch drop field criterion_type_id.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013103000, 'local', 'obf');
    }

    if ($oldversion < 2013110500) {

        // Changing type of field group_id on table obf_backpack_emails to int.
        $table = new xmldb_table('obf_backpack_emails');
        $field = new xmldb_field('group_id', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'backpack_id');

        // Launch change of type for field group_id.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
            $dbman->rename_field($table, $field, 'groups');
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013110500, 'local', 'obf');
    }

    if ($oldversion < 2015052700) {
        $oldpkidir = realpath(__DIR__ . '/../pki/');
        global $CFG;
        $newpkidir = $CFG->dataroot . '/local_obf/pki/';

        if (!is_dir($newpkidir)) {
            mkdir($newpkidir,$CFG->directorypermissions,true);
        }
        $newpkidir = realpath($newpkidir);

        $pkey_filename = '/obf.key';
        $cert_filename = '/obf.pem';

        if (!is_writable($newpkidir)) {
            throw new Exception(get_string('pkidirnotwritable', 'local_obf',
                    $newpkidir));
        }
        $oldpkeyfile = $oldpkidir . $pkey_filename;
        $oldcertfile = $oldpkidir . $cert_filename;

        $newpkeyfile = $newpkidir . $pkey_filename;
        $newcertfile = $newpkidir . $cert_filename;

        if (is_file($oldpkeyfile) ) {
            copy($oldpkeyfile, $newpkeyfile);
        }
        if (is_file($oldcertfile) ) {
            copy($oldcertfile, $newcertfile);
        }

        if (is_writable($oldpkeyfile) && is_file($newpkeyfile)) {
            @unlink($oldpkeyfile);
        }
        if (is_writable($oldcertfile) && is_file($newcertfile)) {
            @unlink($oldcertfile);
        }

        // Obf savepoint reached
        upgrade_plugin_savepoint(true, 2015052700, 'local', 'obf');
    }
    
    if ($oldversion < 2015061000) {

        // Define field completion_method to be added to obf_criterion_courses.
        $table = new xmldb_table('obf_criterion_courses');
        $field = new xmldb_field('criteria_type', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'completed_by');

        // Conditionally launch add field completion_method.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            //TODO: Set all old completion methods to 1
        }
        // And a new table:

        // Define table obf_criterion_params to be created.
        $table = new xmldb_table('obf_criterion_params');

        // Adding fields to table obf_criterion_params.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('obf_criterion_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table obf_criterion_params.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_id', XMLDB_KEY_FOREIGN, array('obf_criterion_id'), 'obf_criterion', array('id'));

        // Conditionally launch create table for obf_criterion_params.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2015061000, 'local', 'obf');
    }

    return true;
}
