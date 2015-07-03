YUI.add('moodle-local_obf-badgecategorizer', function(Y) {
    var BADGECATEGORIZERNAME = 'obf-badgecategorizer';
    var BADGECATEGORIZER = function() {
        BADGECATEGORIZER.superclass.constructor.apply(this, arguments);
    };

    Y.extend(BADGECATEGORIZER, Y.Base, {
        /**
         * Module initializer
         *
         * @param Object config
         */
        initializer: function(config) {
            Y.one('ul.obf-categories').delegate('click', this.toggle_class, 'button', this);
            Y.one('button.obf-reset-filter').on('click', this.reset_filter, this);
        },

        /**
         * Toggles the visibility of a single category.
         *
         * @param {type} e
         * @returns {undefined}
         */
        toggle_class: function(e) {
            e.preventDefault();

            var node = e.currentTarget;
            node.toggleClass('active');
            this.toggle_badges();
        },

        /**
         * Displays (and hides) the badges according to states of the category
         * buttons.
         *
         * @returns {undefined}
         */
        toggle_badges: function() {
            var categories = Y.all('ul.obf-categories button.active').get('text');

            Y.all('ul.badgelist li').each(function(badge, index, list) {
                var badge_categories = JSON.parse(badge.getAttribute('data-categories'));
                var show_badge = true;

                // Show all badges if none of the categories is selected
                if (categories.length === 0) {
                    show_badge = true;
                } else {
                    Y.Array.each(categories, function(cat) {
                        if (Y.Array.indexOf(badge_categories, cat) === -1) {
                            show_badge = false;
                        }
                    });
                }

                badge[show_badge ? 'show' : 'hide'](true);
            }, this);
        },

        /**
         * Resets the filter and displays all badges.
         *
         * @param {type} e
         * @returns {undefined}
         */
        reset_filter: function(e) {
            e.preventDefault();

            Y.all('ul.obf-categories button').removeClass('active');
            this.toggle_badges();
        }
    }, {
        NAME: BADGECATEGORIZERNAME,
        ATTRS: {
            aparam: {}
        }
    });

    M.local_obf = M.local_obf || {};
    M.local_obf.init_badgecategorizer = function(config) {
        return new BADGECATEGORIZER(config);
    };
}, '@VERSION@', {requires: ['base', 'json-parse', 'transition']});
