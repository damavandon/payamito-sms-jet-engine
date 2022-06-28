jQuery(document).ready(function ($) {
    "use strict"

    class Payamito_Jet {
        constructor(form) {
            this.form = $(form);
            this.formID = this.form.data('form-id');
            this.getSettings();
            this.ajaxFilter(this);

        }
        init(settings) {
            this.settings = settings;
            if (typeof this.settings === 'undefined') {
                return;
            }
            if (!this.isConfirmPhone()) {
                return;
            }
            this.OTPExist = false;
            this.phoneField = $(this.form.find("#" + this.settings['to']));
            this.getErrorWrapper();
            this.showReloadOTP = this.getUrlParameter('paymito_show');
            
            this.formStatus = this.getUrlParameter('status');
            if (this.showReloadOTP === '1' && this.formStatus !== 'success' && this.formStatus !== 'payamito_phone_invalide') {
                this.appendOTP();
            }
        }
        isConfirmPhone() {
            if (typeof this.settings === undefined) {
                return false;
            }
            if (this.settings['active'] === true && typeof this.settings['to'] === "string") {
                return true;
            }
            return false;
        }
        appendOTP() {
         
            let parent = this.phoneField.parent();
            if (this.OTPExist === false) {
                parent.append('<div class="jet-form-col jet-form-col-12"  id="payamito_jet_otp"  field-type-text  jet-form-field-container" data-field="payamito_jet_otp_filed" data-conditional="false"><div class="jet-form__label"> <span class="jet-form__label-text">' + this.settings.lable + '<span class="jet-form__required">*</span></span>');
                parent.append('<input class="jet-form__field text-field " value="" required="required" name="payamito_jet_otp_filed" id="payamito_jet_otp_filed" type="number" placeholder=' + this.settings.placeholder + ' data-field-name="payamito_jet_otp_filed">');
                parent.append('<button id="payamito_jet_resend" style="margin: 1% 0px"  type="button">' + this.settings.resend_button + '</button>');

                this.resendButton = $(this.form.find("#payamito_jet_resend"));
                this.resendButton.on("click", { this: this }, this.resendOTP);
                parent.append("</div></div>");
                this.OTPDiv = $(this.form.find("#payamito_jet_otp"));
                this.OTPExist = true;
                this.removeOTP(this);
            }
        }
        removeOTP(object) {
            var $this = object;
            $(document).on("jet-engine/form/ajax/on-success", function (response) {
                $this.OTPDiv.remove();
                $this.OTPExist = false;
            });
        }
        ajaxFilter(object) {
            var $this = object;
            $(document).ajaxSuccess(function (response, data) {
                var data = data.responseJSON;
                if (typeof data.payamito_show_otp !== undefined) {
                    if (data.payamito_show_otp === true) {
                        $this.appendOTP();
                    }
                }
            });
        }
        getSettings() {
            this.ajax("settings")
        }
        resendOTP(event) {
            $(this).prop('disabled', true);

            let $form = event.data.this;

            $(this).attr('disabled', true);

            $form.form.addClass('is-loading');
            let data = { phone: $form.phoneField.val() }
            $form.ajax("resend", data);

        }
        responseResend(response) {
            if (typeof response === 'undefined') {
                this.form.removeClass('is-loading');
                return;
            }
            if (response['result'] === true) {
                this.counterTime(this)
            } else {
                $(this).attr('disabled', false);
            }

            var message = "";
            if (response['result'] === true) {
                message = '<div class="jet-form-message jet-form-message--success">' + response['message'] + '</div>'
            } else {
                message = '<div class="jet-form-message jet-form-message--error">' + response['message'] + '</div>'
            }
            if (this.form.showReloadOTP === '1') {
                $(this.form).next('.jet-form-message').remove();
                ($(this.form).after($(message)));
            } else {
                $(this.errorWrapper).empty();
                ($(this.errorWrapper).append($(message)));

            }
            this.form.removeClass('is-loading');
        }
        counterTime(form) {
            var timer = form.settings['resend'];
            var innerhtml = form.resendButton.html();
            var Interval = setInterval(function () {

                var seconds = parseInt(timer);
                seconds = seconds < 10 ? "0" + seconds : seconds;
                form.resendButton.html(seconds)
                if (--timer <= 0) {
                    timer = 0;
                    form.resendButton.html(innerhtml);
                    form.resendButton.removeAttr("disabled");
                    clearInterval(Interval);
                }
            }, 1000);
        }
        getErrorWrapper() {
            let wrappers = $(".jet-form-messages-wrap");
            for (const iterator of wrappers) {
                let id = $(iterator).data('form-id');
                if (id == this.formID) {
                    this.errorWrapper = iterator;
                    break;
                }
            }
        }
        getResponse(type, response) {
            if (type === 'settings') {
                this.init(response);
            }
            if (type === 'resend') {
                this.responseResend(response);
            }
        }
        ajax(actionType, dataSend = []) {
            var data = {};
            var $result;
            var $this = this;
            var $actionType = actionType;
            data.formID = this.formID;
            data.action = "payamito_jet_form_booking";
            data.actionType = actionType;
            data.dataSend = dataSend;
            $.ajax({
                url: JetEngineSettings.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: data,
            }).done(function (response, status) {

                if (status === 'success') {
                    $this.getResponse($actionType, response);
                }
            })
        }
        getUrlParameter(sParam) {
            var sPageURL = window.location.search.substring(1),
                sURLVariables = sPageURL.split('&'),
                sParameterName,
                i;

            for (i = 0; i < sURLVariables.length; i++) {
                sParameterName = sURLVariables[i].split('=');

                if (sParameterName[0] === sParam) {
                    return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                }
            }
            return false;
        };
    }
    (function ($) {
        let forms = $(".jet-form");
        for (const key in forms) {
            let form = $(forms[key]);
            if (form.is("form")) {

                new Payamito_Jet(forms[key]);
            }
            if (!isNumeric(key)) {
                break;
            }
        }
        function isNumeric(str) {
            if (typeof str != "string") return false
            return !isNaN(str) && !isNaN(parseFloat(str))
        }
    })(jQuery)

})

