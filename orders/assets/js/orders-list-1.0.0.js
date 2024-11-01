var form_total_fees = 0;
var save_value_pickup_time_datetimepicker;
var save_value_pickup_time_quick_choice_id = '';

jQuery(document).ready(function () {
    order_list.confirmBookingActual();
    order_list.showTabMethod();
    order_list.selectDeliveryType();
    order_list.onChangeSelectVecicle();
    order_list.addExtraServices();
    order_list.addExtraServicesGoodsInsurance();
    order_list.deleteBoookingOrders();
    order_list.trashBoookingOrders();
    order_list.restoreBoookingOrders();
    order_list.onQuickChoices();
    order_list.showBulkOption();
    order_list.bulkConfirmSelected();
    order_list.init();

});

var order_list = {
    init: function () {
        jQuery('#filter_datetimepicker').datetimepicker({
            format: 'd-M-Y',
            timepicker: false,
            onChangeDateTime: function (dp, $input) {
                if ($input.val() != '') {
                    jQuery('.show_pickup_hours').removeClass('disabled');

                } else {
                    jQuery('.show_pickup_hours').addClass('disabled');
                    jQuery('#pickup_hours_start').val('');
                    jQuery('#pickup_hours_end').val('');
                }
            }
        });

    },
    showBulkOption: function () {
        jQuery('body').on('click', '.bulk-button', function (event) {
            event.stopPropagation();
            let checked_length = jQuery("input.checkbox-confirm-booking:checked").length;
            jQuery(".bulk-option").toggle();
            jQuery("#bulk-confirm-selected span").html(checked_length)
        });

        jQuery(document.body).click(function () {
            jQuery(".bulk-option").hide();
        });

    },
    bulkConfirmSelected: function () {
        jQuery('body').on('click', '#bulk-confirm-selected', function (event) {
            let checked_length = jQuery("input.checkbox-confirm-booking:checked").length;
            mscConfirm({
                title: 'Confirm Bookings',
                subtitle: 'By confirming, the booking will include the Deliveree suggested vehicle type and default extra services for the selected orders.',  // default: ''
                okText: 'Back',
                cancelText: 'Confirm Booking (' + checked_length + ')', // default: Cancel,
                dismissOverlay: true, // default: false, closes dialog box when clicked on overlay.
                onOk: function () {  //Swapp button
                    //Cancel
                    window.location.reload();
                },
                onCancel: function () { //Swapp button
                    //Ok
                    order_list.submitBulkBooking();
                }
            });
        });
    },
    submitBulkBooking: function (retry_failed = []) {
        order_list.showPopupComfirming();
        let results = [];
        let p = jQuery.when();
        let count_success = 0;
        let checked_length = jQuery("input.checkbox-confirm-booking:checked").length;
        let count = 1;
        jQuery.each(jQuery("input.checkbox-confirm-booking:checked"), function () {
            let order_id = jQuery(this).val();
            if (retry_failed.length === 0 || retry_failed.includes(order_id)) {
                let get_order_data = '.get_order_data_' + order_id;
                let order_data = jQuery(get_order_data).first();
                let item = order_data.data('data');
                let confirm_vehicle_type = JSON.parse(item.vehicle_type);

                let data = {
                    order_id: item.order_id,
                    vehicle_type_id: confirm_vehicle_type.vehicle_type_id,
                    time_type: 'now',
                    pickup_time: '',
                    vehicle_type: confirm_vehicle_type,
                    quick_choice_id: ''
                };

                p = p.then(function () {
                    jQuery('.msc-title').html('Confirming (' + (count) + '/' + checked_length + ')');
                    let result = order_list.dontShowPopupCreateBooking(data);
                    ++count;
                    results.push(result);
                    return result;
                });
            }

        });

        p = p.then(function () {
            let retry_failed = [];
            for (let index = 0; index < checked_length; index++) {
                const result = results[index];
                result.done(function (response) {
                    if (response.status) {
                        ++count_success;
                        let get_order_data = '.get_order_data_' + response.order_id;
                        jQuery(get_order_data).first().closest("tr").remove();
                    } else {
                        retry_failed.push(response.order_id)
                    }
                    if (index == checked_length - 1) {
                        order_list.showPopupFinished(count_success, checked_length, retry_failed);
                    }
                });
            }
        });
    },
    showPopupFinished: function (count_success, checked_length, retry_failed) {
        let count_failed = checked_length - count_success;
        if (count_success == 0) {
            order_list.showPopupNotConfirmed(count_failed, retry_failed);
        } else if (count_success == checked_length) {
            order_list.showPopupBookingsConfirmed(count_success);
        } else {
            order_list.showPopupPartiallyConfirmed(count_failed, count_success, retry_failed);
        }
    },
    showPopupBookingsConfirmed: function (count_success) {
        mscConfirm({
            title: 'Bookings Confirmed',
            subtitle: 'Please go to Bookings section to check the booking status.',
            okText: 'Go To Bookings',
            cancelText: 'Ok',
            dismissOverlay: true,
            onOk: function () {
                let site_url = jQuery('#get_site_url').data('site-url')
                window.location.href = site_url + '/wp-admin/admin.php?page=bookings_deliveree';
            },
            onCancel: function () {
                window.location.reload();
            }
        });

        jQuery(".msc-body").prepend('<div class="results-length"><p class="show-count"><span class="count-success">' + count_success + '</span> COMFIRMED</p></div>');

    },
    showPopupPartiallyConfirmed: function (count_failed, count_success, retry_failed) {
        mscConfirm({
            title: 'Partially Confirmed',
            subtitle: 'Please retry to confirm the failed booking.',
            okText: 'Retry Failed (' + count_failed + ')',
            cancelText: 'Ok',
            dismissOverlay: true,
            onOk: function () {
                order_list.submitBulkBooking(retry_failed);

            },
            onCancel: function () {
                window.location.reload();
            }
        });
        jQuery(".msc-body").prepend('<div class="results-partially-confirmed results-length"><p class="show-count"><span class="count-success">' + count_success + '</span> COMFIRMED</p><p class="show-count"><span class="count-failed">' + (count_failed) + '</span> FAILED</p></div>');

    },
    showPopupNotConfirmed: function (count_failed, retry_failed) {
        mscConfirm({
            title: 'Not Confirmed',
            subtitle: 'Something went wrong and your bookings couldn’t be confirmed at the moment. Please try again.',
            okText: 'Retry Failed (' + count_failed + ')',
            cancelText: 'Ok',
            dismissOverlay: true,
            onOk: function () {
                order_list.submitBulkBooking(retry_failed);
            },
            onCancel: function () {
                window.location.reload();
            }
        });

        jQuery(".msc-body").prepend('<div class="results-length"><p class="show-count"><span class="count-failed">' + count_failed + '</span> FAILED</p></div>');

    },
    showPopupComfirming: function () {
        let checked_length = jQuery("input.checkbox-confirm-booking:checked").length;
        mscConfirm({
            title: 'Confirming (1/' + checked_length + ')',
            subtitle: 'This might take a while. Please do not close your browser window, it will cancel the booking process.',  // default: ''
        });
        jQuery(".msc-action").html('<div class="loadding-icon"><div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>');
    },

    selectDeliveryType: function () {
        document.multiselect('#select_delivery_type');
        let selected = jQuery('#select_delivery_type').data('selected');
        jQuery('#select_delivery_type_input').attr("placeholder", "Delivery Type");
        if (selected != null) {
            selected.forEach(element => {
                document.multiselect('#select_delivery_type').select(element);
            });
        }

    },
    showTabMethod: function () {
        jQuery('body').on('click', '.show_tab_method', function (event) {
            jQuery('.show_tab_method').removeClass("active");
            jQuery(this).addClass("active");
            let tab = jQuery(this).data("tab");
            jQuery('.tab_method').removeClass("active");
            jQuery('.' + tab).addClass("active");
        });
    },
    numberWithCommas: function (x, currency = 'Rp', positive = true) {
        let number;
        let pre = (x < 0 && !positive) ? '-' : '';

        if (typeof x == 'undefined')
            number = ''
        else {
            pre += ' ' + currency + ' ';
            number = Math.abs(x).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            number = pre + number;
        }

        return number;
    },
    confirmBookingActual: function () {
        jQuery('body').on('click', '.confirmBookingActual', function (event) {
            let item = jQuery(this).data("data");
            let cloneItem = Object.assign({}, item);
            order_list.ajaxGetQuotesById(cloneItem);
        });
    },
    ajaxGetQuotesById: function (item) {
        jQuery.LoadingOverlay("show");
        var time_type = jQuery('.select_time_type').val();
        var pickup_time = jQuery('.select_pickup_time').val();
        let now = moment(new Date());

        form_total_fees = 0;
        save_value_pickup_time_quick_choice_id = '';
        save_value_pickup_time_datetimepicker = now.format("DD-MMM-YYYY HH:mm");

        var data = {
            "time_type": time_type,
            "pickup_time": pickup_time,
            "action": "getQuoteByBoookingOrders",
            "ids": item.id,
        };
        jQuery.ajax({
            url: jQuery('#urlForm').data("admin-url-ajax"),
            type: 'POST',
            dataType: 'json',
            async: true,
            data: data,
        }).done(function (quotes_response) {
            order_list.showConfirmBooking(quotes_response, item);
            jQuery.LoadingOverlay("hide");
        });
    },
    getQuickChoicesOption: function (quick_choices) {
        let quick_choices_option_html = '';
        let active_pickup_time_quick_choices = '';
        let active_pickup_time_calendar = 'active';

        if (quick_choices.length) {
            for (let index_quick_choice = 0; index_quick_choice < quick_choices.length; index_quick_choice++) {
                const element_quick_choice = quick_choices[index_quick_choice];
                let schedule_time = element_quick_choice.schedule_time;
                let hours = element_quick_choice.schedule_time / 60;
                let unit = 'minute';
                if (hours >= 1) {
                    schedule_time = hours;
                    unit = 'hour';
                }
                let plural = (schedule_time > 1) ? 's' : '';

                quick_choices_option_html += '<option    value="' + element_quick_choice.id + '">';
                quick_choices_option_html += 'Pickup in ' + schedule_time + ' ' + unit + plural;
                quick_choices_option_html += '</option>';
            }

            quick_choices_option_html += '<option value="0">Schedule Pickup</option>';
            active_pickup_time_quick_choices = 'active';
            active_pickup_time_calendar = '';
        }


        return {
            quick_choices_option_html: quick_choices_option_html,
            active_pickup_time_quick_choices: active_pickup_time_quick_choices,
            active_pickup_time_calendar: active_pickup_time_calendar,
        }

    },

    showConfirmBooking: function (quotes_response, item) {
        let formConfirmBooking = order_list.formConfirmBooking(quotes_response, item);
        let minTime = moment(new Date());
        let curent_pickup_time = moment(save_value_pickup_time_datetimepicker, "DD-MMM-YYYY HH:mm", true);
        let slected_curent_pickup_time = (curent_pickup_time > minTime) ? curent_pickup_time : minTime;

        if (typeof save_value_pickup_time_quick_choice_id != 'undefined' && save_value_pickup_time_quick_choice_id != '') {
            jQuery("#select_pickup_time_quick_choices").val(save_value_pickup_time_quick_choice_id).change();
        }

        jQuery('#pickup_time_datetimepicker').datetimepicker({
            format: 'd-M-Y H:i',
            timepicker: true,
            minDate: new Date(),
            value: slected_curent_pickup_time.format("DD-MMM-YYYY HH:mm"),
            minTime: new Date(),
            step: 30,
            onSelectDate: function (ct, $input) {
                let selected_date = moment(ct).format("DD");
                let now = moment(new Date()).format("DD");
                minTime = (now == selected_date) ? new Date() : '00:00';
                this.setOptions({
                    minTime: minTime,
                    value: $input.val()
                })
            }
        });

        jQuery('.woocommerce-help-tip').darkTooltip();

        function formatState(state) {
            let new_state = state.text;
            if (formConfirmBooking.best_fit_id == state.id) {
                new_state = jQuery(
                    '<span>' + state.text + ' <span class="best-fit">BEST FIT</span></span>'
                );
            }

            return new_state;
        };

        jQuery("#confirm_select_vecicle").select2({
            templateResult: formatState,
            minimumResultsForSearch: -1
        });

    },
    formConfirmBooking: function (quotes_response, item) {
        let titlePopup = 'Confirm Booking ';
        let options = '';
        let best_fit_option = '';
        let text_vehicle_recommended_html = '';
        let quick_choices = [];
        let deliveree_services_extra_services_on = jQuery('#deliveree_services_extra_services_on');
        let is_extra_services_on = deliveree_services_extra_services_on.length;
        let confirmButtonText = (is_extra_services_on) ? 'Next' : 'Confirm Booking ';
        let curent_vehicle_type = JSON.parse(item.vehicle_type);
        let best_fit_id = (item.best_fit_id) ? item.best_fit_id : curent_vehicle_type.vehicle_type_id;
        let pay = item.paid_shipping - curent_vehicle_type.total_fees;
        let label_pay = (pay >= 0) ? 'You Receive' : 'You Pay';
        let class_pay = (pay >= 0) ? '' : 'you_pay';
        let text_change_vehicle_active = (best_fit_id == curent_vehicle_type.vehicle_type_id) ? '' : 'active';
        form_total_fees = curent_vehicle_type.total_fees;

        for (let index = 0; index < quotes_response.length; index++) {
            const element = quotes_response[index];
            delete element.vehicle_type_id;
            let vehicle_type_response = element.vehicle_type_response;
            let selected = "";

            if (curent_vehicle_type.vehicle_type_id == vehicle_type_response.vehicle_type_id) {
                selected = 'selected';
                quick_choices = vehicle_type_response.vehicle_type.quick_choices;
            }

            if (best_fit_id == vehicle_type_response.vehicle_type_id) {
                best_fit_option += '<option   ' + selected + ' data-vehicle-type-response="' + JSON.stringify(vehicle_type_response).replaceAll("\"", "'") + '" data-item="' + JSON.stringify(element).replaceAll("\"", "'") + '"  value="' + vehicle_type_response.vehicle_type_id + '">';
                best_fit_option += vehicle_type_response.vehicle_type_name;
                best_fit_option += '</option>';
                text_vehicle_recommended_html = vehicle_type_response.vehicle_type_name + ' recommended';
            } else {
                options += '<option   ' + selected + ' data-vehicle-type-response="' + JSON.stringify(vehicle_type_response).replaceAll("\"", "'") + '" data-item="' + JSON.stringify(element).replaceAll("\"", "'") + '"  value="' + vehicle_type_response.vehicle_type_id + '">';
                options += vehicle_type_response.vehicle_type_name;
                options += '</option>';
            }
        }

        let quick_choices_option = order_list.getQuickChoicesOption(quick_choices)

        options = best_fit_option + options;

        Swal.fire({
            title: titlePopup + ' #' + item.order_id,
            html:
                '<div>' +
                '<table id="select-vehicle-form" class="form-table">' +
                '<tbody>' +
                '<tr valign="top">' +
                '<th scope="row" class="titledesc">' +
                '<label for="">Vehicle</label>' +
                '</th>' +
                '<td class="forminp forminp-deliveree-readonly">' +
                '<fieldset> <select id="confirm_select_vecicle">' + options + '</select> <p class=" ' + text_change_vehicle_active + ' text-change-vehicle text-vehicle-recommended">' + text_vehicle_recommended_html + '</p></fieldset>' +
                '</td>' +
                '</tr>' +
                '<tr valign="top">' +
                '<th scope="row" class="titledesc">' +
                '<label for="">Pickup Time</label>' +
                '</th>' +
                '<td class="forminp forminp-deliveree-readonly">' +
                '<fieldset class="pickup-time-quick-choices ' + quick_choices_option.active_pickup_time_quick_choices + '"> <select id="select_pickup_time_quick_choices"  >' + quick_choices_option.quick_choices_option_html + '</select> </fieldset>' +
                '<fieldset class="pickup-time-calendar ' + quick_choices_option.active_pickup_time_calendar + '">   <span class="icon-block"><i class="icon-calendar" ></i></span> <input class="datetimepicker"  id="pickup_time_datetimepicker" name="d" type="text" > </fieldset>' +
                '</td>' +
                '</tr>' +
                '<tr valign="top">' +
                '<th scope="row" class="titledesc">' +
                '<label for="">Distance</label>' +
                '</th>' +
                '<td class="forminp forminp-deliveree-readonly">' +
                '<fieldset id="form_total_distance"> ' + order_list.numberWithCommas(curent_vehicle_type.total_distance, '') + ' km</fieldset>' +
                '</td>' +
                '</tr>' +
                '<tr valign="top">' +
                '<th scope="row" class="titledesc">' +
                '<label for="">Customer Pays <span class="woocommerce-help-tip" data-tooltip="The price your customer must pay for the ' + window.delivereeConfig.deliveree_name + ' booking."></span></label>' +
                '</th>' +
                '<td class="forminp forminp-deliveree-readonly">' +
                '<fieldset id="form_paid_shipping"> ' + order_list.numberWithCommas(item.paid_shipping, curent_vehicle_type.currency) + '</fieldset>' +
                '</td>' +
                '</tr>' +
                '<tr valign="top">' +
                '<th style="padding-bottom: 0;" scope="row" class="titledesc">' +
                '<label for="">' + window.delivereeConfig.deliveree_name + ' Receives <span class="woocommerce-help-tip" data-tooltip="Total price you pay to ' + window.delivereeConfig.deliveree_name + ' for this booking."></span></label>' +
                '</th>' +
                '<td style="padding-bottom: 0;"  class="forminp forminp-deliveree-readonly">' +
                '<fieldset> <span  id="form_total_fees"> ' + order_list.numberWithCommas(curent_vehicle_type.total_fees, curent_vehicle_type.currency) + '</span> </fieldset>' +
                '</td>' +
                '</tr>' +
                '<tr valign="top" class=" ' + text_change_vehicle_active + '  text-change-vehicle" > <td colspan="2" style="padding: 0 10px;" > <p >Price updated </p> </td>' +
                '</tr>' +
                '<tr valign="top" >' +
                '<th scope="row" class="titledesc">' +
                '<label for=""><span id="label_form_you_receive">' + label_pay + '</span> <span class="woocommerce-help-tip" data-tooltip="If value is positive, you receive this premium. If value is negative, you pay this discount. "></span></label>' +
                '</th>' +
                '<td class="forminp forminp-deliveree-readonly">' +
                '<fieldset class="' + class_pay + '" id="form_you_receive">  ' + order_list.numberWithCommas(pay, curent_vehicle_type.currency) + '</fieldset>' +
                '</td>' +
                '</tr>' +
                '</tbody>' +
                '</table>' +
                '</div>',
            focusConfirm: false,
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Back',
            showCancelButton: true,
            reverseButtons: true,
            customClass: {
                container: 'select-vehicle',
                popup: 'popup-select-vehicle',
                header: 'header-select-vehicle',
                title: 'title-select-vehicle',
                closeButton: '...',
                icon: '...',
                image: '...',
                content: 'content-select-vehicle',
                htmlContainer: '...',
                input: '...',
                inputLabel: '...',
                validationMessage: '...',
                actions: 'actions-select-vehicle',
                confirmButton: 'confirmButton-select-vehicle',
                denyButton: 'denyButton-select-vehicle',
                cancelButton: 'cancelButton-select-vehicle',
                loader: '...',
                footer: '....'
            },
            preConfirm: () => { },
        }).then((result) => {
            if (result.isConfirmed) {
                save_value_pickup_time_quick_choice_id = jQuery('#select_pickup_time_quick_choices').find(":selected").val();
                let vehicle_type_response = jQuery('#confirm_select_vecicle').find(":selected").data('vehicle-type-response');
                save_value_pickup_time_datetimepicker = jQuery('#pickup_time_datetimepicker').val();
                item.vehicle_type = vehicle_type_response.replaceAll("'", "\"");
                item.best_fit_id = best_fit_id;

                if (is_extra_services_on) {
                    order_list.getExtraServices(quotes_response, item);
                } else {
                    let confirm_vehicle_type = JSON.parse(item.vehicle_type);
                    let now = moment(new Date());
                    let curent_pickup_time = moment(save_value_pickup_time_datetimepicker, "DD-MMM-YYYY HH:mm");
                    let time_type = (curent_pickup_time > now) ? 'schedule' : 'now';
                    let pickup_time = (curent_pickup_time > now) ? curent_pickup_time.toISOString() : '';
                    let data = {
                        order_id: item.order_id,
                        vehicle_type_id: confirm_vehicle_type.vehicle_type_id,
                        time_type: time_type,
                        pickup_time: pickup_time,
                        vehicle_type: confirm_vehicle_type,
                        quick_choice_id: save_value_pickup_time_quick_choice_id
                    };

                    order_list.dontShowPopupCreateBooking(data);
                }
            }
        })

        return {
            best_fit_id
        }
    },
    onChangeSelectVecicle: function () {
        jQuery('body').on('change', '#confirm_select_vecicle', function (event) {
            let item_json = jQuery(this).find(":selected").data('item')
            item_json = item_json.replaceAll("'", "\"")
            let item = JSON.parse(item_json);
            let vehicle_type = JSON.parse(item.vehicle_type);
            let quote = item.vehicle_type_response;

            if (vehicle_type.vehicle_type_id == quote.vehicle_type_id) {
                jQuery('.text-change-vehicle').removeClass('active');
            } else {
                jQuery('.text-vehicle-recommended').html(item.vehicle_type_name + ' recommended')
                jQuery('.text-change-vehicle').addClass('active');
            }

            order_list.setQuickChoices(quote.vehicle_type.quick_choices)

            let total_fees = parseInt(quote.total_fees);
            let total_distance = order_list.numberWithCommas(quote.total_distance, '');
            jQuery("#form_total_distance").html(total_distance + ' km');
            jQuery("#form_total_fees").html(order_list.numberWithCommas(total_fees, quote.currency));
            form_total_fees = total_fees;
            let pay = item.paid_shipping - total_fees;
            let label_pay = (pay >= 0) ? 'You Receive' : 'You Pay';

            jQuery("#label_form_you_receive").html(label_pay);
            if (pay >= 0) {
                jQuery("#form_you_receive").removeClass('you_pay');
            } else {
                jQuery("#form_you_receive").addClass('you_pay');
            }

            jQuery("#form_you_receive").html(order_list.numberWithCommas(pay, quote.currency));

        })
    },
    setQuickChoices: function (quick_choices) {
        let quick_choices_option = order_list.getQuickChoicesOption(quick_choices);
        let now = moment(new Date());
        jQuery(".pickup-time-calendar").removeClass('active');
        jQuery(".pickup-time-quick-choices").removeClass('active');
        jQuery("#select_pickup_time_quick_choices").html(quick_choices_option.quick_choices_option_html);
        jQuery(".pickup-time-quick-choices").addClass(quick_choices_option.active_pickup_time_quick_choices);
        jQuery(".pickup-time-calendar").addClass(quick_choices_option.active_pickup_time_calendar);
        jQuery('#pickup_time_datetimepicker').val(now.format("DD-MMM-YYYY HH:mm"));
    },
    onQuickChoices: function () {
        jQuery('body').on('change', '#select_pickup_time_quick_choices', function (event) {
            let pickup_time_quick_choices = jQuery(this).find(":selected").val();
            console.log(111);
            if (pickup_time_quick_choices == '0') {
                jQuery(".pickup-time-calendar").addClass('active');
            } else {
                let now = moment(new Date());
                jQuery(".pickup-time-calendar").removeClass('active');
                jQuery('#pickup_time_datetimepicker').val(now.format("DD-MMM-YYYY HH:mm"));
            }
        })
    },
    getExtraServices: function (quotes_response = [], item = {}) {
        let selected_vehicle_type = JSON.parse(item.vehicle_type);
        let vehicle_type_id = selected_vehicle_type.vehicle_type_id;


        const $delivereeApiKey = window.delivereeConfig.delivereeApiKey.api_key;
        var urlVehicleType = window.delivereeConfig.delivereeApiKey.api_url + "/vehicle_types/" + vehicle_type_id + "/extra_services?time_type=now";
        jQuery.ajax({
            headers: {
                'Content-Type': "application/json",
                "Authorization": $delivereeApiKey,
                "Accept-Language": "en",
            },
            method: "GET",
            url: urlVehicleType.replace(/(https?:\/\/)|(\/)+/g, "$1$2"),
            success: function (response) {
                let extraServices = response.data;
                order_list.showPopupExtraServices(extraServices, quotes_response, item);
            },
            error: (jqXHR, textStatus, errorThrown) => {
                console.log('The following error occured: ' + textStatus, errorThrown);
            }
        });

    },

    showPopupExtraServices: function (extraServices, quotes_response, item) {
        let tbody = '';
        let selected_vehicle_type = JSON.parse(item.vehicle_type);
        let extra_services_goods_insurance = {
            extra_requirement_id: 0,
            selected_amount: 1,
            extra_requirement_pricing_id: 0
        };

        let insurance_policy_link = jQuery('#insurance_policy_link').data('insurance-policy-link')

        for (let index = 0; index < extraServices.length; index++) {
            const element = extraServices[index];
            let unit_price = (element.unit_price == 0) ? '-' : order_list.numberWithCommas(element.unit_price, selected_vehicle_type.currency);


            if (element.pricing_method != 'by_options') {
                tbody += '<tr valign="top">';
                tbody += '<th scope="row" class="titledesc">';
                tbody += '<label >' + element.name + '</label>';
                tbody += '<br/>';
                tbody += '<div class="unit-price" >' + unit_price + '</div>';
                tbody += '</th>';
                tbody += '<td class="forminp forminp-deliveree-readonly">';
                tbody += '<fieldset> <input class="input-extra-services" value="' + element.id + '" data-unit-price="' + element.unit_price + '" type="checkbox" /> </fieldset>';
                tbody += '</td>';
                tbody += '</tr>';
            } else {

                tbody += '<tr class="goods-insurance" valign="top">';
                tbody += '<th scope="row" class="titledesc">';
                tbody += '<label >' + element.name + '</label>';
                tbody += '</th>';
                tbody += '<td class="forminp forminp-deliveree-readonly">';
                tbody += '</td>';
                tbody += '</tr>';
                tbody += '<tr class="goods-insurance small-title" valign="top">';
                tbody += '<th scope="row" class="titledesc">';
                tbody += '<label > Coverage</label>';
                tbody += '</th>';
                tbody += '<td class="forminp forminp-deliveree-readonly">Price</td>';
                tbody += '</tr>';

                extra_services_goods_insurance.extra_requirement_id = element.id;


                for (let index_pricings = 0; index_pricings < element.pricings.length; index_pricings++) {
                    const pricings = element.pricings[index_pricings];
                    let checked = '';
                    if (pricings.fees === 0) {
                        checked = 'row-small-table-checked';
                        extra_services_goods_insurance.extra_requirement_pricing_id = pricings.id;
                    }

                    tbody += '<tr data-fees="' + pricings.fees + '" data-id="' + pricings.id + '" class="goods-insurance row-small-table ' + checked + '" valign="top">';
                    tbody += '<th scope="row" class="titledesc">';
                    tbody += '<label >' + pricings.display_level_price + '</label>';
                    tbody += '</th>';
                    tbody += '<td class="forminp forminp-deliveree-readonly">' + pricings.display_fees + '</td>';
                    tbody += '</tr>';
                }

                // if (index == (extraServices.length - 1)) {
                tbody += '<tr class="goods-insurance" >';
                tbody += '<td colspan="2" style="text-align:left;    padding: 7px 0;" ><input checked="checked" id="insurance-policy" type="checkbox" > <label for="insurance-policy"> I agree with the <a target="_blank" href="' + insurance_policy_link + '">Insurance Policy<a/></label></td>';
                tbody += '</tr>';
                // }

            }
        }



        Swal.fire({
            title: 'Select Extra Service #' + item.order_id,
            html:
                '<div>' +
                '<table id="select-extra-service" class="form-table">' +
                '<tbody>' + tbody + '</tbody>' +
                '</table>' +
                '<table id="footer-extra-service" class="form-table">' +
                '<tbody>' +
                '<tr valign="top">' +
                '<th scope="row" class="titledesc">' +
                '<label > Extra Services <span class="woocommerce-help-tip" data-tooltip="The total price for extra services on this booking."></span></label>' +
                '</th>' +
                '<td class="forminp forminp-deliveree-readonly">' + window.delivereeConfig.deliveree_name + ' Receives <span class="woocommerce-help-tip" data-tooltip="' + window.delivereeConfig.deliveree_name + '\'s total price for this booking, including extra services."></span></td>' +
                '</tr>' +
                '<tr valign="top">' +
                '<th scope="row" class="titledesc">' +
                '<label ><span class="footer-price">' + selected_vehicle_type.currency + '<span/> <span data-total-extra-services="0"   data-extra-services-goods-insurance="' + JSON.stringify(extra_services_goods_insurance).replaceAll("\"", "'") + '" data-list-extra-services="[]"  id="total_extra_services" class="footer-price">0<span/></label>' +
                '</th>' +
                '<td class="forminp forminp-deliveree-readonly"><span class="footer-price">' + selected_vehicle_type.currency + '<span/> <span id="total_fees_extra_services" data-total-fees-extra-services="' + form_total_fees + '" class="footer-price">' + order_list.numberWithCommas(form_total_fees, '') + '<span/></td>' +
                '</tr>' +
                '</tbody>' +
                '</table>' +
                '</div>',
            focusConfirm: false,
            confirmButtonText: 'Confirm Booking',
            cancelButtonText: 'Back',
            showCancelButton: true,
            reverseButtons: true,
            preConfirm: () => { },
            customClass: {
                container: 'select-vehicle',
                popup: 'popup-select-vehicle',
                header: 'header-select-vehicle',
                title: 'title-select-vehicle',
                closeButton: '...',
                icon: '...',
                image: '...',
                content: 'content-select-vehicle',
                htmlContainer: '...',
                input: '...',
                inputLabel: '...',
                validationMessage: '...',
                actions: 'actions-select-vehicle',
                confirmButton: 'confirmButton-select-vehicle',
                denyButton: 'denyButton-select-vehicle',
                cancelButton: 'cancelButton-select-vehicle',
                loader: '...',
                footer: '....'
            },
        }, function () {

        }).then((result) => {
            if (result.isConfirmed) {
                let confirm_vehicle_type = JSON.parse(item.vehicle_type);
                let now = moment(new Date());
                let curent_pickup_time = moment(save_value_pickup_time_datetimepicker, "DD-MMM-YYYY HH:mm", true);//.add(save_value_pickup_time_quick_choice_id, 'minutes');
                let time_type = (curent_pickup_time > now) ? 'schedule' : 'now';
                let pickup_time = (curent_pickup_time > now) ? curent_pickup_time.toISOString() : '';
                let extra_services = [];
                let element_extra_services = jQuery('#total_extra_services');
                let list_extra_services = element_extra_services.data('list-extra-services');
                let string_extra_services_goods_insurance = element_extra_services.data('extra-services-goods-insurance');
                let extra_services_goods_insurance = JSON.parse(string_extra_services_goods_insurance.replaceAll("'", "\""));

                for (let index = 0; index < list_extra_services.length; index++) {
                    let extra_service_id = list_extra_services[index];
                    extra_services.push({ extra_requirement_id: parseInt(extra_service_id), selected_amount: 1 })
                }

                extra_services.push(extra_services_goods_insurance)

                let data = {
                    order_id: item.order_id,
                    vehicle_type_id: confirm_vehicle_type.vehicle_type_id,
                    time_type: time_type,
                    pickup_time: pickup_time,
                    extra_services: extra_services,
                    vehicle_type: confirm_vehicle_type,
                    quick_choice_id: save_value_pickup_time_quick_choice_id
                };


                order_list.dontShowPopupCreateBooking(data);

            } else if (result.isDismissed) {
                order_list.showConfirmBooking(quotes_response, item);
            }
        })

        jQuery('.woocommerce-help-tip').darkTooltip();


        jQuery('body').on('change', '#insurance-policy', function (event) {
            let isChecked = jQuery(this).is(":checked")
            if (isChecked) {
                jQuery('.confirmButton-select-vehicle').prop('disabled', false);
            } else {
                jQuery('.confirmButton-select-vehicle').prop('disabled', true);
            }
        })




    },
    addExtraServices: function () {
        jQuery('body').on('change', '.input-extra-services', function (event) {
            let unit_price = jQuery(this).data("unit-price");
            let extra_service_id = jQuery(this).val();
            let element_extra_services = jQuery('#total_extra_services');
            let total_extra_services = element_extra_services.data('total-extra-services');
            let list_extra_services = element_extra_services.data('list-extra-services');

            if (jQuery(this).is(":checked")) {
                total_extra_services += unit_price;
                list_extra_services.push(extra_service_id)

            } else {
                let index = list_extra_services.indexOf(extra_service_id);
                if (index > -1) {
                    list_extra_services.splice(index, 1);
                    total_extra_services -= unit_price;
                }
            }

            element_extra_services.data('total-extra-services', total_extra_services);
            element_extra_services.data('list-extra-services', list_extra_services);
            element_extra_services.html(order_list.numberWithCommas(total_extra_services, ''));

            let total_extra_fees_services = jQuery('#total_fees_extra_services').data('total-fees-extra-services');
            jQuery('#total_fees_extra_services').html(order_list.numberWithCommas(total_extra_fees_services + total_extra_services, ''));


        });
    },
    /*
     data = {
        "order_id": '1,2,3',
        "vehicle_type_id": tr.find('.select_vehicle_type_id').val(),
        "time_type": 'now',
        "pickup_time" : new Date(correctDateTimeStr).toISOString();
    };
    */
    createBooking: function (data) {
        mscConfirm("Create Booking", "Are you sure create booking with order " + data['order_id'], function () {
            order_list.dontShowPopupCreateBooking(data);
        });
    },
    affterCreateBooking: function (data) {
        mscConfirm({
            title: 'Booking Created',
            subtitle: 'Your booking with the order number ' + data['order_id'] + ' has been created successfully. Please go to the Bookings section to check the booking status.',  // default: ''
            okText: 'Go to Bookings',
            cancelText: 'Ok', // default: Cancel,
            dismissOverlay: true, // default: false, closes dialog box when clicked on overlay.
            onOk: function () {
                let site_url = jQuery('#get_site_url').data('site-url')
                window.location.href = site_url + '/wp-admin/admin.php?page=bookings_deliveree';
            },
            onCancel: function () {
                window.location.reload();
            }
        });
    },
    dontShowPopupCreateBooking: function (data, isBulkBooking = false) {
        if (!isBulkBooking) {
            jQuery.LoadingOverlay("show");
        }

        data.action = "createOrderMultiple";
        return jQuery.ajax({
            url: jQuery('#urlForm').data("admin-url-ajax"),
            type: 'POST',
            dataType: 'json',
            async: true,
            data: data
        }).done(function (response) {
            if (!isBulkBooking) {
                jQuery.LoadingOverlay("hide");
                if (response.status) {
                    order_list.affterCreateBooking(data);
                } else {
                    mscAlert(response.message);
                }
            }
        });
    },
    deleteBoookingOrders: function () {
        jQuery('body').on('click', 'a.delete-order-item', function (event) {
            event.preventDefault();
            const valid = jQuery(this).attr('data-id');
            const order_id = jQuery(this).attr('data-order-id');
            mscConfirm("Are You Sure?", "You’re about to delete order number #" + order_id + ". After deleting, the order status in your platform will be shown as “Cancelled”.", function () {
                jQuery.LoadingOverlay("show");

                jQuery.ajax({
                    url: jQuery('#urlForm').data("admin-url-ajax"),
                    type: 'POST',
                    dataType: 'json',
                    async: true,
                    data: {
                        action: "deleteBoookingOrders",
                        ids: [valid],
                    }
                }).done(function (response) {
                    jQuery.LoadingOverlay("hide");
                    location.reload();
                });
            });
        });
    },
    trashBoookingOrders: function () {
        jQuery('body').on('click', 'a.move-to-trash', function (event) {
            event.preventDefault();
            const valid = jQuery(this).attr('data-id');
            jQuery.LoadingOverlay("show");

            jQuery.ajax({
                url: jQuery('#urlForm').data("admin-url-ajax"),
                type: 'POST',
                dataType: 'json',
                async: true,
                data: {
                    action: "trashBoookingOrders",
                    ids: [valid],
                }
            }).done(function (response) {
                jQuery.LoadingOverlay("hide");
                location.reload();
            });
        });
    },
    restoreBoookingOrders: function () {
        jQuery('body').on('click', 'a.restore-boooking-orders', function (event) {
            event.preventDefault();
            const valid = jQuery(this).attr('data-id');
            jQuery.LoadingOverlay("show");

            jQuery.ajax({
                url: jQuery('#urlForm').data("admin-url-ajax"),
                type: 'POST',
                dataType: 'json',
                async: true,
                data: {
                    action: "restoreBoookingOrders",
                    ids: [valid],
                }
            }).done(function (response) {
                jQuery.LoadingOverlay("hide");
                location.reload();
            });
        });
    },

    addExtraServicesGoodsInsurance: function () {
        jQuery('body').on('click', '.row-small-table', function (event) {
            let element_extra_services = jQuery('#total_extra_services');
            let total_extra_services = element_extra_services.data('total-extra-services');
            let list_extra_services = element_extra_services.data('list-extra-services');
            let string_extra_services_goods_insurance = element_extra_services.data('extra-services-goods-insurance');
            let extra_services_goods_insurance = JSON.parse(string_extra_services_goods_insurance.replaceAll("'", "\""));
            let ischecked = jQuery(this).hasClass("row-small-table-checked");
            let all_row_small_table = jQuery('.row-small-table');
            let checked_fees = jQuery(this).data("fees");
            let checked_extra_service_id = jQuery(this).data("id");

            all_row_small_table.removeClass("row-small-table-checked");

            if (ischecked) {
                total_extra_services -= checked_fees;
                extra_services_goods_insurance.extra_requirement_pricing_id = 0;

            } else {
                for (let index = 0; index < all_row_small_table.length; index++) {
                    const row_small_table = all_row_small_table[index];
                    let extra_service_id = jQuery(row_small_table).data("id");
                    if (extra_service_id == extra_services_goods_insurance.extra_requirement_pricing_id) {
                        let fees = jQuery(row_small_table).data("fees");
                        total_extra_services -= fees;
                    }
                }

                jQuery(this).addClass("row-small-table-checked");
                total_extra_services += checked_fees;
                extra_services_goods_insurance.extra_requirement_pricing_id = checked_extra_service_id;
            }

            element_extra_services.data('total-extra-services', total_extra_services);
            element_extra_services.data('list-extra-services', list_extra_services);
            element_extra_services.data('extra-services-goods-insurance', JSON.stringify(extra_services_goods_insurance).replaceAll("\"", "'"));
            element_extra_services.html(order_list.numberWithCommas(total_extra_services, ''));

            let total_extra_fees_services = jQuery('#total_fees_extra_services').data('total-fees-extra-services');
            jQuery('#total_fees_extra_services').html(order_list.numberWithCommas(total_extra_fees_services + total_extra_services, ''));
        });
    },
};

