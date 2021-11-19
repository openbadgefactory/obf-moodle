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
 * @package    block_obf_displayer
 * @copyright  2020, Open Badge Factory Oy Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/local/obf/class/backpack.php');
require_once($CFG->dirroot . '/local/obf/class/badge.php');
require_once($CFG->dirroot . '/local/obf/renderer.php');

/**
 * OBF displayer block.
 *
 * @copyright  2020, Open Badge Factory Oy Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_obf_displayer extends block_base {
    /**
     * Initialize the block.
     */
    public function init() {
        $this->title = get_string('obf_displayer', 'block_obf_displayer');
    }

    /**
     * Allow multiple instances
     * @return boolean true
     */
    public function instance_allow_multiple() {
      return true;
    }

    protected function get_badge_displaytype() {
        return !empty($this->config) && property_exists($this->config, 'displaytype') && $this->config->displaytype == 'loggedinuser' ? 'loggedinuser' : 'contextuser';
    }
    /**
     * Get content.
     * @return stdClass
     */
    public function get_content() {
        global $DB, $PAGE, $USER;
        if ($this->content !== null) {
            return $this->content;
        }
        
        if ($this->get_badge_displaytype() == 'contextuser') {
            $context = $PAGE->context;
            $this->title = get_string('displaycontextuser_blocktitle', 'block_obf_displayer');
        } else if(isloggedin()) {
            $context = context_user::instance($USER->id);
            $this->title = get_string('displayloggedinuser_blocktitle', 'block_obf_displayer');
        } else {
            return false;
        }

        if ($context->contextlevel !== CONTEXT_USER || ($this->get_badge_displaytype() == 'contextuser' && $PAGE->pagetype !== 'user-profile')) {
            return false;
        }

        $userid = $context->instanceid;

        $assertions = $this->get_assertions($userid, $DB);

        

        $this->content = new stdClass;
        $this->content->text = '';
        $renderer = $PAGE->get_renderer('local_obf');
        $large = !empty($this->config) && property_exists($this->config, 'largebadges') && $this->config->largebadges == true;
        if ($assertions !== false && count($assertions) > 0) {
            $this->content->text .= $renderer->render_user_assertions($assertions, $userid, $large);
        }
        $providers = obf_backpack::get_providers();
        foreach ($providers as $provider) {
            $assertions = $this->get_backpack_assertions($userid, $DB, $provider);
            if (count($assertions) > 0) {
                $this->content->text .= $renderer->render_user_assertions($assertions, $userid, $large);
            }
        }
        $moodle_assertions = $this->get_moodle_assertions($userid);
        if (count($moodle_assertions) > 0) {
            $this->content->text .= $renderer->render_user_assertions($moodle_assertions, $userid, $large);
        }

        return $this->content;
    }
    
    private function is_cache_assertions_enabled() {
        return (!isset($this->config) || !isset($this->config->disableassertioncache)) || !$this->config->disableassertioncache;
    }
    /**
     * Get assertions.
     * @param int $userid
     * @param moodle_database $db
     * @return obf_assertion_collection
     */
    private function get_assertions($userid, $db) {
        $clientid = obf_client::get_instance()->client_id();
        if (!empty($clientid) && (empty($this->config) || !property_exists($this->config, 'showobf') || $this->config->showobf)) {
            $cache = cache::make('block_obf_displayer', 'obf_assertions');
            $assertions = !$this->is_cache_assertions_enabled() ? null : $cache->get($userid);
            $deletedemailscount = $db->count_records('local_obf_history_emails', array('user_id' => $userid));
            $deletedemails = $db->get_records('local_obf_history_emails', array('user_id' => $userid), '', 'email');

            $deleted = array();
            foreach ($deletedemails as $key => $email) {
                $deleted[] = $key;
            }

            if (!$assertions) {
                // Get user's badges in OBF.
                $assertions = new obf_assertion_collection();
                try {
                    $client = obf_client::get_instance();
                    $blacklist = new obf_blacklist($userid);

                    //Get badges issued with previous emails
                    if ($deletedemailscount >= 1) {
                        foreach ($deleted as $email) {
                            $assertions->add_collection(obf_assertion::get_assertions_all($client, $email));
                        }
                    }

                    $assertions->add_collection(obf_assertion::get_assertions_all($client, $db->get_record('user', array('id' => $userid))->email));
                    $assertions->apply_blacklist($blacklist);


                } catch (Exception $e) {
                    debugging('Getting OBF assertions for user id: ' . $userid . ' failed: ' . $e->getMessage());
                }

                $assertions->toArray(); // This makes sure issuer objects are populated and cached.
                $cache->set($userid, $assertions );
            }
        } else {
            $assertions = new obf_assertion_collection();
        }
        return $assertions;
    }
    
    /**
     * Get assertions.
     * @param int $userid
     * @return obf_assertion_collection
     */
    private function get_moodle_assertions($userid) {
        global $CFG;
        $badgeslib_file = $CFG->libdir.'/badgeslib.php';
        
        $assertions = new obf_assertion_collection();
        if (file_exists($badgeslib_file) && (empty($this->config) || !property_exists($this->config, 'showmoodle') || $this->config->showmoodle)) {
            require_once($badgeslib_file);
            $assertions->add_collection(obf_assertion::get_user_moodle_badge_assertions($userid));
        }
        return $assertions;
    }
    /**
     * Get backpack assertions.
     * @param int $userid
     * @param moodle_database $db
     * @param int $provider
     * @return obf_assertion_collection
     */
    private function get_backpack_assertions($userid, $db, $provider) {
        $backpack = obf_backpack::get_instance_by_userid($userid, $db, $provider);
        if ($backpack === false || count($backpack->get_group_ids()) == 0) {
            return new obf_assertion_collection();
        }
        $shortname = $backpack->get_providershortname();
        $showprop = 'show'.$shortname;
        if (empty($this->config) || !property_exists($this->config, $showprop) || $this->config->{$showprop}) {
            $cache = cache::make('block_obf_displayer', 'obf_assertions_backpacks');
            $userassertions = !$this->is_cache_assertions_enabled() ? null : $cache->get($userid);

            if (!$userassertions || !array_key_exists($shortname, $userassertions)) {
                if (!is_array($userassertions)) {
                    $userassertions = array();
                }
                // Get user's badges in OBF.
                $assertions = new obf_assertion_collection();
                try {
                    // Also get user's badges in Backpack, if user has backpack settings.
                    if ($backpack !== false && count($backpack->get_group_ids()) > 0) {
                        $assertions->add_collection( $backpack->get_assertions() );
                    }
                } catch (Exception $e) {
                    debugging('Getting backpack assertions for user id: ' . $userid . ' failed: ' . $e->getMessage());
                }

                $assertions->toArray(); // This makes sure issuer objects are populated and cached.
                $userassertions[$shortname] = $assertions;
                $cache->set($userid, $userassertions );
            }
        } else {
            $userassertions[$shortname] = new obf_assertion_collection();
        }
        return $userassertions[$shortname];
    }
    /**
     * Has config?
     * @return boolean True
     */
    public function has_config() {
        return false;
    }
    /**
     * HTML Attributes.
     * @return array
     */
    public function html_attributes() {
        $attributes = parent::html_attributes();
        $attributes['class'] .= ' local-obf';
        return $attributes;
    }
}
