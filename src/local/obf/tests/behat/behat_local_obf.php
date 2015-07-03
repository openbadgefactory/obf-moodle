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
 * Plugin configuration page.
 *
 * @package    local_obf
 * @copyright  2013-2015, Discendum Oy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use Behat\Behat\Context\Step\Given;
use Behat\Behat\Event\FeatureEvent;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Session;

class behat_local_obf extends behat_base {

    /**
     * @AfterFeature
     */
    public static function teardownFeature(FeatureEvent $event) {
        require_once(__DIR__ . '/../../class/client.php');
        try {
            obf_client::get_instance()->delete_badges();
        } catch (Exception $e) {
            // TODO: Do something?
        }

    }

    private static function iCreatePkiDir() {
        global $CFG;
        $newpkidir = $CFG->behat_dataroot . '/local_obf/pki/';

        if (!is_dir($newpkidir)) {
            mkdir($newpkidir, $CFG->directorypermissions, true);
        }
    }
    /**
     * @Given /^I enter a valid request token to "([^"]*)"$/
     */
    public function iEnterAValidRequestTokenTo($fieldname) {
        self::iCreatePkiDir();
        $session = $this->getSession();
        $seleniumsession = new Session(new Selenium2Driver());
        $seleniumsession->start();

        $seleniumsession->visit('https://elvis.discendum.com/obf/');
        $seleniumsession->getPage()->fillField('username', 'behat@example.com');
        $seleniumsession->getPage()->fillField('password', 'behat');
        $seleniumsession->getPage()->pressButton('Login');
        $seleniumsession->getPage()->clickLink('Admin tools');
        $seleniumsession->wait(1000);
        $seleniumsession->getPage()->clickLink('API key');
        $seleniumsession->wait(1000);
        $seleniumsession->getPage()->clickLink('Generate certificate signing request token');

        $seleniumsession->wait(5000, "$('#csrtoken-out textarea').length > 0");

        $textarea = $seleniumsession->getPage()->find('css', '#csrtoken-out textarea');
        $token = $textarea->getValue();
        $seleniumsession->stop();

        $session->getPage()->fillField($fieldname, $token);
    }

    /**
     * @Given /^the following badges exist:$/
     */
    public function theFollowingBadgesExist(TableNode $badgetable) {
        $steps = array();

        foreach ($badgetable->getHash() as $hash) {

            $name = $hash['Name'];
            $desc = $hash['Description'];
            $issuer = $hash['issuername'];
            $table = new TableNode(<<<TABLE
                | Name        | $name   |
                | Description | $desc   |
                | issuername  | $issuer |
TABLE
            );

            $steps[] = new Given('I expand "Site administration" node');
            $steps[] = new Given('I expand "Badges" node');
            $steps[] = new Given('I follow "Add a new badge"');
            $steps[] = new Given('I fill the moodle form with:', $table);
            $steps[] = new Given('I upload "' . $hash['image'] . '" file to "Image" filepicker');
            $steps[] = new Given('I press "Create badge"');
        }

        $steps[] = new Given('I expand "Open Badges" node');
        $steps[] = new Given('I follow "Settings"');
        $steps[] = new Given('I enter a valid request token to "obftoken"');
        $steps[] = new Given('I press "Save changes"');

        foreach ($badgetable->getHash() as $hash) {
            $steps[] = new Given('I check "' . $hash['Name'] . '"');
        }

        $steps[] = new Given('I check "Make exported badges visible by default"');
        $steps[] = new Given('I press "Continue"');

        return $steps;
    }

    /**
     * This step triggers cron like a user would do going to admin/cron.php.
     *
     * @Given /^I trigger cron$/
     */
     /*
      * public function iTriggerCron() {
      *     $this->getSession()->visit($this->locate_path('/admin/cron.php'));
      * }
      */

    /**
     * @Given /^I go to badge list$/
     */
    public function iGoToBadgeList() {
        return array(
            new Given('I am on homepage'),
            new Given('I expand "Site administration" node'),
            new Given('I expand "Open Badges" node'),
            new Given('I follow "Badge list"')
        );
    }

    /**
     * @Given /^I set "([^"]*)" to be completed when assignment "([^"]*)" is completed$/
     */
    public function iSetToBeCompletedWhenAssignmentIsCompleted($course, $assignment) {
        return array(
            new Given('I am on homepage'),
            new Given('I follow "' . $course . '"'),
            new Given('I follow "Edit settings"'),
            new Given('I fill the moodle form with:',
                    new TableNode(<<<TABLE
                | Enable completion tracking | Yes |
TABLE
                    )),
            new Given('I press "Save changes"'),
            new Given('I turn editing mode on'),
            new Given('I add a "Assignment" to section "1" and I fill the form with:',
                    new TableNode(<<<TABLE
                | Assignment name                     | $assignment            |
                | Description                         | Assignment description |
                | assignsubmission_onlinetext_enabled | 1                      |
TABLE
                    )),
            new Given('I follow "Course completion"'),
            new Given('I select "2" from "id_overall_aggregation"'),
            new Given('I click on "Condition: Activity completion" "link"'),
            new Given('I check "Assign - ' . $assignment . '"'),
            new Given('I press "Save changes"'));
    }

    /**
     * @Given /^I mark "([^"]*)" of "([^"]*)" completed by "([^"]*)"$/
     */
    public function iMarkOfCompletedBy($assignment, $course, $user) {
        return array(
            new Given('I log in as "' . $user . '"'),
            new Given('I follow "' . $course . '"'),
            new Given('I press "Mark as complete: ' . $assignment . '"'),
            new Given('I wait "3" seconds'),
            new Given('I log out')
        );
    }
}
