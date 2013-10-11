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
        $table->add_field('obf_criterion_type_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('badge_id', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_criterion_attributes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_type_id', XMLDB_KEY_FOREIGN, array('obf_criterion_type_id'), 'obf_criterion_types', array('id'));

        // Conditionally launch create table for obf_criterion_attributes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2013100701, 'local', 'obf');

        return true;
    }

    if ($oldversion < 2013100702) {

        // Define table obf_criterion_groups to be created.
        $table = new xmldb_table('obf_criterion_groups');

        // Adding fields to table obf_criterion_groups.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('obf_criterion_type_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('badge_id', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('completion_method', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_criterion_groups.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_type_id', XMLDB_KEY_FOREIGN, array('obf_criterion_type_id'), 'obf_criterion_types', array('id'));

        // Conditionally launch create table for obf_criterion_groups.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table obf_criterion_attributes to be created.
        $table = new xmldb_table('obf_criterion_attributes');

        // Adding fields to table obf_criterion_attributes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('obf_criterion_group_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_criterion_attributes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_group_id', XMLDB_KEY_FOREIGN, array('obf_criterion_group_id'), 'obf_criterion_groups', array('id'));

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
        $table->add_field('obf_criterion_group_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_criterion_attributes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_obf_criterion_group_id', XMLDB_KEY_FOREIGN, array('obf_criterion_group_id'), 'obf_criterion_groups', array('id'));

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
        $field = new xmldb_field('obf_criterion_group_id', XMLDB_TYPE_INTEGER,
                '10', null, XMLDB_NOTNULL, null, null, 'id');
        
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
        $field = new xmldb_field('obf_criterion_type_id', XMLDB_TYPE_INTEGER,
                '10', null, XMLDB_NOTNULL, null, null, 'id');
        
        $dbman->rename_field($table, $field, 'criterion_type_id');
        
        $table = new xmldb_table('obf_criterion_types');
        $dbman->drop_table($table);
        
        upgrade_plugin_savepoint(true, 2013101001, 'local', 'obf');
    }
}

?>
