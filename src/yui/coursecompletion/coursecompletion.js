YUI.add('moodle-local_obf-coursecompletion', function(Y) {
    var COURSECOMPLETIONNAME = 'obf-coursecompletion';
    var COURSECOMPLETION = function() {
        COURSECOMPLETION.superclass.constructor.apply(this, arguments);
    };
    Y.extend(COURSECOMPLETION, Y.Base, {
        criteriacontainer: null,
        criterialist: null,
        addbutton: null,
        courselist: null,
        /**
         * Module initializer.
         * 
         * @param {type} config
         * @returns {undefined}
         */
        initializer: function(config) {
            this.criteriacontainer = Y.one('#existing_criteria');
            this.criterialist = Y.Node.create('<ul></ul>');
            this.criteriacontainer.append(this.criterialist);
            this.addbutton = Y.one('#id_addnewcriterion');
            this.courselist = Y.one('#id_course');

            this.criterialist.delegate('click', Y.bind(this.remove_criterion, this), 'a');
            this.addbutton.on('click', Y.bind(this.add_course, this));
            Y.one('#coursecompletionform').on('submit', Y.bind(this.save_criterion, this));
        },
        /**
         * 
         * @param {type} e
         * @returns {undefined}
         */
        remove_criterion: function(e) {
            e.preventDefault();

            if (window.confirm('Remove this course from criterion?')) {
                var listelement = e.target.ancestor('li');
                var courseid = listelement.getData('courseid');
                this.courselist.one('option[value=' + courseid + ']').show();
                listelement.remove();
                this.enableform();
                this.selectfirstcourse();
            }
        },
        /**
         * Adds a single course to our criteria list.
         * 
         * @returns {undefined}
         */
        add_course: function() {
            var selectedoption = this.courselist.get('options').item(this.courselist.get('selectedIndex'));
            var coursename = selectedoption.get('text');
            var courseid = selectedoption.get('value');
            var grade = parseInt(Y.one('#id_mingrade').get('value')) || '';
            var date_enabled = Y.one('#id_completedby_enabled').getDOMNode().checked;
            var criteriontext = '<strong>' + coursename + '</strong>';

            // TODO: get_string
            if (date_enabled) {
                criteriontext += ' by ' + this.getformatteddate('completedby');
            }

            if (grade) {
                criteriontext += ' with minimum grade of ' + grade;
            }

            criteriontext += ' <a href="#" title="Remove">x</a>';

            // Show fancy animation while adding course. We can remove
            // this later, just trying out YUI's animation capabilities.

            var fieldset = Y.one('#id_header_criterion_fields .fcontainer');
            var ghost = Y.Node.create('<div></div>').setStyles({
                'position': 'absolute',
                'width': fieldset.get('offsetWidth') + 'px',
                'height': fieldset.get('offsetHeight') + 'px',
                'border': 'solid #666 1px'
            });

            ghost.setX(fieldset.getX()).setY(fieldset.getY());

            var anim = new Y.Anim({
                node: ghost,
                duration: 0.5,
                easing: Y.Easing.easeOut,
                to: {
                    xy: this.criterialist.getXY(),
                    opacity: 0.2,
                    width: this.criterialist.get('offsetWidth') + 'px',
                    height: this.criterialist.get('offsetHeight') + 'px'
                }
            });

            anim.on('end', Y.bind(function() {
                ghost.remove(true);

                var date = date_enabled ? this.getdatevalues('completedby').reverse().join('-') : '';
                var listelement = Y.Node.create('<li>' + criteriontext + '</li>').
                        setData('courseid', courseid).
                        setData('grade', grade).
                        setData('date', date);

                this.criterialist.append(listelement);
            }, this));

            Y.one('body').append(ghost);
            anim.run();

            // Same course cannot be added multiple times.
            selectedoption.hide();
            // Reset form
            Y.one('#id_mingrade').set('value', '');

            if (this.hasselectablecourses())
                this.selectfirstcourse();
            else
                this.disableform();
        },
        save_criterion: function(e) {
//            e.preventDefault();

            var haserrors = false;
            var form = Y.one('#coursecompletionform');
            var field = form.one('input[name=coursedata]');
            var items = [];

            this.criterialist.all('li').each(function(item) {
                var obj = {
                    'id': item.getData('courseid'),
                    'date': item.getData('date'),
                    'grade': item.getData('grade')
                };
                
                items.push(obj);
            });

            field.set('value', Y.JSON.stringify(items));

            if (haserrors)
                e.preventDefault();
        },
        /**
         * 
         * @returns {Boolean}
         */
        hasselectablecourses: function() {
            var hasselectable = false;

            this.courselist.get('options').some(function(option) {
                if (option.getStyle('display') !== 'none') {
                    hasselectable = true;
                    return true;
                }
            });

            return hasselectable;
        },
        /**
         * 
         * @returns {undefined}
         */
        selectfirstcourse: function() {
            this.courselist.get('options').some(function(option) {
                if (option.getStyle('display') !== 'none') {
                    option.set('selected', true);
                    return true;
                }
            });
        },
        /**
         * 
         * @returns {undefined}
         */
        disableform: function() {
            Y.one('fieldset#id_header_criterion_fields').set('disabled', true);
        },
        /**
         * 
         * @returns {undefined}
         */
        enableform: function() {
            Y.one('fieldset#id_header_criterion_fields').set('disabled', false);
        },
        /**
         * 
         */
        getdatevalues: function(fieldname) {
            return Y.Array(['day', 'month', 'year']).map(function(item) {
                return Y.one('select[name="' + fieldname + '[' + item + ']"]').get('value');
            });
        },
        getdate: function(datevalues) {
            return new Date(datevalues[2], datevalues[1] - 1, datevalues[0]);

        },
        getformatteddate: function(fieldname) {
            var date = this.getdate(this.getdatevalues(fieldname));
            return date.toDateString();
        }
    }, {
        NAME: COURSECOMPLETIONNAME,
        ATTRS: {
            aparam: {}
        }
    });
    M.local_obf = M.local_obf || {};
    M.local_obf.init_coursecompletion = function(config) {
        return new COURSECOMPLETION(config);
    };
}, '@VERSION@', {requires: ['base', 'anim','json-stringify'] });