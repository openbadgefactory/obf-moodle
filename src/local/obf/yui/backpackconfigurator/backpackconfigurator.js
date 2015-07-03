YUI.add('moodle-local_obf-backpackconfigurator', function(Y) {
    var BACKPACKCONFIGURATORNAME = 'obf-backpackconfigurator';
    var BACKPACKCONFIGURATOR = function() {
        BACKPACKCONFIGURATOR.superclass.constructor.apply(this, arguments);
    };

    Y.extend(BACKPACKCONFIGURATOR, Y.Base, {
        config: null,

        /**
         * Module initializer
         *
         * @param Object config
         */
        initializer: function(config) {
            this.config = config;
            Y.all('input.verifyemail').on('click', this.connect, this);
            Y.all('input.verifyobpemail').on('click', this.connect, this);
        },

        /**
         * Performs authentication using Persona, sends returned assertion to
         * server and validates the email via backpack.
         *
         * @param {type} evt
         * @returns {undefined}
         */
        connect: function (evt) {
            evt.preventDefault();
            var provider = evt.target.getAttribute('data-provider');

            navigator.id.get(Y.bind(function (assertion) {
                // User cancelled
                if (assertion === null) {
                    return;
                }

                Y.io(this.config.url, {
                    data: { assertion: assertion, provider: provider },
                    on: { complete: Y.bind(this.assertion_validated, this) }
                });
            }, this));
        },

        /**
         * Called when the assertion is successfully validated. Reloads the page
         * on success and displays an error message on failure.
         *
         * @param {type} transactionid
         * @param {type} xhr
         * @param {type} args
         * @returns {undefined}
         */
        assertion_validated: function (transactionid, xhr, args) {
            var response = JSON.parse(xhr.responseText);

            // Everything's ok -> redirect
            if (response.error === '') {
                window.location.reload();
            } else {
                window.alert(response.error);
            }
        }
    }, {
        NAME: BACKPACKCONFIGURATORNAME,
        ATTRS: {
            aparam: {}
        }
    });

    M.local_obf = M.local_obf || {};
    M.local_obf.init_backpackconfigurator = function(config) {
        return new BACKPACKCONFIGURATOR(config);
    };
}, '@VERSION@', { requires: ['base', 'io-base', 'json-parse'] });
