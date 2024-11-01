jQuery(document).ready(function () {
  general_settings.init();
  general_settings.changeOriginDataType();
  general_settings.changeDelivereeApiMethod();
  general_settings.showPopupMap();
  general_settings.validateForm();
  general_settings.changeDelivereeGoogleApiKey();
});

// Attach your callback function to the `window` object
window.initMap = function () {
  general_settings.showPopupMap();
  jQuery("#deliveree_general_setting_origin_lat").val("");
  jQuery("#deliveree_general_setting_origin_lng").val("");
};

window.onload = (e) => {
  const existingScript = document.getElementById("deliveree-woo-google-js");
  if (existingScript) {
    jQuery("#deliveree-woo-google-js").remove();
    let k1 = jQuery("#deliveree_google_api_key").val() || "";
    let k2 = jQuery("#deliveree_google_api_key_picker").val() || "";
    if ("" !== k1 && "" !== k2 && k1 === k2) {
      var script = document.createElement("script");
      script.src =
        "https://maps.googleapis.com/maps/api/js?libraries=geometry,places&key=" +
        k1 +
        "&callback=initMap";
      script.async = true;
      script.id = "#deliveree-woo-google-js";
      document.head.appendChild(script);
      window.delivereeConfig.delivereeGoogleApiKey = k1;
    }
  }
};

