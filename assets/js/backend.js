(function ($) {

    function delevereeToggleButtons(args) {
        $.when($('#deliveree-buttons').remove()).then(function () {
            $('#btn-ok').hide().after(wp.template('deliveree-buttons')(delivereeGetButtons(args)));
        });
    }
    function delivereeGetButtons(args) {
        var buttonDefault = {
            btn_left: {
                id: 'btn-add-more-services',
                label: 'Add More Services',
                icon: ''
            },
            btn_right: {
                id: 'save-settings',
                icon: 'yes',
                label: 'Save Changes',
            },
        };

        if (!args) {
            return buttonDefault;
        }

        return args;
    }

    let pricePerKmInput = [];

    var delievereeBackend = {
        addMoreServices() {
            $('#btn-add-more-services').on('click', function () {
                $('.wc-modal-shipping-method-settings').closest('section').find('button.modal-close').trigger('click');
                $('.wc-shipping-zone-add-method').trigger('click');
                $('select[name=add_method_id]').val('deliveree_shipping_method');
            });
        },

        addClassGroupMaxMin: function () {
            $('input[data-class="fieldset"]').each(function () {
                $(this).closest('fieldset').addClass('fieldset fieldset-group-min-max');
                var moveto = $(this).attr('moveto');
                if (moveto) {
                    $(this).closest('fieldset').addClass(moveto);
                }

                var afterContent = $(this).attr('data-after');
                if (afterContent) {
                    $(this).closest('fieldset').addClass(afterContent);
                }
                var beforeContent = $(this).attr('data-before');
                if (beforeContent) {
                    $(this).closest('fieldset').addClass(beforeContent);
                }
            });

            //move group min, max
            $('input[data-move=move_to]').each(function () {
                var parent = $(this).closest('tr');
                var inputClassMoveTo = $(this).attr('data-group-input-move');
                $(this).closest('fieldset').appendTo($('input[data-move=' + inputClassMoveTo + ']').closest('td'));
                parent.remove();
            });
        },
        listPerPrice: function () {
            $('input.display_none_price_per_km').each(function () {
                $(this).closest('table').addClass('list_price_per_km');
                $(this).closest('tr').attr('data-group', $(this).attr('data-group'));
            });
            for (var i = 1; i <= 20; i++) {
                $('table.form-table tr[data-group=' + i + ']').each(function (index) {
                    $(this).find('legend').removeClass('screen-reader-text');

                    if (index > 0) {
                        $(this).find('fieldset').closest('fieldset').appendTo($('table.form-table tr[data-group=' + i + ']:first td'));
                        $(this).remove();
                    } else if (i === 1) {
                        $(this).find('label').text('Price Per KM');
                    } else {
                        $(this).find('label').text('');
                    }
                });
            }

            $('.start_price_group').after('<table id="table_start_price"><tbody></tbody><tfoot><tr><td colspan="3"><a href="javascript:;" type="button" id="addMoreTiger" class="addMoreTiger">Add More Tiers</a></td></tr></tfoot></table>');
            var dataJson = $('.start_price_group').val();
            if (dataJson) {
                var startPriceValue = JSON.parse($('.start_price_group').val());
                if (startPriceValue !== undefined && startPriceValue.length > 0) {
                    for (var index in startPriceValue) {
                        var indexItem = delievereeBackend.addMoreTiger();
                        var priceItem = $('.price-item-' + indexItem);
                        priceItem.find('.start').val(startPriceValue[index].start);
                        priceItem.find('.end').val(startPriceValue[index].end);
                        priceItem.find('.price').val(startPriceValue[index].price);
                    }
                } else {
                    delievereeBackend.addMoreTiger();
                    delievereeBackend.autoAddStartValuePricePerKM();
                }
            } else {
                delievereeBackend.addMoreTiger();
                delievereeBackend.autoAddStartValuePricePerKM();
            }

            $('#addMoreTiger').on('click', function (e) {
                e.preventDefault();
                //validate max value need input
                var maxDistance = $('#woocommerce_deliveree_shipping_method_service_setting_shipping_maximum_distance').val();
                if (maxDistance == 0 || maxDistance === '') {
                    alert('Please enter value of maximum distance');
                } else {
                    var inputMaxEmpty = true;
                    var lastEndValue = 0;
                    if ($('.price-item-input').length > 0) {
                        $('.price-item-input').each(function (index) {
                            if ($(this).find('input.input_max_end').val() == '') {
                                inputMaxEmpty = false;
                            } else {
                                lastEndValue = parseFloat($(this).find('input.input_max_end').val());
                            }
                        });
                    }
                    if (inputMaxEmpty && lastEndValue >= maxDistance) {
                        alert('The end value equals the maximum distance.');
                    } else if (!inputMaxEmpty) {
                        alert('Please enter value for "End" km');
                    } else {
                        delievereeBackend.addMoreTiger();
                        delievereeBackend.autoAddStartValuePricePerKM();
                    }
                }
            })
        },
        autoAddStartValuePricePerKM() {
            if ($('.price-item-input').length > 0) {
                $('.price-item-input').each(function (index) {
                    var min = $(this).find('input.input_min_start').val();
                    var max = $(this).find('input.input_max_end').val();
                    pricePerKmInput[index] = {
                        min: min,
                        max: max
                    }
                });

                $('.price-item-input').each(function (index) {
                    if (0 == index) {
                        $(this).find('input.input_min_start').val(0);
                    } else {
                        $(this).find('input.input_min_start').val(pricePerKmInput[(index - 1)].max);
                    }
                });
            }
        },
        addMoreTiger() {
            if ($('.price-item').length < 20) {
                var uid = Math.random().toString(36).substring(2, 10);
                var index = Math.floor(Math.random() * 100000000);
                var tr1 = '<tr data-index="' + uid + '" ><td class="col">Start</td><td class="col">End</td><td class="col">Price per KM</td></tr>';
                var btnDelete = ' <a href="javascript:;" class="delete-start-price">X</a>';
                var tr2 = '<tr data-index="' + uid + '" class="price-item price-item-INDEX price-item-input"><td class="col"><input class="start validate_number input_min_start" readonly="readonly" name="price_per_km_start_INDEX" type="text"></td><td class="col"><input class="end validate_number input_max_end" name="price_per_km_end_INDEX" type="text"></td><td class="col"><input class="price validate_number" name="price_per_km_price_INDEX" type="text"> ' + btnDelete + '</td></tr>';
                tr2 = tr2.replace(/INDEX/g, index);
                $('#table_start_price tbody').append(tr1 + tr2);
                delievereeBackend.validateInputNumber();
                delievereeBackend.autoAddStartValuePricePerKM();
                return index;
            }
        },
        deleteTier() {
            $('body').on('click', '.delete-start-price', function (e) {
                e.preventDefault();
                if ($('.price-item').length > 1) {
                    var index = $(this).closest('tr').attr('data-index');
                    $('tr[data-index=' + index + ']').remove();
                    delievereeBackend.autoAddStartValuePricePerKM();
                } else {
                    $(this).closest('tr.price-item-input').find('input').val(0);
                }
            })
        },
        validateInputNumber() {

            $(document).on('keypress keyup blur', 'input.validate_number_decimal', function (evt) {
                var decimal = /\./;
                var value = $(this).val();
                evt = (evt) ? evt : window.event;
                var charCode = (evt.which) ? evt.which : evt.keyCode;

                if (charCode == 46 && !value.match(decimal)) {
                    return true;
                } else if ((charCode > 31 && (charCode < 48 || charCode > 57)) || charCode == 46) {
                    return false;
                }
                return true;
            });

            $(document).on('keypress keyup blur', 'input.validate_number', function (evt) {
                evt = (evt) ? evt : window.event;
                var charCode = (evt.which) ? evt.which : evt.keyCode;
                if (charCode == 46) {
                    return true;
                }
                if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                    return false;
                }
                return true;
            });

            $(document).on('change', 'input.validate_required', function (evt) {
                let val = $(evt.target).val();
                let class_errors = $(evt.target).data('class-errors');
                let message = $(evt.target).data('message-required');
                if (val.length > 0) {
                    $('.' + class_errors).html('');
                    $('.add-class-' + class_errors).removeClass('red-border');

                } else {
                    $('.' + class_errors).html(message);
                    $('.add-class-' + class_errors).addClass('red-border');

                }
            });

            $(document).on('change', 'input.validate_number_max', function (evt) {
                let prev = parseFloat($(evt.target).data('val'));
                let max = parseFloat($(evt.target).data('max'));
                let val = parseFloat($(evt.target).val());
                if (val > max) {
                    let newPrev = (prev > max) ? max : prev;
                    $(evt.target).val(newPrev);
                    alert('The number must less than ' + max + '.');
                } else {
                    $(evt.target).data('val', val);
                }
            });


            // let val = $(evt.target).val();
            // $(evt.target).data('val', val == '' ? 0 : val);

            $("input.input_max").on("focusin", function (evt) {
                $(this).data('val', $(this).val());
            }).on("change", function (evt) {
                var prev = $(this).data('val');
                var min = parseFloat($(this).closest('tr').find('.input_min').val());
                var max = parseFloat($(this).val());
                if (prev == $(this).val()) {
                    return true;
                }
                if (min > max) {
                    $(this).val(prev);
                    alert('The minimum must less than the value maximum.');
                }
            });

            $("input.input_min").on("focusin", function (evt) {
                $(this).data('val', $(this).val());
            }).on("change", function (evt) {
                var prev = $(this).data('val');
                var max = parseFloat($(this).closest('tr').find('.input_max').val());
                var min = parseFloat($(this).val());
                if (prev == $(this).val()) {
                    return true;
                }
                if ((min > max) && max > 0) {
                    $(this).val(prev);
                    alert('The minimum must less than the value maximum.');
                    return false;
                }
            });

            $("input.input_max_end").on("focusin", function (evt) {
                $(this).data('val', $(this).val());
            }).on("change", function (evt) {
                var prev = $(this).data('val');
                var min = parseFloat($(this).closest('tr').find('.input_min_start').val());
                var max = parseFloat($(this).val());
                var maxDistance = parseFloat($('#woocommerce_deliveree_shipping_method_service_setting_shipping_maximum_distance').val());
                if (prev == $(this).val()) {
                    return true;
                }

                if (min > max) {
                    $(this).val(prev);
                    alert('The start value must less than the end value.');
                } else if (max > maxDistance) {
                    $(this).val(prev);
                    alert('The end value must less than the value maximum distance.');
                }
                delievereeBackend.autoAddStartValuePricePerKM();
            });

            $("input.input_min_start").on("focusin", function (evt) {
                $(this).data('val', $(this).val());
            }).on("change", function (evt) {
                var prev = $(this).data('val');
                var max = parseFloat($(this).closest('tr').find('.input_max_end').val());
                var min = parseFloat($(this).val());
                if (prev == $(this).val()) {
                    return true;
                }
                if ((min > max) && max > 0) {
                    $(this).val(prev);
                    alert('The start value must less than the end value.');
                }
            });

            $('#woocommerce_deliveree_shipping_method_service_setting_shipping_maximum_distance').on('change', function () {
                $('.price-item-input').each(function (index) {
                    var max = parseFloat($(this).find('input.input_max_end').val());
                    var maxDistance = parseFloat($('#woocommerce_deliveree_shipping_method_service_setting_shipping_maximum_distance').val());
                    if (max > maxDistance) {
                        $(this).find('.delete-start-price').trigger('click');
                    }
                });
            })
        },

        showMinimumPrice() {
            var trMinimumPrice = $('#woocommerce_deliveree_shipping_method_service_setting_shipping_minimum_price').closest('tr');
            var deliveryTypeSetting = $('#woocommerce_deliveree_shipping_method_service_setting_shipping_delivery_type');

            trMinimumPrice.hide();
            deliveryTypeSetting.change(function () {
                trMinimumPrice.hide();
                $(".input_min").prop('disabled', $(this).val() == 'LTL');
                if ($(this).val() == 'LTL') {
                    trMinimumPrice.show();
                    $(".input_min.validate_number_decimal").val(0);
                }
            });
            deliveryTypeSetting.trigger('change')
        },
        submitForm: function (e) {
            e.preventDefault();

            $('#btn-ok').trigger('click');
        },
        renderForm: function () {
            if (!$('#woocommerce_deliveree_shipping_method_service_setting') || !$('#woocommerce_deliveree_shipping_method_service_setting').length) {
                return;
            }

            $('article.wc-modal-shipping-method-settings').addClass('woocommerce_deliveree_modal_setting_method');

            // Submit form
            $(document).off('click', '#deliveree-btn--save-settings', delievereeBackend.submitForm);
            $(document).on('click', '#deliveree-btn--save-settings', delievereeBackend.submitForm);

            //add button
            delevereeToggleButtons();

            //add more button services
            delievereeBackend.addMoreServices();

            delievereeBackend.showMinimumPrice();

            //add class to group max,min
            delievereeBackend.addClassGroupMaxMin();

            //Price Per KM
            delievereeBackend.listPerPrice();
            delievereeBackend.deleteTier();

            //validate
            delievereeBackend.validateInputNumber();
        },

        initForm: function () {
            // Init form
            $(document.body).off('wc_backbone_modal_loaded', delievereeBackend.renderForm);
            $(document.body).on('wc_backbone_modal_loaded', delievereeBackend.renderForm);
        },
        init: function () {
            delievereeBackend.initForm();
        },
        showCustomPickHours: function () {
            jQuery('.input_radio_booking_mode').change(function (e) {
                let input_radio_booking_mode = $(this).val();
                if ('auto_assign_booking_mode' == input_radio_booking_mode) {
                    $('.tr_radio_pickup_hours').addClass('active');
                    $('.tr_input_toggle_deliveree_services_extra_services').removeClass('active');

                } else {
                    $('.tr_radio_pickup_hours').removeClass('active');
                    $('.tr_input_toggle_deliveree_services_extra_services').addClass('active');
                }
            });
        },
        setCustomPickHours: function () {

            let pickup_hours_end;
            let pickup_hours_start = jQuery('#pickup_hours_start').datetimepicker({
                format: 'H:i',
                timepicker: true,
                datepicker: false,
                step: 30,
                onShow: function (ct) {
                    // pickup_hours_end.trigger('open.xdsoft');
                },
            });

            pickup_hours_end = jQuery('#pickup_hours_end').datetimepicker({
                format: 'H:i',
                timepicker: true,
                datepicker: false,
                step: 30,
                onShow: function (ct) {
                    // pickup_hours_start.trigger('open.xdsoft');
                },
            });


        },
        clearSearchInput: function () {
            jQuery(".clear-search-input").click((e) => {
                let clear = jQuery(e.target).data('clear');
                let url_search_date = new URL(window.location.href);
                if (clear == 'd') {
                    url_search_date.searchParams.set('d', 'none');
                } else {
                    url_search_date.searchParams.delete(clear);
                }
                console.log(clear)
                window.location.href = url_search_date.toString();
            })
        },
        delivereeSettingMethod: function () {
            $(document).on('change', 'input[type=radio][name=woocommerce_deliveree_actual_shipping_method_service_setting_shipping_delivery_actual_adjustment]', function () {
                let type = $(this).val();

                if (type === 'premium') {
                    $(this).closest('ul').find('.deliverree_premium_currency_group_input').css('display', 'inline-block');
                    $(this).closest('ul').find('.deliverree_discount_currency_group_input').css('display', 'none');
                } else if (type === 'discount') {
                    $(this).closest('ul').find('.deliverree_discount_currency_group_input').css('display', 'inline-block');
                    $(this).closest('ul').find('.deliverree_premium_currency_group_input').css('display', 'none');
                } else {
                    $(this).closest('ul').find('.deliverree_discount_currency_group_input').css('display', 'none');
                    $(this).closest('ul').find('.deliverree_premium_currency_group_input').css('display', 'none');
                }
            })

            $(document).on('ready', 'input[type=radio][name=woocommerce_deliveree_actual_shipping_method_service_setting_shipping_delivery_actual_adjustment]', function () {
                $(this).trigger('change');
            });

            $(document).on('change', '.adjustment-currency-select', function () {
                let currency = $(this).val();
                let adjustment_type = $(this).data('adjustment-type');
                if (currency === '%') {
                    $('.max-cap-wrapper-' + adjustment_type).css('display', 'flex');
                    $('.deliverree_' + adjustment_type + '_currency_value').data('max', 100);
                    $('.deliverree_' + adjustment_type + '_currency_value').focusin().change();

                } else {
                    $('.max-cap-wrapper-' + adjustment_type).css('display', 'none');
                    $('.deliverree_' + adjustment_type + '_currency_value').data('max', 9999999999);

                }
            })

        },
        loadSelet2DefaultVehicle: function () {

            $(document.body).on('wc_backbone_modal_loaded', function (evt, target) {
                function formatResultDesign(item) {
                    let new_state = item.text;
                    var selectionText = item.text.split(".");
                    var returnString = '<strong>' + selectionText[0] + ' </strong></br><span class="option-vehicle-detail">' + selectionText[1];
                    new_state = jQuery(
                        '<span>' + returnString + ' </span>'
                    );
                    return new_state;
                };

                function formatResultSelection(item) {
                    var selectionText = item.text.split(".");
                    return selectionText[0];
                };

                $('.select-default-vehicle').select2({
                    placeholder: "Select something",
                    minimumResultsForSearch: -1,
                    width: '80%',
                    templateResult: formatResultDesign,
                    templateSelection: formatResultSelection
                }).on("change", function (e) {
                    let text = $(this).find(':selected').html()
                    var selectionText = text.split(".");
                    $('#selected-vehicle-default').html(selectionText[1])

                });;

            });
        },

    };

    $(document).ready(delievereeBackend.init);
    $(window).load(function () {
        delievereeBackend.validateInputNumber();
        delievereeBackend.deleteTier();
        delievereeBackend.setCustomPickHours();
        delievereeBackend.showCustomPickHours();
        delievereeBackend.clearSearchInput();
        delievereeBackend.delivereeSettingMethod();
        delievereeBackend.loadSelet2DefaultVehicle();

    })
}(jQuery));
