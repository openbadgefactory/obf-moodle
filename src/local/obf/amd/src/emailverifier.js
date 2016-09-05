/**
  * @module local_obf/emailverifier
  */
define(['jquery'], function($) {
    var EMAILVERIFIER = {
        initialise: function (options) {
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
            parent.prepend(modal);
            modal.toggleClass('fade');
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
            var email = modal.find('input[type="email"]').val();
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
            modal = parent.find('#myModal');
            modal.find('.modal-content').toggleClass('hide', true);
            modal.find('.modal-content.step-two').toggleClass('hide', false);
        },
        modal_show_status_msg: function (that, msg) {
            var parent = $(that.opt.selector).parents('form');
            modal = parent.find('#myModal');
            modal.find('.modal-content').toggleClass('hide', true);
            var statusDiv = modal.find('.modal-content.step-three').toggleClass('hide', false);
            statusDiv.find('.modal-body').html($('<p></p>').html(msg));
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
            modal = parent.find('#myModal');
            modal.find('.modal-content').toggleClass('hide', true);
            modal.find('.modal-content.step-one').toggleClass('hide', false);
            return false;
        },
        modal_template: function () {
            return  '<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">' +
                        '<div class="modal-dialog">'+
                            '<div class="modal-content step-one">'+
                                '<div class="modal-header">'+
                                    '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
                                    '<h4 class="modal-title" id="myModalLabel">Add email address</h4>'+
                                '</div>'+
                                '<div class="modal-body">'+
                                    '<p>Type your email address. A verification code will be sent to that address.</p>'+
                                    '<label for="email">Email-address</label><input type="email" name="email"></input>'+
                                '</div>'+
                                '<div class="modal-footer"><button class="btn btn-default create-token">Add</button>'+
                                '</div>'+
                            '</div>'+
                            '<div class="modal-content step-two hide">'+
                                '<div class="modal-header">'+
                                    '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
                                    '<h4 class="modal-title" id="myModalLabel">Verify email address</h4>'+
                                '</div>'+
                                '<div class="modal-body">'+
                                    '<p>An email has been sent to the provided address. Check your email for a verification code.</p>' +
                                    '<label for="token">Verification code</label><input type="text" name="token"></input>'+
                                '</div>'+
                                '<div class="modal-footer"><button class="btn btn-default verify-token">Verify</button>'+
                                '</div>'+
                            '</div>'+
                            '<div class="modal-content step-three status hide">'+
                                '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
                                '<h4 class="modal-title" id="myModalLabel">Error</h4>'+
                                '<div class="modal-body">'+
                                '</div>'+
                                '<div class="modal-footer"><button class="btn btn-default btn-reset">Reset</button><button class="btn btn-default btn-close">Close</button>'+
                            '</div>'+
                        '</div>'+
                    '</div>';
        }
    };

    return EMAILVERIFIER;
});
