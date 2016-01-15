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
 * Page for displaying badge earning criteria.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/class/badge.php');

$badgeid = required_param('badge_id', PARAM_ALPHANUM);

require_login();
// TODO: capabilities?

$badge = obf_badge::get_instance($badgeid);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/obf/criteriapreview.php', array('badge_id' => $badgeid)));
$PAGE->set_title(get_string('criteriapreview', 'local_obf'));
$PAGE->set_pagelayout('popup');
$criteriacss = $badge->get_criteria_css();
$badgehascss = !empty($criteriacss);
$xhrrequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if (!$xhrrequest) {
    ?>
    <html>
        <head>
            <title><?php echo get_string('criteriapreview', 'local_obf') ?></title>
    <?php
    if ($badgehascss) {
        html_writer::tag('style', $badge->get_criteria_css(), array('type' => 'text/css'));
    } else {
        ?>
        <style type="text/css">
            body { background-color: #FFF; font-family: "Source Sans Pro",sans-serif; color: #333; margin: 75px auto;
            width: 800px; border: 1px solid #CCC; padding: 10px; border-radius: 3px;
            box-shadow: 4px 4px 10px 2px rgba(80, 80, 80, 0.4); }
        </style>
        <?php
    }
    ?>
        </head>

        <body class='local-obf criteria-page'>
            <?php echo $badge->get_criteria_html(); ?>
        </body>
    </html>
    <?php
} else {
    echo $badge->get_criteria_html();
}
