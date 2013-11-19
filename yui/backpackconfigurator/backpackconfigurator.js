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
            Y.one('input.verifyemail').on('click', this.connect, this);
        },

        connect: function (evt) {
            evt.preventDefault();

            navigator.id.get(Y.bind(function (assertion) {
                // User cancelled
                if (assertion === null) {
                    return;
                }

                Y.io(this.config.url, {
                    data: { assertion: assertion },
                    on: { complete: Y.bind(this.assertion_validated, this) }
                });
            }, this));
        },

        assertion_validated: function (transactionid, xhr, args) {
            var response = JSON.parse(xhr.responseText);

            // Everything's ok -> redirect
            if (response.error === '') {
                window.location.reload();
            }
            else {
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