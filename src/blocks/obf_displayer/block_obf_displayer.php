<?php
require_once $CFG->dirroot . '/local/obf/class/backpack.php';
require_once $CFG->dirroot . '/local/obf/class/badge.php';
require_once $CFG->dirroot . '/local/obf/renderer.php';

class block_obf_displayer extends block_base {
    public function init() {
        $this->title = get_string('obf_displayer', 'block_obf_displayer');
    }

    public function get_content() {
        global $DB, $PAGE;
        if ($this->content !== null) {
            return $this->content;
        }
        $context = $PAGE->context;

        if ($context->contextlevel !== CONTEXT_USER || $PAGE->pagetype !== 'user-profile') {
            return false;
        }

        $userid = $context->instanceid;

        $assertions = $this->get_assertions($userid, $DB);


        $this->content =  new stdClass;
        $this->content->text = '';
        $renderer = $PAGE->get_renderer('local_obf');
        if ($assertions !== false && count($assertions) > 0) {
            $this->content->text = $renderer->render_user_assertions($assertions);
        }

        return $this->content;
    }
    private function get_assertions($userid, $db) {
        $cache = cache::make('block_obf_displayer', 'obf_assertions');
        $assertions = $cache->get($userid);

        if (!$assertions) {
            // Get user's badges in OBF
            $assertions = new obf_assertion_collection();
            if ($this->config->showobf) {
                try {
                    $client = obf_client::get_instance();
                    $assertions->add_collection(obf_assertion::get_assertions($client, null, $db->get_record('user', array('id' => $userid))->email ));
                } catch(Exception $e) {
                    debugging('Getting OBF assertions for user id: ' . $userid . ' failed: ' . $e->getMessage());
                }
            }

            if ($this->config->showbackpack) {
                try {
                    // Also get user's badges in Backpack, if user has backpack settings
                    $backpack = obf_backpack::get_instance_by_userid($userid, $db);
                    if ($backpack !== false && count($backpack->get_group_ids()) > 0) {
                        $assertions->add_collection( $backpack->get_assertions() );
                    }
                } catch(Exception $e) {
                    debugging('Getting backpack assertions for user id: ' . $userid . ' failed: ' . $e->getMessage());
                }
            }

            $assertions->toArray(); // This makes sure issuer objects are populated and cached
            $cache->set($userid, $assertions );
        }
        return $assertions;
    }
}
