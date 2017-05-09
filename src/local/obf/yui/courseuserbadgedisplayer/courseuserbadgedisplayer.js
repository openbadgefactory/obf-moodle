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
         * If true, we're dealing only with a single list of badges (one user, one backpack).
         */
        init_list_only: false,

        /**
         * ID of the element we are binding to.
         */
        elementid: null,
        /**
         * Base url for criteria pages, for cases where badges are locally issued.
         */
        criteria_baseurl: null,
        /**
         * Are badges listed here blacklistable, and should be blacklist button be shown?
         */
        blacklistable: true,

        /**
         * Url to blacklist badges.
         */
        blacklist_url: null,
        /**
         * Params to pass to blacklist url, when blacklisting badges.
         */
        blacklist_params: null,
        /**
         * Branding images to be displayed.
         */
        branding_urls: {},
        
        /**
         * Data source id for OBF
         */
        obf_data_source: -1,
        /**
         * Module initializer
         *
         * @param Object config
         */
        initializer: function(config) {
            this.config = config;
            this.assertions = config.assertions || {};
            this.init_list_only = config.init_list_only || false;
            this.elementid = config.elementid || null;
            this.criteria_baseurl = config.criteria_baseurl || null;
            this.blacklist_url = config.blacklist_url || null;
            this.blacklist_params = config.blacklist_params || {};
            this.blacklistable = config.blacklistable || false;
            this.branding_urls = config.branding_urls || false;

            // compile templates
            this.templates.assertion = this.compile_template(unescape(this.config.tpl.assertion));
            this.templates.badge = this.compile_template(unescape(this.config.tpl.badge));
            this.templates.list = this.compile_template(unescape(this.config.tpl.list));

            // do it!
            if (this.init_list_only) {
                this.process_single();
            } else {
                this.process();
            }
        },
        /**
         * Get panel width based on the window/display size.
         * @returns int Panel width
         */
        get_panel_width: function () {
            return Y.one('body').get('winWidth') >= 800 ? 800 : (Y.one('body').get('winWidth') * 0.9);
        },
        /**
         * Initializes the panel that displays the badges.
         *
         * @returns {undefined}
         */
        init_panel: function() {
            this.panel = new Y.Panel({
                id: 'obf-assertion-panel',
                headerContent: '',
                centered: true,
                modal: true,
                visible: false,
                width: this.get_panel_width(),
                render: true,
                zIndex: 10,
                buttons: [
                    {
                        value: M.util.get_string('closepopup', 'local_obf'),
                        action: function(e) {
                            e.preventDefault();
                            this.hide();
                        },
                        section: Y.WidgetStdMod.FOOTER
                    }
                ]
            });
            var hide_badge_button = {
                classNames: 'blacklist-badge',
                value : M.util.get_string('blacklistbadge', 'local_obf'),
                action: function(e) {
                    e.preventDefault();
                    var node = e.currentTarget;
                    var badgeurl = node.getAttribute('data-url');
                    window.location = badgeurl;
                },
                section: Y.WidgetStdMod.FOOTER
            };
            this.panel.addButton(hide_badge_button);
        },
        /**
         * Adds click observers for a single list of badges.
         *
         * @returns {undefined}
         */
        process_single: function() {
            this.init_panel();
            if (this.elementid !== null) {
                Y.one('ul#' + this.elementid + '.badgelist').delegate('click', this.display_badge, 'li', this);
            } else {
                Y.one('ul.badgelist').delegate('click', this.display_badge, 'li', this);
            }

        },
        /**
         * Adds click observers for a whole table of badges.
         *
         * @returns {undefined}
         */
        process: function() {
            var table = Y.one('table#obf-participants');

            if (!table) {
                return;
            }

            this.init_panel();

            // Show badges of a participants
            table.delegate('click', function(e) {
                e.preventDefault();

                var node = e.currentTarget;
                var parentrow = node.ancestor('tr');

                this.toggle_badge_row(parentrow);
            }, 'td.show-badges a', this);

            // Display a single badge
            table.delegate('click', this.display_badge, 'ul.badgelist li', this);
        },
        close_panel: function(e) { if (this.panel !== null) { this.panel.hide(); } },
        /**
         * Displays the information of a single badge.
         *
         * @param {type} e
         * @returns {undefined}
         */
        display_badge: function(e) {
            e.preventDefault();

            var node = e.currentTarget;
            var data = this.assertions[node.generateID()];

            data = this.data_conversions(data);

            this.panel.set('bodyContent', this.templates.assertion(data));
            this.panel.set('headerContent', null);

            this.setup_blacklist_button(data);
            this.setup_panel_branding(data);

            Y.one('body').delegate('click', this.display_criteria, '.view-criteria', this);
            this.panel.set('width', this.get_panel_width());

            this.panel.render().show().centered();
            Y.one('.yui3-widget-mask').detach('click', this.close_panel).once('click', this.close_panel, this );
        },
        setup_panel_branding: function (data) {
            // Do we want more flexible branding?
            var footer_classes = [];
            this.panel.footerNode.getAttribute('class').split(' ').forEach(
                    function(a) {
                        var pattern = /^assertion/i; if (!pattern.test(a)) { footer_classes.push(a); }
                    });
            var sourcename = 'unknown';
            if (data.source === 1) {
                sourcename = 'obf';
            } else if (data.source === 2) {
                sourcename = 'obp';
            }
            this.panel.footerNode.setAttribute('class', footer_classes + ' assertion-source-' + sourcename);
            if (this.branding_urls.hasOwnProperty(data.source)) {
                var url = this.branding_urls[data.source];
                this.panel.footerNode.setAttribute('style', 'background-image: url(\'' + url + '\');');
            } else {
                this.panel.footerNode.setAttribute('style', '');
            }
        },
        /**
         * Sets button visibility and adds badge ids and blacklist urls to button attributes.
         *
         * @returns {undefined}
         */
        setup_blacklist_button: function(data) {
            var footer = this.panel.get('footerContent').get('node')[0];
            var button = footer.one('.blacklist-badge');
            if (this.blacklistable && typeof button === "object" && data.source === this.obf_data_source && data.badge && data.badge.id.length > 1) {
                button.setAttribute('data-id', data.badge.id);
                var params = this.blacklist_params;
                params['badgeid'] = data.badge.id;
                var url = this.blacklist_url + '?' + Y.QueryString.stringify(params);
                button.setAttribute('data-url', url);
                button.removeClass('hide');
            } else if (typeof button === "object" && button !== null) {
                button.addClass('hide');
            }
        },
        /**
         * Displays the information of a single badge.
         *
         * @param {type} e
         * @returns {undefined}
         */
        display_criteria: function(e) {
            e.preventDefault();

            var node = e.currentTarget;
            var url = node.getAttribute('data-url');
            var badgeid = node.getAttribute('data-id');
            if (url.length > 0) {
                window.location = url;
            } else if (url.length === 0 && badgeid.length > 0) {
                url = this.criteria_baseurl + '?badge_id=' + badgeid;
                window.location = url;
            }

        },
        /**
         * Convert dates and such.
         */
        data_conversions: function(data) {
            var date_fields = ['issued_on', 'expires'];
            date_fields.forEach(function (val,idx) {
                if (this.hasOwnProperty(val)) {
                    if (val != "-" && typeof val == "string" && !isNaN(Number(this[val]))) {
                        var datems = parseInt(this[val], 10) * 1000;
                        if (!isNaN(datems)) {
                            this[val] = new Date(datems).toLocaleDateString();
                        }
                    }
                }
            }, data);

            return data;
        },
        /**
         * Shows or hides a row of badges.
         *
         * @param {type} row
         * @returns {undefined}
         */
        toggle_badge_row: function(row) {
            var badgerow = row.next();

            if (!badgerow || !badgerow.hasClass('badge-row')) {
                var target = row.one('td.show-badges');
                var spinner = !!M.util.add_spinner ? M.util.add_spinner(Y, target) : false;
                var cellcount = row.all('td').size();
                var badgecell = Y.Node.create('<td></td>').setAttribute('colspan', cellcount);
                var userid = row.generateID().split('-')[1];

                badgerow = Y.Node.create('<tr></tr>').append(badgecell).addClass('badge-row').
                        setStyle('display', 'none');
                row.insert(badgerow, 'after');

                if (spinner !== false) {
                    spinner.show();
                }

                this.get_badges(userid, badgecell, function() {
                    badgerow.toggleView();

                    if (spinner !== false) {
                        spinner.hide();
                    }
                });

            } else {
                badgerow.toggleView();
            }

        },
        /**
         * Gets the badges of a user and calls this.show_badges to display
         * them.
         *
         * @param {type} userid
         * @param {type} cell
         * @param {type} callback
         * @returns {undefined}
         */
        get_badges: function(userid, cell, callback) {
            Y.io(this.config.url, {
                data: {userid: userid},
                on: {complete: Y.bind(this.show_badges, this)},
                arguments: {cell: cell, callback: callback, userid: userid}
            });
        },

        /**
         * Displays the badges of a single user.
         *
         * @param {type} transactionid
         * @param {type} xhr
         * @param {type} args
         * @returns {undefined}
         */
        show_badges: function(transactionid, xhr, args) {
            var assertions = JSON.parse(xhr.responseText);
            var cell = args.cell;
            var html = '';

            Y.Array.each(assertions, Y.bind(function(assertion, index) {
                assertion.id = args.userid + '-' + index;
                html += this.templates.badge(assertion);
                this.assertions[assertion.id] = assertion;
            }, this));

            cell.setContent(this.templates.list({content: html}));
            args.callback();
        },

        /**
         * Copied from YUI 3.8.0 templates to work with Moodle 2.2
         */
        compile_template: function(text, options) {

            var blocks = [],
                    tokenClose = "\uffff",
                    tokenOpen = "\ufffe",
                    source;

            options = Y.merge({
                code: /\{\{%([\s\S]+?)%\}\}/g,
                escapedOutput: /\{\{(?!%)([\s\S]+?)\}\}/g,
                rawOutput: /\{\{\{([\s\S]+?)\}\}\}/g,
                stringEscape: /\\|'|\r|\n|\t|\u2028|\u2029/g,
                stringReplace: {
                    '\\': '\\\\',
                    "'": "\\'",
                    '\r': '\\r',
                    '\n': '\\n',
                    '\t': '\\t',
                    '\u2028': '\\u2028',
                    '\u2029': '\\u2029'
                }
            }, options);

            source = "var $b='', $v=function (v){return v || v === 0 ? v : $b;}, $t='" + text.replace(/\ufffe|\uffff/g, '')
                    .replace(options.rawOutput, function(match, code) {
                        return tokenOpen + (blocks.push("'+\n$v(" + code + ")+\n'") - 1) + tokenClose;
                    })
                    .replace(options.escapedOutput, function(match, code) {
                        return tokenOpen + (blocks.push("'+\n$e($v(" + code + "))+\n'") - 1) + tokenClose;
                    })
                    .replace(options.code, function(match, code) {
                        return tokenOpen + (blocks.push("';\n" + code + "\n$t+='") - 1) + tokenClose;
                    })
                    .replace(options.stringEscape, function(match) {
                        return options.stringReplace[match] || '';
                    })
                    .replace(/\ufffe(\d+)\uffff/g, function(match, index) {
                        return blocks[parseInt(index, 10)];
                    })
                    .replace(/\n\$t\+='';\n/g, '\n') + "';\nreturn $t;";

            // If compile() was called from precompile(), return precompiled source.
            if (options.precompile) {
                return "function (Y, $e, data) {\n" + source + "\n}";
            }

            // Otherwise, return an executable function.
            return this.revive_template(new Function('Y', '$e', 'data', source));
        },
        /**
         * Copied from YUI 3.8.0
         */
        revive_template: function(precompiled) {
            return function(data) {
                data || (data = {});
                return precompiled.call(data, Y, Y.Escape.html, data);
            };
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

    M.local_obf.init_badgedisplayer = function(config) {
        config.init_list_only = true;
        return new COURSEUSERBADGEDISPLAYER(config);
    };
}, '@VERSION@', {requires: ['io-base', 'json-parse', 'panel', 'escape', 'widget-buttons', 'widget-stdmod']});
