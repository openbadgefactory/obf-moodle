M.mod_earnablebadge = {};

M.mod_earnablebadge.init_view = function(Y) {
    var names = [], ids = [], descriptions = [];
    Y.all('.obf-badge').each(
            function(el, idx) {
              var data = Y.one(el).getData();
              names.push(data.name);
              ids.push(data.id);
              descriptions.push(data.description);
            }
    );
    Y.on('click', function(e) {
        var data = Y.one(e.currentTarget).siblings().get(0).pop().one('.obf-badge').getData();

        Y.one('input[name="externalearnablebadge"]').set('value', data.id);
        var nameEl = Y.one('#id_name');
        //var descriptionEl = Y.one('#id_description');
        if (nameEl.get('value').trim().length == 0 || names.indexOf(nameEl.get('value')) > -1) {
          nameEl.set('value', data.name);
        }
        //if (descriptionEl.get('value').trim().length == 0 || names.indexOf(descriptionEl.get('value')) > -1) {
        //  descriptionEl.set('value', data.description);
        //}
    }, 'input[name="earnable"]');
};

M.mod_earnablebadge.init_view_bootstrap_form = function(Y) {
  var form = jQuery('#earnable-form');
  form.find('label').parent().toggleClass('fitem', true).toggleClass('clearfix', true);
  form.find('label').toggleClass('fitemtitle', true);
  form.find('select,input').parent().toggleClass('felement', true);//.toggleClass('col-md-4', false);
  form.toggleClass('mform', true);
  form.find('fieldset').toggleClass('fcontainer', true);
}
