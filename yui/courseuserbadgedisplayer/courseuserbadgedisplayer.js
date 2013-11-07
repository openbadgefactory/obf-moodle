YUI.add('moodle-local_obf-courseuserbadgedisplayer', function(Y) {
    var DISPLAYERNAME = 'obf-courseuserbadgedisplayer';
    var COURSEUSERBADGEDISPLAYER = function() {
        COURSEUSERBADGEDISPLAYER.superclass.constructor.apply(this, arguments);
    };

    Y.extend(COURSEUSERBADGEDISPLAYER, Y.Base, {
        /**
         * Module configuration
         */
        config: null,

        /**
         * Panel that displays a single assertion
         */
        panel: null,

        /**
         * Assertion cache
         */
        assertions: {},

        /**
         * Precompiled templates
         */
        templates: {},

        /**
         * Module initializer
         *
         * @param Object config
         */
        initializer: function(config) {
            Y.mix(Y.Template.Micro.options, {
                code: /\{\{%([\s\S]+?)%\}\}/g,
                escapedOutput: /\{\{(?!%)([\s\S]+?)\}\}/g,
                rawOutput: /\{\{\{([\s\S]+?)\}\}\}/g
            }, true);

            this.config = config;

            // compile templates
            var micro = new Y.Template();
            this.templates.assertion = micro.compile(unescape(this.config.tpl.assertion));
            this.templates.badge = micro.compile(unescape(this.config.tpl.badge));

            // do it!
            this.process();
        },
        process: function() {
            var table = Y.one('table#obf-participants');

            if (!table) {
                return;
            }

            this.panel = new Y.Panel({
                id: 'obf-assertion-panel',
                headerContent: '',
                centered: true,
                modal: true,
                visible: false,
                width: 600,
                render: true,
                zIndex: 10
            });

            // Show badges of a participants
            table.delegate('click', function(e) {
                e.preventDefault();

                var node = e.currentTarget;
                var parentrow = node.ancestor('tr');

                this.toggle_badge_row(parentrow);
            }, 'td.show-badges a', this);

            // Display a single badge
            table.delegate('click', function(e) {
                e.preventDefault();

                var node = e.currentTarget;
                var data = this.assertions[node.generateID()];

                this.panel.set('bodyContent', this.templates.assertion(data));
                this.panel.set('headerContent', data.badge.name);
                this.panel.show();
            }, 'ul.badgelist li', this);
        },
        toggle_badge_row: function(row) {
            var badgerow = row.next();

            if (!badgerow || !badgerow.hasClass('badge-row')) {
                var target = row.one('td.show-badges');
                var spinner = M.util.add_spinner(Y, target);
                var cellcount = row.all('td').size();
                var badgecell = Y.Node.create('<td></td>').setAttribute('colspan', cellcount);
                var userid = row.generateID().split('-')[1];

                badgerow = Y.Node.create('<tr></tr>').append(badgecell).addClass('badge-row').
                        setStyle('display', 'none');
                row.insert(badgerow, 'after');

                spinner.show();

                this.insert_badges(userid, badgecell, function() {
                    badgerow.toggleView();
                    spinner.hide();
                });

            }
            else {
                badgerow.toggleView();
            }

        },
        insert_badges: function(userid, cell, callback) {
            Y.io(this.config.url, {
                data: {userid: userid},
                on: {complete: Y.bind(this.receive_badges, this)},
                arguments: {cell: cell, callback: callback, userid: userid}
            });
        },
        receive_badges: function(transactionid, xhr, args) {
            var assertions = JSON.parse(xhr.responseText);
            var cell = args.cell;
            var micro = new Y.Template();
            var html = '';

            Y.Array.each(assertions, Y.bind(function(assertion, index) {
                assertion.id = args.userid + '-' + index;
                html += this.templates.badge(assertion);

                this.assertions[assertion.id] = assertion;
            }, this));

            cell.setHTML(micro.render(unescape(this.config.tpl.list), {content: html}));
            args.callback();
        }
    }, {
        NAME: DISPLAYERNAME,
        ATTRS: {
            aparam: {}
        }
    });

    M.local_obf = M.local_obf || {};
    M.local_obf.init_courseuserbadgedisplayer = function(config) {
        return new COURSEUSERBADGEDISPLAYER(config);
    };
}, '@VERSION@', {requires: ['io-base', 'json-parse', 'template', 'panel']});