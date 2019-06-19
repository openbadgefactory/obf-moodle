<?php

namespace local_obf\task;
use \obf_backpack;

class user_email_changer extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('useremailupdater', 'local_obf');
    }

    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/obf/class/backpack.php');

        $users = obf_backpack::get_user_ids_with_backpack();
        $records = $DB->get_records_list('user', 'id',
            $users, '', 'id, email');

        foreach ($users as $user) {
            $pack = obf_backpack::get_instance_by_userid($user, $DB);
            if($pack) {
                if ($pack->get_email() != $records[$pack->get_user_id()]->email) {
                    if (!$pack->requires_email_verification()){
                        $pack->disconnect();
                    }
                }
            }
        }
    }
}
