OBF Moodle plugin
=================

This project user Composer to manage dependencies. If you don't have Composer
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

OBF Displayer plugin
--------------------

[OBF Displayer plugin README](src/blocks/obf_displayer/README.md)

How to install
--------------

Moodle 2.5:

1. Install the zip via Moodle's plugin page. Select "local" as the type of the plugin.
2. Update the database using the notifications page

Moodle 2.2:

1. Unzip the archive to /[MoodleDir]/local/
2. Give write permissions to web server user on /[MoodleDir]/local/obf/pki
   directory, for example `sudo chown www-data:www-data /[MoodleDir]/local/obf/pki`
3. Update the database using the notifications page

