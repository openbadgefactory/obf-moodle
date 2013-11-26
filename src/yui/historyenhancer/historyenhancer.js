YUI.add('moodle-local_obf-historyenhancer', function(Y) {
    var HISTORYENHANCERNAME = 'obf-historyenhancer';
    var HISTORYENHANCER = function() {
        HISTORYENHANCER.superclass.constructor.apply(this, arguments);
    };
    Y.extend(HISTORYENHANCER, Y.Base, {
        initializer: function(config) {
            this.process();
        },
        process: function() {
            var lists = Y.all('table.historytable .recipientlist');
            
            lists.each(function (list) {
               var anchors = list.all('a');
               
                if (anchors.size() > 1) {
                    var first = anchors.shift();
                    var link = Y.Node.create('<a href="#">' + anchors.size() + ' ' +
                            M.util.get_string('showmorerecipients', 'local_obf') + '</a>');
                    var hiddentoggle = Y.Node.create('<span></span>');
                    var hiddenbox = Y.Node.create('<span></span>');
                    
                    hiddentoggle.append('&nbsp;(+&nbsp;').append(link).append(')');
                    
                    list.setHTML('');
                    list.append(first);                  
                    
                    while (anchors.size() > 0) {
                        hiddenbox.append(',&nbsp;');
                        hiddenbox.append(anchors.shift());
                    }

                    hiddenbox.hide();
                    link.on('click', function (e) {
                        e.preventDefault();
                        hiddentoggle.hide();
                        hiddenbox.show();
                    });
                    
                    list.append(hiddentoggle);
                    list.append(hiddenbox);
                    
               }
            });
        }
    }, {
        NAME: HISTORYENHANCERNAME,
        ATTRS: {
            aparam: {}
        }
    });
    M.local_obf = M.local_obf || {};
    M.local_obf.init_historyenhancer = function(config) {
        return new HISTORYENHANCER(config);
    };
}, '@VERSION@', {requires: ['base']});