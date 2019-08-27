<?php

namespace local_obf\task;
use \obf_backpack;
use stdClass;

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
            $content = new stdClass();
            if($pack) {
                $usermail = $records[$pack->get_user_id()]->email;
                $record = $DB->get_record('local_obf_deleted_emails',
                    array('user_id' => $pack->get_user_id(),'email' => $usermail));
                if ($pack->get_email() != $usermail) {
                    if (!$pack->requires_email_verification()){
                        if(!$record) {
                            $content->user_id = $pack->get_user_id();
                            $content->email = $usermail;
                            $content->timestamp = time();
                            $DB->insert_record('local_obf_deleted_emails', $content);
                        }
                        $pack->disconnect();
                    }
                }
            }
        }
    }
}
