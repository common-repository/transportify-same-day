(function ($) {

    document.addEventListener("DOMContentLoaded", function (event) {
        const $delivereeApiKey = window.delivereeConfig.delivereeApiKey.api_key;
        const $delivereeStoreAdress = window.delivereeConfig.delivereeStoreAdress;
        const $delivereeDateTimeFormat = window.delivereeConfig.delivereeDateTimeFormat;

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

        jQuery('#pickup_time_create').datetimepicker({
            format: $delivereeDateTimeFormat,
            minDate: new Date,
            minTime: d.getHours() + ':' + d.getMinutes(),
            onChangeDateTime: logic
        });




        jQuery("body").on("click", ".button-dropdown", (e) => {
            jQuery(e.currentTarget).toggleClass("show");
        }).on("click", ".cancel-booking", (e) => {
            var url = window.delivereeConfig.delivereeApiKey.api_url + '/deliveries/' + e.currentTarget.dataset.id + "/cancel";
            jQuery.ajax({
                headers: {
                    "Authorization": $delivereeApiKey
                },
                dataType: 'json',
                method: "POST",
                crossDomain: true,
                url: url.replace(/(https?:\/\/)|(\/)+/g, "$1$2"),
                contentType: 'application/json',
                success: function () {
                    location.reload();
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    Swal.fire({
                        icon: 'error',
                        text: 'An internal error occurred while calling cancel'
                    });
                }
            });
        });

        if (document["deliveree-create-booking"]) {
            let indexDeliveree = 3;

            jQuery(document["deliveree-create-booking"]).parsley({
                trigger: "change",
                errorsWrapper: "<div></div>",
                errorTemplate: "<span class='required'></span>",
            }).on("form:submit", (e) => {
                const data = { locations: [] };
                let object = {}
                for (let i = 0; i < e.fields.length; i++) {
                    if (e.fields[i].element.name.indexOf("locations__") > -1) {
                        const key = e.fields[i].element.name.replace("locations__", "");
                        if (object[key]) {
                            data.locations.push(object);
                            object = {};
                        }
                        object[key] = e.fields[i].value;
                    } else {
                        data[e.fields[i].element.name] = e.fields[i].value;
                    }
                }
                const pickUpDate = new Date(dateTimeReverse($delivereeDateTimeFormat, jQuery('#pickup_time_create').val()));
                data.locations.push(object);
                data.vehicle_type_id = jQuery('#vehicle_type_id').val();
                data.note = jQuery('#note').val().trim();
                data.time_type = jQuery('#time_type').val();
                data.pickup_time = pickUpDate.toMongoISOString();
                data.job_order_number = jQuery('#job_order_number').val().trim();
                $.LoadingOverlay("show");
                var url = window.delivereeConfig.delivereeApiKey.api_url + "/deliveries";
                jQuery.ajax({
                    headers: {
                        "Authorization": $delivereeApiKey
                    },
                    dataType: 'json',
                    method: "POST",
                    crossDomain: true,
                    url: url.replace(/(https?:\/\/)|(\/)+/g, "$1$2"),
                    contentType: 'application/json',
                    data: JSON.stringify(data),
                    success: function (response) {
                        window.location.href = window.location.href.replace("bookings_deliveree_form", "bookings_deliveree")
                    },
                    error: (jqXHR, textStatus, errorThrown) => {
                        $.LoadingOverlay("hide");
                        console.log(jqXHR.responseText);
                        Swal.fire({
                            icon: 'error',
                            text: JSON.parse(jqXHR.responseText).message,
                        })
                    }
                });
                return false;
            });
            const rowBtnAddMore = jQuery(".tr-deliveree-add-more");
            jQuery(".deliveree-add-more").click((e) => {
                if (jQuery('#item-deliveree tr').length > 15) { return; }
                const html = jQuery("<html>").append(
                    jQuery("<tr>", { class: "item-deliveree-" + indexDeliveree }).append(
                        jQuery("<td>", { class: "errors-group-" + indexDeliveree }).append(
                            jQuery("<div>", { class: "input-group" }).append(
                                jQuery("<div>", { class: "input-group-prepend" }).append(
                                    jQuery("<div>", { class: "input-group-text" }).append(jQuery("[class*=item-deliveree-]").length + 1)
                                ),
                                jQuery("<input>", {
                                    name: "locations__address",
                                    type: "text",
                                    required: true,
                                    readonly: true,
                                    "data-parsley-errors-container": ".errors-group-" + indexDeliveree
                                }),
                                jQuery("<input>", { name: "locations__latitude", type: "text", class: "hidden" }),
                                jQuery("<input>", { name: "locations__longitude", type: "text", class: "hidden" })
                            )
                        ),
                        jQuery("<td>").append(
                            jQuery("<input>", { name: "locations__note", type: "text" })
                        ),
                        jQuery("<td>").append(
                            jQuery("<input>", { name: "locations__recipient_name", type: "text", required: true })
                        ),
                        jQuery("<td>").append(
                            jQuery("<input>", {
                                name: "locations__recipient_phone",
                                type: "tel",
                                "data-inputmask": "'mask': '+(99) 9999-9999{1,3}'",
                                required: true
                            })
                        ),
                        jQuery("<td>").append(
                            jQuery("<button>", {
                                class: "page-title-action remore-item-deliveree",
                                "data-item": ".item-deliveree-" + indexDeliveree
                            }).append("-")
                        )
                    )
                ).html();
                rowBtnAddMore.before(html)

                if (jQuery("#item-deliveree tr").length === 24) {
                    rowBtnAddMore.hide();
                }

                jQuery(":input").inputmask();
                indexDeliveree++
            })
            jQuery(":input").inputmask();

            var urlVehicleType = window.delivereeConfig.delivereeApiKey.api_url + "/vehicle_types";
            jQuery.ajax({
                headers: {
                    'Content-Type': "application/json",
                    "Authorization": $delivereeApiKey,
                    "Accept-Language": "en",
                },
                method: "GET",
                url: urlVehicleType.replace(/(https?:\/\/)|(\/)+/g, "$1$2"),
                success: function (response) {
                    let html = ""
                    jQuery(response.data).each((index, item) => {
                        html += jQuery("<html>").append(jQuery("<option>", { value: item.id }).append(item.name)).html();
                    })
                    jQuery("#vehicle_type_id").html(html);
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    console.log('The following error occured: ' + textStatus, errorThrown);
                }
            });
            let address;
            let latitude;
            let longitude;
            let parentAddressInputGroup;
            jQuery("body").on("click", ".remore-item-deliveree", (e) => {
                jQuery(e.currentTarget.dataset.item).remove();
                jQuery.each(jQuery("[class*=item-deliveree-]"), function (index, item) {
                    jQuery(item).find(".input-group-text").text(index + 1)
                })
                rowBtnAddMore.show();
            }).on("click", "input[name=locations__address]", (e) => {
                parentAddressInputGroup = $(e.currentTarget).closest('.input-group');
                var currentAddress = parentAddressInputGroup.find('input[name=locations__address]').val();
                var currentLatitude = parentAddressInputGroup.find('input[name=locations__latitude]').val();
                var currentLongitude = parentAddressInputGroup.find('input[name=locations__longitude]').val();
                var defaultAddress = (currentAddress != '') ? currentAddress : '';
                var defaultLatitude = (currentLatitude != '') ? currentLatitude : $delivereeStoreAdress.latitude;
                var defaultLongitude = (currentLongitude != '') ? currentLongitude : $delivereeStoreAdress.longtitude;

                Swal.fire({
                    title: 'Pickup in Map',
                    confirmButtonText: "Set",
                    html:
                        '    <div>\n' +
                        '      <input type="text" id="address" style="width: 100%"/>\n' +
                        '      <input type="text" id="latitude" class="hidden"/>\n' +
                        '      <input type="text" id="longitude" class="hidden"/>\n' +
                        '      <div id="map" style="width: 100%; height: 400px;"></div>\n' +
                        '    </div>',
                    icon: false,
                    focusConfirm: false,
                    showCloseButton: false,
                    onRender: (toast) => {
                        address = jQuery('#address');
                        latitude = jQuery('#latitude');
                        longitude = jQuery('#longitude');

                        //get current value if it exist
                        address.val(defaultAddress);
                        latitude.val(defaultLatitude);
                        longitude.val(defaultLongitude);
                        jQuery('#map').locationpicker({
                            location: {
                                latitude: latitude.val(),
                                longitude: longitude.val()
                            },
                            addressFormat: 'places',
                            radius: 0,
                            enableAutocomplete: true,
                            inputBinding: {
                                latitudeInput: latitude,
                                longitudeInput: longitude,
                                radiusInput: null,
                                locationNameInput: address
                            }
                        });
                    },
                    onAfterClose: () => {
                        e.currentTarget.value = address.val();
                        const parent = jQuery(e.currentTarget).parents(".input-group:eq(0)");
                        parent.find("[name=locations__latitude]").val(latitude.val())
                        parent.find("[name=locations__longitude]").val(longitude.val())
                    }
                });
            });
        }

    });


}(jQuery));


