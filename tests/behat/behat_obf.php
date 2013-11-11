<?php
#    Then I copy the request token
#    Then I go to "https://elvis.discendum.com/obf/"
#    And I fill in "form-inline" with:
#        | username | test |
#        | password | test |
#    And I press "Login"
#    And I follow "Admin tools"
#    And I follow "Edit Organisation Details"
#    And I follow "More settings"
#    And I follow "Generate certificate signing request token"

class behat_obf extends behat_base {
    /**
     * @Given /^I enter a valid request token to "([^"]*)"$/
     */
    public function iEnterAValidRequestTokenTo($fieldname)
    {
        $session = $this->getSession();
        $seleniumsession = new \Behat\Mink\Session(new \Behat\Mink\Driver\Selenium2Driver('chrome'));
        $seleniumsession->start();

        $seleniumsession->visit('https://elvis.discendum.com/obf/');
        $seleniumsession->getPage()->fillField('username', 'test');
        $seleniumsession->getPage()->fillField('password', 'test');
        $seleniumsession->getPage()->pressButton('Login');
        $seleniumsession->getPage()->clickLink('Admin tools');
        $seleniumsession->getPage()->clickLink('Edit Organisation Details');
        $seleniumsession->getPage()->clickLink('More settings');
        $seleniumsession->getPage()->clickLink('Generate certificate signing request token');

        $seleniumsession->wait(5000, "$('#csrtoken-out textarea').length > 0");

        $textarea = $seleniumsession->getPage()->find('css', '#csrtoken-out textarea');
        $token = $textarea->getValue();
        $seleniumsession->stop();

        $session->getPage()->fillField($fieldname, $token);
    }

}