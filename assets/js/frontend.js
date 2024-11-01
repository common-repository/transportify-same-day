jQuery(document).ready(function () {
    shipping_calculator.hiddenShippingCalculatorButton();
    shipping_calculator.onChangePostCode();
    shipping_calculator.pinYourAddress();
    shipping_calculator.showErrorCalculator();
    shipping_calculator.validatePostalCode();
    shipping_calculator.searchAddressByGoogleMap();
    shipping_calculator.checkUserLogin();

});

var shipping_calculator = {
    searchAddressByGoogleMap: function () {
        if (window.delivereeConfig.delivereeGoogleApiKey == '')
            return false;

        let address_1 = jQuery('#calc_shipping_address_1');
        let address = address_1.val();
        let latitude_input = jQuery('#calc_shipping_latitude');
        let longitude_input = jQuery('#calc_shipping_longitude');
        let latitude = latitude_input.val() || window.delivereeConfig.delivereeStoreAdress.latitude;
        let longitude = longitude_input.val() || window.delivereeConfig.delivereeStoreAdress.longtitude;

        jQuery('#hidden_map').locationpicker({
            locationName: address,
            location: {
                latitude: latitude,
                longitude: longitude
            },
            addressFormat: 'places',
            radius: 0,
            enableAutocomplete: true,
            inputBinding: {
                latitudeInput: latitude_input,
                longitudeInput: longitude_input,
                radiusInput: null,
                locationNameInput: jQuery('#calc_shipping_address_1')
            },
            onchanged: function (currentLocation, radius, isMarkerDropped) {
                console.log(currentLocation)
            },
        });

        setTimeout(() => {
            jQuery('#calc_shipping_address_1').val(address);
        }, 1000);

    },
    validatePostalCode: function () {
        jQuery(document).on('keypress', '#calc_shipping_postcode', function (event) {
            var keycode = event.which;
            var val = jQuery(this).val();
            if (!(event.shiftKey == false && val.length < 7 && (keycode == 46 || keycode == 8 || keycode == 37 || keycode == 39 || (keycode >= 48 && keycode <= 57)))) {
                event.preventDefault();
            }
        })
    },
    showErrorCalculator: function () {
        jQuery(document.body).on('updated_cart_totals', function () {
            var error = jQuery('.woocommerce-error').first();
            if (error.length) {
                jQuery('#woocommerce-notices-wrapper-calculator').html(jQuery(error[0]).html())
            }
            shipping_calculator.searchAddressByGoogleMap();

        });
    },
    is_blocked: function ($node) {
        return $node.is('.processing') || $node.parents('.processing').length;
    },
    block: function ($node) {
        if (!shipping_calculator.is_blocked($node)) {
            $node.addClass('processing').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }
    },
    unblock: function ($node) {
        $node.removeClass('processing').unblock();
    },
    hiddenShippingCalculatorButton: function () {
        jQuery(document).on('click', '.shipping-calculator-button', function () {
            jQuery(this).hide();
            jQuery('.wc-proceed-to-checkout').addClass('disabled');
        })
    },
    pinYourAddress: function () {
        if (window.delivereeConfig.delivereeGoogleApiKey == '')
            return false;

        jQuery(document).on('click', '#pin-your-address', function (e) {
            e.preventDefault();
            let address;
            let latitude_input;
            let longitude_input;
            let addressComponents;
            Swal.fire({
                title: 'PICKUP IN MAP',
                confirmButtonText: "Set",
                html:
                    '    <style>.swal2-content{padding:0px;}</style><div>\n' +
                    '      <input type="text" id="pin_your_address_input" style="width: 100%"/>\n' +
                    '      <div id="map" style="width: 100%; height: 400px;"></div>\n' +
                    '    </div>',
                icon: false,
                focusConfirm: false,
                showCloseButton: false,
                showCancelButton: true,

                onRender: (toast) => {
                    address = jQuery('#pin_your_address_input');
                    latitude_input = jQuery('#calc_shipping_latitude');
                    longitude_input = jQuery('#calc_shipping_longitude');

                    let latitude = latitude_input.val() || window.delivereeConfig.delivereeStoreAdress.latitude;
                    let longitude = longitude_input.val() || window.delivereeConfig.delivereeStoreAdress.longtitude;

                    jQuery('#map').locationpicker({
                        location: {
                            latitude: latitude,
                            longitude: longitude
                        },
                        addressFormat: 'places',
                        radius: 0,
                        enableAutocomplete: true,
                        inputBinding: {
                            latitudeInput: latitude_input,
                            longitudeInput: longitude_input,
                            radiusInput: null,
                            locationNameInput: address
                        },
                        onchanged: function (currentLocation, radius, isMarkerDropped) {
                            addressComponents = jQuery(this).locationpicker('map').location.addressComponents;
                        },
                    });


                },
                onAfterClose: () => {
                    if (typeof addressComponents != 'undefined') {
                        let city = (typeof addressComponents.city == "undefined") ? '' : addressComponents.city;
                        let postalCode = (typeof addressComponents.postalCode == "undefined") ? '' : addressComponents.postalCode;
                        let country = (typeof addressComponents.country == "undefined") ? '' : addressComponents.country;
                        let state = (typeof addressComponents.state == "undefined") ? '' : addressComponents.state;
                        let stateOrProvince = (typeof addressComponents.stateOrProvince == "undefined" || addressComponents.stateOrProvince == "") ? state : addressComponents.stateOrProvince

                        jQuery('#calc_shipping_state_input').val(stateOrProvince);
                        jQuery('#calc_shipping_city').val(city);
                        jQuery('#calc_shipping_postcode').val(postalCode);
                        jQuery('#calc_shipping_address_1').val(address.val());
                        jQuery("#calc_shipping_country").val(country).change();
                    }


                }
            });
        });
    },
    onChangePostCode: function () {
        var wto;

        jQuery(document).on('change', '.calc_shipping_get_address_google', function (e) {
            clearTimeout(wto);
            wto = setTimeout(function () {
                let country = jQuery('#calc_shipping_country').val();
                let postCode = jQuery('#calc_shipping_postcode').val();
                let state_input = jQuery('#calc_shipping_state_input');
                let city_input = jQuery('#calc_shipping_city');
                let form_cart = jQuery('div.cart_totals');
                shipping_calculator.codeAddress(postCode, country, state_input, city_input, form_cart);
            }, 500);
        });



        jQuery(document).on('change', '#billing_postcode', function (e) {
            clearTimeout(wto);
            let postCode = jQuery(this);
            wto = setTimeout(function () {
                let country = jQuery('#billing_country').val();
                let state_input = jQuery('#billing_state_input');
                let city_input = jQuery('#billing_city');
                let form_cart = jQuery('div#customer_details');
                shipping_calculator.codeAddress(postCode.val(), country, state_input, city_input, form_cart);
            }, 500);
        });

        jQuery(document).on('change', '#shipping_postcode', function (e) {
            clearTimeout(wto);
            let postCode = jQuery(this);
            wto = setTimeout(function () {
                let country = jQuery('#billing_country').val();
                let state_input = jQuery('#shipping_state_input');
                let city_input = jQuery('#shipping_city');
                let form_cart = jQuery('div#customer_details');
                shipping_calculator.codeAddress(postCode.val(), country, state_input, city_input, form_cart);
            }, 500);
        });


    },

    codeAddress: function (postCode, country, state_input, city_input, form, address_1 = '', address_2 = '') {
        
        if (window.delivereeConfig.delivereeGoogleApiKey == '')
            return false;


        let geocoder = new google.maps.Geocoder();
        state_input.prop('disabled', true);
        city_input.prop('disabled', true);
        shipping_calculator.block(form);
        let search = postCode;
        geocoder.geocode({
            address: search,
            componentRestrictions: {
                country: country,
            }
        },
            function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    let province_index = results[0].address_components.length - 2;
                    if (province_index > 1) {
                        let province_name = results[0].address_components[province_index];
                        state_input.val(province_name.long_name);
                    }
                    let city_index = results[0].address_components.length - 3;
                    if (city_index > 0) {
                        let city_name = results[0].address_components[city_index];
                        city_input.val(city_name.long_name);
                    }
                    jQuery('#calc_shipping_latitude').val(results[0].geometry.location.toJSON().lat);
                    jQuery('#calc_shipping_longitude').val(results[0].geometry.location.toJSON().lng);

                } else {
                    console.log("Geocode was not successful for the following reason: " + status);
                    state_input.val('');
                    city_input.val('');
                }
                state_input.prop('disabled', false);
                city_input.prop('disabled', false);
                shipping_calculator.unblock(form);
            });
    },
    checkUserLogin: function () {
        if (!window.delivereeConfig.delivereeUserLogin) {
            jQuery('#calc_shipping_state_input').val('');
            jQuery('#calc_shipping_city').val('');
            jQuery('#calc_shipping_postcode').val('');
            jQuery('#calc_shipping_address_1').val('');
            jQuery('#calc_shipping_latitude').val('');
            jQuery('#calc_shipping_longitude').val('');
        }
    }
}