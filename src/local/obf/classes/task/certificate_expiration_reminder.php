<?php

/*
 * Copyright (c) 2015 Discendum Oy

 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.

 */

namespace local_obf\task;
use \obf_client;
use \stdClass;

/**
 * Description of certificate_expiration_reminder
 *
 * @author jsuorsa
 */
class certificate_expiration_reminder extends \core\task\scheduled_task  {
    public function get_name() {
        // Shown in admin screens
        return get_string('certificateexpirationremindertask', 'local_obf');
    }

    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . '/local/obf/class/client.php');
        require_once($CFG->libdir . '/messagelib.php');
        require_once($CFG->libdir . '/datalib.php');

        $certexpiresin = obf_client::get_instance()->get_certificate_expiration_date();
        $diff = $certexpiresin - time();
        $days = floor($diff / (60 * 60 * 24));

        // Notify only if there's certain amount of days left before the certification expires.
        $notify = in_array($days, array(30, 25, 20, 15, 10, 5, 4, 3, 2, 1));

        if (!$notify) {
            return true;
        }

        $severity = $days <= 5 ? 'errors' : 'notices';
        $admins = get_admins();
        $textparams = new stdClass();
        $textparams->days = $days;
        $textparams->obfurl = obf_client::get_site_url();
        $textparams->configurl = (string)(new \moodle_url('/local/obf/config.php'));

        foreach ($admins as $admin) {
            $eventdata = new stdClass();
            $eventdata->component = 'moodle';
            $eventdata->name = $severity;
            $eventdata->userfrom = $admin;
            $eventdata->userto = $admin;
            $eventdata->subject = get_string('expiringcertificatesubject',
                    'local_obf');
            $eventdata->fullmessage = get_string('expiringcertificate', 'local_obf',
                    $textparams);
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = get_string('expiringcertificate',
                    'local_obf', $textparams);
            $eventdata->smallmessage = get_string('expiringcertificatesubject',
                    'local_obf');

            $result = message_send($eventdata);
        }

        return true;
    }

}
