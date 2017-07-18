<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * See https://docs.moodle.org/dev/Upgrade_API for details.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * The upgrade function for local_obf.
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_local_obf_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2016031800) {
         //set default apiurl https://openbadgefactory.com/
         set_config('apiurl', "https://openbadgefactory.com/", 'local_obf');
         // Obf savepoint reached.
         upgrade_plugin_savepoint(true, 2016031800, 'local', 'obf');
    }

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

        // OBF savepoint reached.
        upgrade_plugin_savepoint(true, 2013100901, 'local', 'obf');
    }

    if ($oldversion < 2013101000) {
        // Drop index and rename the foreign key column.
        $attributetable = new xmldb_table('obf_criterion_attributes');
        $attributetable->deleteKey('fk_obf_criterion_group_id');
        $field = new xmldb_field('obf_criterion_group_id', XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, null, 'id');

        $dbman->rename_field($attributetable, $field, 'obf_criterion_id');

        // Rename criterion table.
        $criteriontable = new xmldb_table('obf_criterion_groups');
        $dbman->rename_table($criteriontable, 'obf_criterion');

        // Add the new index.
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
            mkdir($newpkidir, $CFG->directorypermissions, true);
        }
        $newpkidir = realpath($newpkidir);

        $pkeyfilename = '/obf.key';
        $certfilename = '/obf.pem';

        if (!is_writable($newpkidir)) {
            throw new Exception(get_string('pkidirnotwritable', 'local_obf',
                    $newpkidir));
        }
        $oldpkeyfile = $oldpkidir . $pkeyfilename;
        $oldcertfile = $oldpkidir . $certfilename;

        $newpkeyfile = $newpkidir . $pkeyfilename;
        $newcertfile = $newpkidir . $certfilename;

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

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2015052700, 'local', 'obf');
    }

    if ($oldversion < 2015061000) {

        // Define field completion_method to be added to obf_criterion_courses.
        $table = new xmldb_table('obf_criterion_courses');
        $field = new xmldb_field('criteria_type', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'completed_by');

        // Conditionally launch add field completion_method.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            // TODO: Set all old completion methods to 1.
        }
        // And a new table...

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
    if ($oldversion < 2015061700) {

        // Define table obf_user_preferences to be created.
        $table = new xmldb_table('obf_user_preferences');

        // Adding fields to table obf_user_preferences.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table obf_user_preferences.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for obf_user_preferences.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2015061700, 'local', 'obf');
    }
    if ($oldversion < 2015061800) {

        // Define table obf_user_badge_blacklist to be created.
        $table = new xmldb_table('obf_user_badge_blacklist');

        // Adding fields to table obf_user_badge_blacklist.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('badge_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table obf_user_badge_blacklist.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_user_id', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'));

        // Adding indexes to table obf_user_badge_blacklist.
        $table->add_index('idx_badge', XMLDB_INDEX_NOTUNIQUE, array('badge_id'));

        // Conditionally launch create table for obf_user_badge_blacklist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2015061800, 'local', 'obf');
    }
    if ($oldversion < 2015062100) {

        // Define table obf_issue_events to be created.
        $table = new xmldb_table('obf_issue_events');

        // Adding fields to table obf_issue_events.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('event_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('obf_criterion_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table obf_issue_events.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_user_id', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'));
        $table->add_key('fk_obf_criterion_id', XMLDB_KEY_FOREIGN, array('obf_criterion_id'), 'obf_criterion', array('id'));

        // Conditionally launch create table for obf_issue_events.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2015062100, 'local', 'obf');
    }
    if ($oldversion < 2015062300) {

        // Define field backpack_provider to be added to obf_backpack_emails.
        $table = new xmldb_table('obf_backpack_emails');
        $field = new xmldb_field('backpack_provider', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'groups');

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2015062300, 'local', 'obf');
    }
    if ($oldversion < 2015062501) {
        $oldtables = array('obf_criterion_courses', 'obf_criterion',
                'obf_email_templates', 'obf_criterion_met', 'obf_backpack_emails',
                'obf_criterion_params', 'obf_user_preferences',
                'obf_user_badge_blacklist', 'obf_issue_events');
        foreach ($oldtables as $oldtable) {
            // Define table obf_criterion_courses to be renamed to NEWNAMEGOESHERE.
            $table = new xmldb_table($oldtable);

            // Launch rename table for obf_criterion_courses.

            if ($oldtable == 'obf_user_badge_blacklist') {
                $newtablename = 'local_obf_badge_blacklists';
            } else {
                $newtablename = 'local_'.$oldtable;
            }
            if ($dbman->table_exists($table)) {
                $dbman->rename_table($table, $newtablename);
            }
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2015062501, 'local', 'obf');
    }
    if ($oldversion < 2015070600) {
        set_config('availablecategories', null, 'local_obf');
        upgrade_plugin_savepoint(true, 2015070600, 'local', 'obf');
    }
    if ($oldversion < 2015120301) {

        // Define field use_addendum to be added to local_obf_criterion.
        $table = new xmldb_table('local_obf_criterion');
        $field = new xmldb_field('use_addendum', XMLDB_TYPE_BINARY, null, null, null, null, null, 'completion_method');

        // Conditionally launch add field use_addendum.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $addendumfield = new xmldb_field('addendum', XMLDB_TYPE_TEXT, null, null, null, null, null, 'use_addendum');

        // Conditionally launch add field addendum.
        if (!$dbman->field_exists($table, $addendumfield)) {
            $dbman->add_field($table, $addendumfield);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2015120301, 'local', 'obf');
    }
    if ($oldversion < 2015121500) {

        // Define field use_addendum to be added to local_obf_criterion.
        $table = new xmldb_table('local_obf_email_templates');
        $field = new xmldb_field('link_text', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'body');

        // Conditionally launch add field use_addendum.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2015121500, 'local', 'obf');
    }
      if ($oldversion < 2016060301) {

        // Define table local_obf_backpack_sources to be created.
        $table = new xmldb_table('local_obf_backpack_sources');

        // Adding fields to table local_obf_backpack_sources.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('requirepersonaorg', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_obf_backpack_sources.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_obf_backpack_sources.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);

            $backpacksources = array();
            $obj = new stdClass();
            $obj->url = 'https://backpack.openbadges.org/displayer/';
            $obj->fullname = 'Backpack';
            $obj->shortname = 'moz';
            $obj->requirepersonaorg = 1;
            $backpacksources[] = clone($obj);
            $obj->url = 'https://openbadgepassport.com/displayer/';
            $obj->fullname = 'Open Badge Passport';
            $obj->shortname = 'obp';
            $obj->requirepersonaorg = 0;
            $backpacksources[] = clone($obj);
            foreach($backpacksources as $key => $backpacksource) {
                $newids[$obj->shortname] = $DB->insert_record('local_obf_backpack_sources', $backpacksource);
            }
        }
        $newids = $DB->get_records_menu('local_obf_backpack_sources', null, '', 'shortname,id');

        // Alter old backpack associations
        $bpetable = new xmldb_table('local_obf_backpack_emails');
        $bpefield = new xmldb_field('backpack_provider', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'groups');
        $dbman->change_field_precision($bpetable, $bpefield);
        // Update old backpack emails to use new provider definitions
        $DB->execute(
                'UPDATE {local_obf_backpack_emails} SET backpack_provider = IF(backpack_provider = 0, ?, ?)',
                array(
                    $newids['moz'],
                    $newids['obp']
                    )
                );

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2016060301, 'local', 'obf');
    }
    if ($oldversion < 2016062200) {

        $table = new xmldb_table('local_obf_backpack_sources');
        $field = new xmldb_field('requirepersonaorg', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'url');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        upgrade_plugin_savepoint(true, 2016062200, 'local', 'obf');
    }
    if ($oldversion < 2016081000) {
        // Define table local_obf_user_emails to be created.
        $table = new xmldb_table('local_obf_user_emails');

        // Adding fields to table local_obf_user_emails.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('token', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('verified', XMLDB_TYPE_BINARY, null, null, null, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_obf_user_emails.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('idx_local_obf_user_email', XMLDB_KEY_UNIQUE, array('user_id', 'email'));

        // Conditionally launch create table for local_obf_user_emails.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2016081000, 'local', 'obf');
    }
    if ($oldversion < 2016090700) {
        // Define table local_obf_user_emails to be created.
        $table = new xmldb_table('local_obf_backpack_sources');
        $oldfield = $table->add_field('requirepersonaorg', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, 'configureableaddress')) {
            $dbman->rename_field($table, $oldfield, 'configureableaddress');
        }

        // Obf savepoint reached.
        upgrade_plugin_savepoint(true, 2016090700, 'local', 'obf');
    }
    if ($oldversion < 2016121900) {
        require_once(__DIR__ . '/../class/client.php');
        if (class_exists('obf_client') && 
                method_exists('obf_client', 'get_client_info') && 
                method_exists('obf_client', 'get_branding_image_url')
        ) {
            try {
                if (obf_client::has_client_id()) {
                    $client = obf_client::get_instance();
                    $client_info = $client->get_client_info();
                    $images = array('verified_by', 'issued_by');
                    set_config('verified_client', $client_info['verified'] == 1, 'local_obf');
                    foreach($images as $imagename) {
                        $imageurl = $client->get_branding_image_url($imagename);
                        set_config($imagename . '_image_url', $imageurl, 'local_obf');
                    }
                }
            } catch (Exception $ex) {
            }
        }
        upgrade_plugin_savepoint(true, 2016121900, 'local', 'obf');
    }
    if ($oldversion < 2017010900) {
        set_config('displaymoodlebadges', 0, 'local_obf');
        upgrade_plugin_savepoint(true, 2017010900, 'local', 'obf');
    }
    if ($oldversion < 2017060800) {
        set_config('apidataretrieve', 'local', 'local_obf');
        upgrade_plugin_savepoint(true, 2017060800, 'local', 'obf');
    }
    return true;
}
