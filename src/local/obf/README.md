Open Badge Factory -plugin
=================

Open Badge Factory is a cloud platform that provides the tools your organization needs to implement a meaningful and sustainable Open Badges system.

With the local_obf plugin you can issue Open Badges created in Open Badge Factory. To use the plugin, you need an account on [https://openbadgefactory.com](https://openbadgefactory.com) (You can register for free, see [https://openbadgefactory.com/faq](https://openbadgefactory.com/faq) for details about different service levels).

For developers
--------

[See the project README](../../../README.md)

How to install
--------------

Moodle 2.7, 2.9, 3.0, 3.1 and up:

1. Install the zip via Moodle's plugin page. Select "local" as the type of the plugin. (alternative: unzip to moodle's local subdirectory)
2. Update the database using the notifications page
3. Complete the [Post install steps](README.md#post-install)

Totara 11.0 and greater

Totara Learn does not include an add-on installer, all additional plugins must be installed manually by server administrators.

1. Download plugin from https://moodle.org/plugins/local_obf
2. Unzip the file into the Totara installation directory.
3. By using a site administrator account, go to Site administration → Notifications and upgrade Totara database
4. Complete the [Post install steps](README.md#post-install)

Post install
------------------

To connect to Open Badge Factory, the plugin needs a request token or API key.

To generate the required API key, log in to Open Badge Factory. When logged in, navigate to `Admin tools > API`.

Legacy key:

On the API key -page click on `Generate certificate signing request token` for legacy type key. Copy the generated token into OBF Moodle plugin settings, in `Site administration > Open Badges > Settings`.

OAuth2 key:

Pro level clients can also connect with OAuth2. This supports multiple clients on one Moodle installation.

On the API key -page click on `Generate new client secret` for OAuth2 Client Credentials. Give a description for the key and copy the client id and secret values into OBF Moodle plugin settings, in `Site administration > Open Badges > Settings`.

Changelog
------------------

0.5.5

- API auth fixes

0.5.4

- PostgreSQL query fix
- Replaced array\_key\_first function

0.5.3

- PostgreSQL query fix

0.5.2

- Fixed warnings for missing page context
- Fixed api call for all user badges when using legacy connection

0.5.1

- Connect multiple Factory clients with OAuth2
- Awarding rules bug fixes
- Other minor fixes and improvements

0.4

- Fixed problem with Moodle 3.10.1
- Added support for Totara program and certications