jQuery(document).ready(function () {
    booking.setSearchDate();
    booking.setSelectStatus();
    booking.setSelectShippingMethods();
});

var booking = {
    setSelectShippingMethods: function () {
        document.multiselect('#select_shipping_methods');
        let selected_methods = jQuery('#select_shipping_methods').data('selected');
        let total_methods = jQuery('#select_shipping_methods').data('total');
        jQuery('#select_shipping_methods_input').attr("placeholder", "All Methods (" + total_methods + ")");
        if (selected_methods != null) {
            selected_methods.forEach(method => {
                document.multiselect('#select_shipping_methods').select(method);
            });
        }
        jQuery('label[for="select_shipping_methods_input"]').attr('for', 'dropdown_arrow_select_shipping_methods')
        jQuery('label[for="dropdown_arrow_select_shipping_methods"]').click((e) => {
            e.defaultPrevented;
            let itemList = jQuery("#select_shipping_methods_itemList");
            let active = itemList.hasClass('active')
            if (active) {
                itemList.removeClass('active');
            } else {
                itemList.addClass('active');
            }
        })
    },
    setSelectStatus: function () {
        document.multiselect('#select_status');
        let selected = jQuery('#select_status').data('selected');
        let total = jQuery('#select_status').data('total');
        jQuery('#select_status_input').attr("placeholder", "All statuses (" + total + ")");
        if (selected != null) {
            selected.forEach(element => {
                document.multiselect('#select_status').select(element);
            });
        }
        jQuery('label[for="select_status_input"]').attr('for', 'dropdown_arrow_select_status')
        jQuery('label[for="dropdown_arrow_select_status"]').click((e) => {
            e.defaultPrevented;
            let itemList = jQuery("#select_status_itemList");
            let active = itemList.hasClass('active')
            if (active) {
                itemList.removeClass('active');
            } else {
                itemList.addClass('active');
            }
        })
    },
    setSearchDate: function () {
        let url_search_date = new URL(window.location.href);
        let search_date = url_search_date.searchParams.get('d');

        let search_date_daterangepicker = {
            locale: {
                format: 'DD-MMM-Y'
            },

        };

        if (search_date == null || search_date == "") {
            search_date_daterangepicker.startDate = moment().startOf('hour').subtract(90, 'days');
            search_date_daterangepicker.endDate = moment().startOf('hour');
        }else if(search_date=='none'){
            search_date_daterangepicker.autoUpdateInput= false;
        }

        jQuery('#filter_datetimepicker').daterangepicker(search_date_daterangepicker);

        jQuery('#filter_datetimepicker').on('apply.daterangepicker', function(ev, picker) {
            jQuery(this).val(picker.startDate.format('DD-MMM-Y') + ' - ' + picker.endDate.format('DD-MMM-Y'));
        });
      
        jQuery('#filter_datetimepicker').on('cancel.daterangepicker', function(ev, picker) {
            jQuery(this).val('');
        });
      
    }
}