var general_settings = {
  changeDelivereeGoogleApiKey: function (type) {
    jQuery(".deliveree-api-key-input").change(function () {
      jQuery("#deliveree-woo-google-js").remove();
      let k1 = jQuery("#deliveree_google_api_key").val() || "";
      let k2 = jQuery("#deliveree_google_api_key_picker").val() || "";
      if ("" !== k1 && "" !== k2 && k1 === k2) {
        var script = document.createElement("script");
        script.src =
          "https://maps.googleapis.com/maps/api/js?libraries=geometry,places&key=" +
          k1 +
          "&callback=initMap";
        script.async = true;
        script.id = "#deliveree-woo-google-js";
        document.head.appendChild(script);
        window.delivereeConfig.delivereeGoogleApiKey = k1;
      }
    });
  },
  showPopupMap: function () {
    jQuery("#deliveree_general_setting_origin_address").after(
      '<span class="deliveree-icon-maps">' +
        '<img width="25" class="deliveree-icon-maps-img" src="' +
        window.delivereeConfig.delivereeIcon.map_marked_alt +
        '"></span>'
    );

    if (window.delivereeConfig.delivereeGoogleApiKey != "") {
      jQuery("span.deliveree-icon-maps").click(function (e) {
        e.preventDefault();
        let address;
        let latitude;
        let longitude;
        let addressComponents;

        Swal.fire({
          title: "Search Address or Pin on Map",
          confirmButtonText: "Set",
          html:
            "    <style>.swal2-content{padding:0px;}</style><div>\n" +
            '      <textarea  id="address" style="width: 100%" ></textarea>\n' +
            '      <input type="text" id="latitude" class="hidden"/>\n' +
            '      <input type="text" id="longitude" class="hidden"/>\n' +
            '      <div id="map" style="width: 100%; height: 400px;"></div>\n' +
            "    </div>",
          icon: false,
          focusConfirm: false,
          showCloseButton: false,
          onRender: (toast) => {
            address = jQuery("#address");
            latitude = jQuery("#latitude");
            longitude = jQuery("#longitude");
            jQuery("#map").locationpicker({
              location: {
                latitude: window.delivereeConfig.delivereeStoreAdress.latitude,
                longitude:
                  window.delivereeConfig.delivereeStoreAdress.longtitude,
              },
              addressFormat: "places",
              radius: 0,
              enableAutocomplete: true,
              inputBinding: {
                latitudeInput: latitude,
                longitudeInput: longitude,
                radiusInput: null,
                locationNameInput: address,
              },
              onchanged: function (currentLocation, radius, isMarkerDropped) {
                addressComponents =
                  jQuery(this).locationpicker("map").location.addressComponents;
              },
            });
          },

          onAfterClose: () => {
            e.currentTarget.value = address.val();
            jQuery("#deliveree_general_setting_origin_lat").val(latitude.val());
            jQuery("#deliveree_general_setting_origin_lng").val(
              longitude.val()
            );
            jQuery("#deliveree_general_setting_origin_address").html(
              address.val()
            );
            if (typeof addressComponents != "undefined") {
              let country =
                typeof addressComponents.country == "undefined"
                  ? ""
                  : addressComponents.country;
              jQuery("#deliveree_general_setting_origin_country").val(country);
            }
          },
        });
      });
    }
  },
  showSettingByType: function (type) {
    if (type === "coordinate") {
      jQuery("#deliveree_general_setting_origin_address").closest("tr").hide();
      jQuery("#deliveree_general_setting_origin_lat").closest("tr").show();
      jQuery("#deliveree_general_setting_origin_lng").closest("tr").show();
    } else {
      jQuery("#deliveree_general_setting_origin_address").closest("tr").show();
      jQuery("#deliveree_general_setting_origin_lat").closest("tr").hide();
      jQuery("#deliveree_general_setting_origin_lng").closest("tr").hide();
    }
  },
  changeDelivereeApiMethod: function () {
    jQuery("input[type=radio][name=deliveree_api_method]").change(function () {
      let mode = jQuery(this).val();
      let deliveryAPIKeyInput = jQuery("#deliveree_api_key");
      let key_test_mode = "";
      switch (mode) {
        case "live_mode":
          key_test_mode = deliveryAPIKeyInput.val();
          deliveryAPIKeyInput.data("key-test-mode", key_test_mode);
          deliveryAPIKeyInput.val("");
          break;
        case "test_mode":
          key_test_mode = deliveryAPIKeyInput.data("key-test-mode");
          deliveryAPIKeyInput.val(key_test_mode);
          break;
        default:
          break;
      }
    });
  },
  getUrlParameter: function (sParam) {
    var sPageURL = window.location.search.substring(1),
      sURLVariables = sPageURL.split("&"),
      sParameterName,
      i;

    for (i = 0; i < sURLVariables.length; i++) {
      sParameterName = sURLVariables[i].split("=");

      if (sParameterName[0] === sParam) {
        return typeof sParameterName[1] === undefined
          ? true
          : decodeURIComponent(sParameterName[1]);
      }
    }
    return false;
  },
  validateForm: function () {
    var isTabDeliveree = general_settings.getUrlParameter("tab");
    if (
      isTabDeliveree !== false &&
      isTabDeliveree === "deliveree_custom_shipping_methods"
    ) {
      var mainForm = jQuery("#mainform");

      mainForm.submit(function (event) {
        let isErros = false;

        let deliveryAPIKeyInput = jQuery("#deliveree_api_key");
        if ("" === deliveryAPIKeyInput.val()) {
          deliveryAPIKeyInput.addClass("deliveree-error-box");
          jQuery("#delivereeAPIKey-error").remove();
          deliveryAPIKeyInput.after(
            '<p id="delivereeAPIKey-error" class="deliveree-error-msg">Please enter the ' +
              window.delivereeConfig.deliveree_name +
              " API key.</p>"
          );
          isErros = true;
        }

        let type = jQuery(
          "input[type=radio][name=deliveree_general_setting_origin_data_type]:checked"
        ).val();
        let delivereeGenSetAddress = jQuery(
          "#deliveree_general_setting_origin_address"
        );

        if ("" === delivereeGenSetAddress.val()) {
          delivereeGenSetAddress.addClass("deliveree-error-box");
          jQuery("#delivereeGenSetAddress-error").remove();
          delivereeGenSetAddress.after(
            '<p id="delivereeGenSetAddress-error" class="deliveree-error-msg">Please enter the store location address.</p>'
          );
          isErros = true;
        }
        delivereeGenSetAddress.removeClass("deliveree-error-box");

        let delivereeGneralSettingOrigin_Lat = jQuery(
          "#deliveree_general_setting_origin_lat"
        );
        if (
          "" === delivereeGneralSettingOrigin_Lat.val() &&
          type === "coordinate"
        ) {
          delivereeGneralSettingOrigin_Lat.addClass("deliveree-error-box");
          jQuery("#delivereeGenSetAddress-error").remove();
          delivereeGneralSettingOrigin_Lat.after(
            '<p id="delivereeGenSetAddress-error" class="deliveree-error-msg">Please enter the store location latitude.</p>'
          );
          isErros = true;
        }
        delivereeGneralSettingOrigin_Lat.removeClass("deliveree-error-box");

        let delivereeGneralSettingOrigin_Lng = jQuery(
          "#deliveree_general_setting_origin_lng"
        );
        if (
          "" === delivereeGneralSettingOrigin_Lng.val() &&
          type === "coordinate"
        ) {
          delivereeGneralSettingOrigin_Lng.addClass("deliveree-error-box");
          jQuery("#delivereeGenSetAddress-error").remove();
          delivereeGneralSettingOrigin_Lng.after(
            '<p id="delivereeGenSetAddress-error" class="deliveree-error-msg">Please enter the store location latitude.</p>'
          );
          isErros = true;
        }
        delivereeGneralSettingOrigin_Lng.removeClass("deliveree-error-box");

        if (isErros) {
          event.preventDefault();
        }
      });
    }
  },
  changeOriginDataType: function () {
    jQuery(
      "input[type=radio][name=deliveree_general_setting_origin_data_type]"
    ).change(function () {
      let type = jQuery(this).val();
      general_settings.showSettingByType(type);
    });
  },
  init: function () {
    let type = jQuery(
      "input[type=radio][name=deliveree_general_setting_origin_data_type]:checked"
    ).val();
    general_settings.showSettingByType(type);
  },
};
