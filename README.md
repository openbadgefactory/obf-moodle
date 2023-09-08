OBF Moodle/Totara plugin
=================

## NOTE
This repository is archived. For latest plugin versions please see:

- https://github.com/openbadgefactory/moodle-local_obf
- https://github.com/openbadgefactory/moodle-block_obf_displayer

----------------


This project is for 2 Open Badge Factory Moodle/Totara -plugins.

- [OBF Issuer plugin README](src/local/obf/README.md)
- [OBF Displayer plugin README](src/blocks/obf_displayer/README.md)

For developers
--------

This project uses Composer to manage dependencies. If you don't have Composer
installed, run the following command to install it:

    curl -sS https://getcomposer.org/installer | php

And then, install the project dependencies using Composer:

    php composer.phar install

Building
--------

Build task creates a zip-file to project's build-directory. Building the plugin
is as easy as running the following command in project directory:

    vendor/bin/phing

Testing
-------

The plugin has a few unit tests (there should be more and the current ones
should cover more). To test the plugin, you need to have Moodle installed and
it's test environment initialized. To initialize Moodle's test environment, set
the PHPUnit-related configuration values (mainly `$CFG->phpunit_prefix` and
`$CFG->phpunit_dataroot` in `/[MoodleDir]/config.php` and run the following
commands:

    cd /[MoodleDir]
    php admin/tool/phpunit/cli/init.php

When the test environment is initialized, the tests are run using command

    $ vendor/bin/phing test

**Behat**

There are also a few acceptance tests in tests-directory created using Behat,
but running them doesn't serve any purpose. They are done mostly to test Behat
and Selenium.

How to install (issuer plugin)
--------------

Moodle 2.7 and up:

1. Install the zip via Moodle's plugin page. Select "local" as the type of the plugin.
2. Update the database using the notifications page
3. Complete the [Post install steps](README.md#post-install)

Totara 11.0 and greater

Totara Learn does not include an add-on installer, all additional plugins must be installed manually by server administrators. 

1. Download plugin from https://moodle.org/plugins/local_obf
2. Unzip the file into the Totara installation directory. 
3. By using a site administrator account, go to Site administration → Notifications and upgrade Totara database


Post install
------------

To connect to Open Badge Factory, the plugin needs a request token or API key.

To generate the required API key, log in to [Open Badge Factory](https://openbadgefactory.com).
When logged in, navigate to `Admin tools > API key`.
On the API key -page click on `Generate certificate signing request token`.

Copy the generated token into OBF Moodle plugin settings,
in `Site administration > Open Badges > Settings`.
