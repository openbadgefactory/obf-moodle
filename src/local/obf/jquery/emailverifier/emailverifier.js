/**
  * @module local_obf/emailverifier
  */
var LOCAL_OBF_EMAILVERIFIER = {};
(function($) {
    LOCAL_OBF_EMAILVERIFIER = {
        initialise: function (ignore, options) {
            this.opt = options;
            var that = this;
            $(this.opt.selector).on('click', that, that.connect);
        },
        connect: function (event) {
            that = event.data;
            that.provider = $(event.target).attr('data-provider');
            that.show_modal(that);
        },
        show_modal: function (that) {
            var parent = $(that.opt.selector).parents('form');
            var modal = $(that.modal_template());
            modal.addClass('modal');
            parent.prepend(modal);
            modal.find('.modal-body * div.step').each(function() {
                var $t = $(this);
                $t.detach().appendTo(modal.find('.modal-body:first'));
            });
            modal.toggleClass('fade', false);
            modal.css('display', 'block');
            that.modal_binding(that, modal);
        },
        modal_binding: function (that, modal) {
            modal.find('button.close,button.btn-close').on('click', {that: that, modal: modal}, that.modal_destroy);
            modal.find('.create-token').on('click', {that: that, modal: modal}, that.add_address);
            modal.find('.verify-token').on('click', {that: that, modal: modal}, that.verify_address);
            modal.find('button.btn-reset').on('click', {that: that, modal: modal}, that.reset);
        },
        add_address: function (event) {
            event.preventDefault();
            var that=event.data.that;
            var modal=event.data.modal;
            var email = modal.find('input[type="email"],input[name="email"]').val();
            if (email.length > 3) {
                that.email = email;
                $.ajax({url: that.opt.url, context: that, data: {action: 'create_token', assertion: JSON.stringify({email: email})}, complete: that.after_token_create});
            }
            return false;
        },
        connect_to_backpack: function (that) {
            $.ajax({url: that.opt.url, context: that, data: {action: 'connect_email', provider: that.provider, assertion: JSON.stringify({email: that.email})}, complete: that.connect_to_backpack_handler, error: that.connect_to_backpack_handler}).fail(function () { that.connect_to_backpack_handler(that); });
        },
        connect_to_backpack_handler: function (data,xhr) {
            var that = this;
            data.statusText = 'abort';
            if (data.status = '200') {
                var resp = JSON.parse(data.responseText);
                if (resp.status == true) {
                    M.core_formchangechecker.set_form_submitted();
                    window.location.reload();
                } else {
                    that.modal_show_status_msg(that, resp.message);
                }
            }
        },
        verify_address: function (event) {
            event.preventDefault();
            var that=event.data.that;
            var modal=event.data.modal;
            var token = modal.find('input[name="token"]').val();
            if (that.email.length > 3) {
                $.ajax({url: that.opt.url, context: that, data: {action: 'verify_token', assertion: JSON.stringify({email: that.email, token: token})}, complete: that.after_token_verify});
            }
            return false;
        },
        after_token_create: function (data,xhr) {
            var that = this;
            if (data.status = '200') {
                var resp = JSON.parse(data.responseText);
                if (resp.status == true) {
                    if (resp.verified !== true) {
                        that.modal_step_two(that);
                    }
                    that.auto_status_check_loop();
                }
            }
        },
        auto_status_check_loop: function (that) {
            var that = typeof that === 'undefined' ? this : that;
            if (that.hasOwnProperty('email') && that.email.length > 1) {
                $.ajax({url: that.opt.url, context: that, data: {action: 'check_status', assertion: JSON.stringify({email: that.email})}, complete: that.auto_status_check_handler});
            }
        },
        auto_status_check_handler: function (data,xhr) {
            var that = this;
            if (data.status = '200') {
                var resp = JSON.parse(data.responseText);
                if (resp.status == true) {
                    that.connect_to_backpack(that);
                    return;
                }
            }
            setTimeout(function() { that.auto_status_check_loop(that); }, 2000);
        },
        after_token_verify: function (data,xhr) {
            var that = this;
            if (data.status = '200') {
                var resp = JSON.parse(data.responseText);
                if (resp.status == true) {
                    that.connect_to_backpack(that);
                }
            }
        },
        modal_step_two: function (that) {
            var parent = $(that.opt.selector).parents('form');
            modal = parent.find('.modal');
            modal.find('.step').toggleClass('hide', true);
            modal.find('.step.step-two').toggleClass('hide', false);
        },
        modal_show_status_msg: function (that, msg) {
            var parent = $(that.opt.selector).parents('form');
            modal = parent.find('.modal');
            modal.find('.step').toggleClass('hide', true);
            var statusDiv = modal.find('.step.step-three').toggleClass('hide', false);
            statusDiv.find('.body').html($('<p></p>').html(msg));
        },
        modal_destroy: function (event) {
            var that=event.data.that;
            var modal=event.data.modal;
            modal.remove();
        },
        reset: function (event) {
            var that=event.data.that;
            event.preventDefault();
            var parent = $(that.opt.selector).parents('form');
            that.email = '';
            modal.find('input').val('');
            modal = parent.find('.modal');
            modal.find('.modal-content').toggleClass('hide', true);
            modal.find('.modal-content.step-one').toggleClass('hide', false);
            return false;
        },
        modal_template: function () {
            return that.opt.verifyform;
        }
    };

    return LOCAL_OBF_EMAILVERIFIER;
})(jQuery);
