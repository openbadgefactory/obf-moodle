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
        },

        toggle_class: function (e) {
            e.preventDefault();

            var node = e.currentTarget;
            node.toggleClass('active');
            var categories = Y.all('ul.obf-categories button.active').get('text');

            Y.all('ul.badgelist li').each(function (badge, index, list) {
               var badge_categories = JSON.parse(badge.getAttribute('data-categories'));
               var show_badge = badge_categories.length === 0; // always show badges without categories

               Y.Array.some(badge_categories, function (cat) {
                   if (Y.Array.indexOf(categories, cat) > -1) {
                       show_badge = true;
                       return true;
                   }
               });

               badge[show_badge ? 'show' : 'hide'](true);
            }, this);
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
}, '@VERSION@', { requires: ['base','json-parse','transition'] });