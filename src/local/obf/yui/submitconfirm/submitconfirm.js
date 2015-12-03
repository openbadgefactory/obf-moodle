YUI.add('moodle-local_obf-submitconfirm', function(Y) {
    var SUBMITCONFIRMNAME = 'obf-submitconfirm';
    var SUBMITCONFIRM = function() {
        SUBMITCONFIRM.superclass.constructor.apply(this, arguments);
    };

    Y.extend(SUBMITCONFIRM, Y.Base, {
        /**
         * Module config
         */
        config: null,
        /**
         * Module initializer
         *
         * @param Object config
         */
        initializer: function(config) {
            this.config = config;
            M.local_obf.submitconfirmlisteners.push(Y.one('.' + config.class).on('click', this.confirm_click, this));
        },
        confirm_click: function(e) {
            e.preventDefault();
            var confirm = new M.core.confirm(this.config);
            confirm.on('complete-yes', function () {
                new Y.EventHandle(M.local_obf.submitconfirmlisteners).detach();
                e.currentTarget.simulate('click');
            }, this);
            confirm.show();
        }
    }, {
        NAME: SUBMITCONFIRMNAME,
        ATTRS: {
            aparam: {}
        }
    });

    M.local_obf = M.local_obf || {};
    M.local_obf.submitconfirmlisteners = [];
    M.local_obf.init_submitconfirm = function(config) {
        return new SUBMITCONFIRM(config);
    };
}, '@VERSION@', {requires: ['base', 'node', 'node-event-simulate', 'moodle-core-notification-confirm']});