(function ($) {
    jQuery('tbody').on('click', '.toggle-row', function () {
        jQuery(this).closest('tr').toggleClass('is-expanded');
    });
    $('body').on('change', '.select_vehicle_type_id', function () {
        var option = $('option:selected', this).attr('data-value');
        $(this).closest('tr').find('.booking_fee').text(option);
    });

    $("body").on("click", ".button-dropdown", (e) => {
        jQuery(e.currentTarget).toggleClass("show");
    });

    $('body').on('click', '.confirmBooking', function () {
        var orderIdsMessage = $(this).attr('data-order-ids-for-message');
        var tr = $(this).closest('tr');
        var time_type = $('.select_time_type').val();
        var pickup_time = $('.select_pickup_time').val();

        if (time_type == 'schedule' && pickup_time === '') {
            mscAlert("Please select pickup time");
            return false;
        }

        var data = {
            "order_id": orderIdsMessage,
            "vehicle_type_id": tr.find('.select_vehicle_type_id').val(),
            "time_type": time_type,
        };

        if (time_type == 'schedule') {
            let correctDateTimeStr = '';
            try {
                const dateTimePartsArr = pickup_time.split(' ');
                const datePartsArr = dateTimePartsArr[0].split('/');
                const timePartsArr = dateTimePartsArr[1].split(':');
                correctDateTimeStr = datePartsArr[2] + '/' + datePartsArr[1] + '/' + datePartsArr[0] + ' ' + timePartsArr[0] + ':' + timePartsArr[1] + ' GMT+' + $gmtoffset;
            } catch (e) {
                alert('Time format is incorrect !');
                console.log(e);
                return false;
            }
            data['pickup_time'] = new Date(correctDateTimeStr).toISOString();
        }

        order_list.createBooking(data);
    });



    var d = new Date;
    var logic = function (currentDateTime) {
        if (currentDateTime.getDate() == d.getDate()) {
            this.setOptions({
                minTime: d.getHours() + ':' + d.getMinutes()
            });
        } else {
            this.setOptions({
                minTime: '00:00'
            });
        }
    };



    $('.pickup_time_datepicker').datetimepicker({
        format: 'd/m/Y H:i',
        minDate: new Date,
        minTime: d.getHours() + ':' + d.getMinutes(),
        onChangeDateTime: logic
    });


    $("body").on("click", ".collapse", (e) => {
        $('tr' + e.currentTarget.dataset.show).toggle();
        $(e.target).closest('thead').next('tbody').toggle();
        $(e.target).closest('thead').find('.collapse-select').toggleClass("dashicons-arrow-up");
        if (e.currentTarget.dataset.show == '.selected') {
            $('tr.optimise-route').toggle();
        }
    });


    $('body').on('click', '.viewErrorMessage', function (event) {
        var message = $(this).closest('div').find('p.error-message').text();
        Swal.fire({
            text: message
        });
    });


    var tableOrderDeliveree = $('#list_booking_order_select_paging');
    // $('body').on('click', 'input.checkbox-confirm-booking', function (event) {
    //     event.preventDefault();
    //     var ids = $('#order_selected').val();
    //     var value = $(this).val();
    //     var thead = $(this).closest('tbody').prev('thead');

    //     if (thead.attr('class') === 'list-selected') {
    //         var values = ids.split('-');
    //         var index = values.indexOf(value);
    //         if (index >= 0) {
    //             values.splice(index, 1);
    //             ids = values.join('-');
    //         }
    //     } else {
    //         ids += (ids === '') ? $(this).val() : '-' + $(this).val();
    //     }
    //     $('#order_selected').val(ids);
    //     tableOrderDeliveree.submit();
    // });

    // $('body').on('click', 'input.checkbox-all-confirm-booking', function (event) {
    //     event.preventDefault();
    //     let inputs = $('input.checkbox-confirm-booking');
    //     let ids = [];
    //     for (let index = 0; index < inputs.length; index++) {
    //         const element = inputs[index];
    //         let value = $(element).val();
    //         ids.push(value)
    //     }
    //     $('#order_selected').val(ids.join('-'));
    //     // tableOrderDeliveree.submit();
    // });

    // $('body').on('click', 'input.uncheckbox-all-confirm-booking', function (event) {
    //     event.preventDefault();
    //     $('#order_selected').val('');
    //     // tableOrderDeliveree.submit();
    // });


}(jQuery));
