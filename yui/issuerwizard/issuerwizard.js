YUI.add('moodle-local_obf-issuerwizard', function(Y) {
    M.local_obf = {
        /**
         * Module initializer.
         * 
         * @param {type} config
         * @returns {undefined}
         */
        init: function(config) {
            this.create_tabview();
        },
                
        /**
         * Creates the tabview from the existing markup.
         * 
         * @returns {undefined}
         */
        create_tabview: function() {
            YUI({fetchCSS: false}).use('tabview', Y.bind(function(Y) {
                var tabview = new Y.TabView({srcNode: '#obf-issuerwizard'});
                tabview.render();

                // We need to apply Moodle CSS-class to selected tab
                // to make the tabs look like they should in Moodle.
                var changeClass = function() {
                    var node = tabview.get('srcNode');
                    node.all('.yui3-tab').removeClass('active');
                    node.all('.yui3-tab-selected').addClass('active');
                };

                changeClass(tabview);
                tabview.after('selectionChange', Y.bind(function(e) {
                    // selectionChange fires too early, so we need a tiny hack
                    Y.later(0, null, Y.bind(function() {
                        changeClass();

                        // HACK: Isn't there a way to find out which tab has been selected?
                        // Like tabview.get('activeDescendant').get('id') == 'idofmytab'
                        var lasttabselected = tabview.get('activeDescendant').get('index') == tabview._items.length - 1;

                        if (lasttabselected) {
                            this.populate_confirmation_fields();
                        }
                    }, this), e);
                }, this));

                Y.one('.confirm-email a').on('click', this.show_email_preview, this);
            }, this));
        },
        
        /**
         * Displays the email preview window.
         * 
         * @returns {undefined}
         */
        show_email_preview: function() {
            var email = '<pre style="white-space: pre-wrap">' +
                    M.util.get_string('emailsubject', 'local_obf') + ': ' + Y.one('#id_emailsubject').get('value') + '\n\n' +
                    Y.one('#id_emailbody').get('value') + '\n\n' +
                    Y.one('#id_emailfooter').get('value') +
                    '</pre>';

            var email_preview_window = window.open('', 'obf-email-preview', 'width=600, height=400' +
                    ',menubar=0, toolbar=0, status=0, scrollbars=1, resizable=1');
            email_preview_window.document.body.innerHTML = email;
        },
                
        getdatevalues: function(fieldname) {
            return Y.Array(['day', 'month', 'year']).map(function(item) {
                return Y.one('#details select[name="' + fieldname + '[' + item + ']"]').get('value');
            });
        },
        getformatteddate: function(fieldname) {
            var datevalues = this.getdatevalues(fieldname);
            var date = new Date(datevalues[2], datevalues[1] - 1, datevalues[0]);

            return date.toDateString();
        },
        populate_confirmation_fields: function() {
            var selected_users = Y.all('#recipientlist option:checked');
            var selected_user_names = selected_users.get('label');
            var issuedon = this.getformatteddate('issuedon');
            var expiration_entered = Y.one('input[name="expiresby[enabled]"]').getDOMNode().checked;
            var expiresby = expiration_entered ? this.getformatteddate('expiresby') : '-';

            // create DOM-elements
            var list = Y.Node.create('<ul></ul>');

            Y.Array.each(selected_user_names, function(item) {
                list.append('<li>' + item + '</li>');
            });

            var recipientsnode = Y.one('.confirm-recipients').setHTML(list);
            var issuedonnode = Y.one('.confirm-issuedon').setHTML(issuedon);
            var expiresbynode = Y.one('.confirm-expiresby').setHTML(expiresby);
            var emailpreviewnode = Y.one('.confirm-email');

            // When creating static form elements with Moodleforms, the value
            // has a trailing non-breaking space. In this case the value is
            // a DOM-node (div) and the NBSP adds an extra row making the form
            // look stupid with too much space between the elements. So this is
            // how we clear the extra whitespace.
            recipientsnode.ancestor().setHTML(recipientsnode);
            issuedonnode.ancestor().setHTML(issuedonnode);
            expiresbynode.ancestor().setHTML(expiresbynode);
            emailpreviewnode.ancestor().setHTML(emailpreviewnode);
        }
    };
}, '@VERSION@');