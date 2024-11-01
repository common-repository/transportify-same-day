var form_total_fees = 0;
var save_value_pickup_time_datetimepicker;
var save_value_pickup_time_quick_choice_id = "";

jQuery(document).ready(function () {
	order_list.addToGroupBooking();
	order_list.removeFromGroupBooking();
	order_list.nextGroupBooking();
	order_list.showTabMethod();
	order_list.selectDeliveryType();
	order_list.onChangeSelectVecicle();
	order_list.addExtraServices();
	order_list.addExtraServicesGoodsInsurance();
	order_list.deleteBoookingOrders();
	order_list.trashBoookingOrders();
	order_list.restoreBoookingOrders();
	order_list.onQuickChoices();
	order_list.init();
	order_list.sortGroupBooking();
	order_list.coppyAddress();
	order_list.bulkAction();
	order_list.clearBookingGroup();
	order_list.showBulkOption();
	order_list.deselectAll();
});

var order_list = {
	showBulkOption: function () {
		jQuery("body").on("click", ".bulk-button", function (event) {
			event.stopPropagation();
			let checked_length = jQuery(
				"input.checkbox-confirm-booking:checked"
			).length;
			jQuery(".bulk-option").toggle();
			jQuery(".bulk-confirm-selected-js span").html(checked_length);
		});

		jQuery(document.body).click(function () {
			jQuery(".bulk-option").hide();
		});
	},
	addToGroupBooking: function () {
		jQuery("body").on("click", ".add-to-group-booking-js", function (event) {
			let item = jQuery(this).data("data");
			order_list.editBookingIdsOnUrl([item.id]);
		});
	},
	removeFromGroupBooking: function () {
		jQuery("body").on("click", ".remove-from-list-js", function (event) {
			let id = jQuery(this).data("id");
			order_list.editBookingIdsOnUrl([id.toString()], false);
		});
	},
	clearBookingGroup: function () {
		jQuery("body").on("click", ".clear-booking-group-js", function (event) {
			let url_search_date = new URL(window.location.href);
			url_search_date.searchParams.delete("group_booking_ids");
			window.location.href = url_search_date.toString();
		});
	},
	bulkAction: function () {
		jQuery("body").on("click", ".bulk-confirm-selected-js", function (event) {
			let checkbox_confirm = jQuery("input.checkbox-confirm-booking:checked");
			let booking_ids = [];
			for (let index = 0; index < checkbox_confirm.length; index++) {
				const element = checkbox_confirm[index];
				let booking_id = jQuery(element).val();
				booking_ids.push(booking_id);
			}

			order_list.editBookingIdsOnUrl(booking_ids);
		});
	},
	deselectAll: function () {
		jQuery("body").on("click", ".bulk-deselect-js", function (event) {
			let checkbox_confirm = jQuery(".column-cb input:checked");
			if (checkbox_confirm.length) {
				checkbox_confirm.click();
			} else {
				jQuery("input.checkbox-confirm-booking:checked").removeAttr("checked");
			}
		});
	},

	init: function () {
		// ------ Show darkTooltip
		jQuery(".woocommerce-help-tip").darkTooltip();

		// ------- Show total_customer_pay
		let orderSelected = order_list.getOrderSelected();
		let { total_customer_pay, total_weight, currency } =
			order_list.calculateSelectVecicle(orderSelected.items);
		let string_total_customer_pay = order_list.numberWithCommas(
			total_customer_pay,
			currency
		);

		jQuery("#footer_group_total_weight_js").html(total_weight + " kg");
		jQuery("#footer_group_total_customer_pay_js").html(
			string_total_customer_pay
		);

		// ---------- Show dropdown
		jQuery("body").on("click", ".button-dropdown", (e) => {
			e.stopPropagation();
			jQuery(e.currentTarget).toggleClass("show");
			jQuery(".button-dropdown").not(e.currentTarget).removeClass("show");
		});

		jQuery(document).on("click", function () {
			jQuery(".button-dropdown").removeClass("show");
		});

		// ----- Set datetimepicker
		jQuery("#filter_datetimepicker").datetimepicker({
			format: "d-M-Y",
			timepicker: false,
			onChangeDateTime: function (dp, $input) {
				if ($input.val() != "") {
					jQuery(".show_pickup_hours").removeClass("disabled");
				} else {
					jQuery(".show_pickup_hours").addClass("disabled");
					jQuery("#pickup_hours_start").val("");
					jQuery("#pickup_hours_end").val("");
				}
			},
		});
	},
	coppyAddress: function () {
		jQuery("body").on("click", ".warraper-copy-icon-js", function (event) {
			let shipping_address = jQuery(this).data("shipping-address");
			navigator.clipboard.writeText(shipping_address);
			Swal.fire({
				position: "top-end",
				icon: "success",
				title: "Copied",
				width: 190,
				showConfirmButton: false,
				timer: 1000,
			});
		});
	},
	sortGroupBooking: function () {
		jQuery("body").on(
			"click",
			".arrow-sort-group-booking-js",
			function (event) {
				let url_search_date = new URL(window.location.href);
				let sort = parseInt(jQuery(this).data("sort"));
				let booking_id = jQuery(this).data("booking-id").toString();
				let ids_str = url_search_date.searchParams.get("group_booking_ids");

				let ids_arr = [];
				if (ids_str) {
					ids_arr = ids_str.split("-");
					let current_index = ids_arr.indexOf(booking_id);

					let next_index = current_index + sort;
					if (next_index < 0) {
						next_index = ids_arr.length - 1;
					}

					if (next_index >= ids_arr.length) {
						next_index = 0;
					}

					var b = ids_arr[current_index];
					ids_arr[current_index] = ids_arr[next_index];
					ids_arr[next_index] = b;
				}

				ids_str = ids_arr.join("-");

				url_search_date.searchParams.set("group_booking_ids", ids_str);
				window.location.href = url_search_date.toString();
			}
		);
	},
	updateSortGroupBooking: function () {
		jQuery("body").on("click", ".update-sort-booking-group-js", function () {
			var elmArray = document.querySelectorAll(".warraper-arrow-sort");
			var gb_ids_array = [];
			elmArray.forEach(function (item) {
				var gb_id = item.getAttribute("data-booking-id");
				gb_ids_array.push(gb_id);
			});

			let url = new URL(window.location.href);
			let gb_ids_str = "";
			if (gb_ids_array) gb_ids_str = gb_ids_array.join("-");
			url.searchParams.set("group_booking_ids", gb_ids_str);
			window.location.href = url.toString();
		});
	},

	selectDeliveryType: function () {
		document.multiselect("#select_delivery_type");
		let selected = jQuery("#select_delivery_type").data("selected");
		jQuery("#select_delivery_type_input").attr("placeholder", "Delivery Type");
		if (selected != null) {
			selected.forEach((element) => {
				document.multiselect("#select_delivery_type").select(element);
			});
		}
	},
	showTabMethod: function () {
		jQuery("body").on("click", ".show_tab_method", function (event) {
			jQuery(".show_tab_method").removeClass("active");
			jQuery(this).addClass("active");
			let tab = jQuery(this).data("tab");
			jQuery(".tab_method").removeClass("active");
			jQuery("." + tab).addClass("active");
		});
	},
	numberWithCommas: function (x, currency = "Rp", positive = true) {
		switch (currency) {
			case "u20b1":
				currency = "₱";
				break;
			case "THB":
				currency = "฿";
				break;
			default:
				break;
		}

		let number;
		let pre = x < 0 && !positive ? "-" : "";

		if (typeof x == "undefined") number = "";
		else {
			pre += " " + currency + " ";
			number = Math.abs(x)
				.toString()
				.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
			number = pre + number;
		}

		return number;
	},

	editBookingIdsOnUrl: function (booking_ids = [], addAction = true) {
		let url_search_date = new URL(window.location.href);
		let ids_str = url_search_date.searchParams.get("group_booking_ids");
		let ids_arr = [];
		if (ids_str) {
			ids_arr = ids_str.split("-");
			//Remove old booking_ids
			for (
				let index_booking_id = 0;
				index_booking_id < booking_ids.length;
				index_booking_id++
			) {
				let booking_id = booking_ids[index_booking_id];
				let index_ids_arr = ids_arr.indexOf(booking_id);
				if (index_ids_arr >= 0) {
					ids_arr.splice(index_ids_arr, 1);
				}
			}
		}

		if (addAction) {
			//add new booking_ids
			for (
				let index_booking_id = 0;
				index_booking_id < booking_ids.length;
				index_booking_id++
			) {
				let booking_id = booking_ids[index_booking_id];
				ids_arr.push(booking_id);
			}
		}

		ids_str = ids_arr.join("-");
		url_search_date.searchParams.set("group_booking_ids", ids_str);
		window.location.href = url_search_date.toString();
	},

	nextGroupBooking: function () {
		jQuery("body").on("click", ".next-group-booking-js", function (event) {
			order_list.ajaxGetQuotesById();
		});
	},

	getOrderSelected: function () {
		let url_search_date = new URL(window.location.href);
		let booking_ids_str = url_search_date.searchParams.get("group_booking_ids");
		let booking_ids_arr = [];
		let items = [];

		if (booking_ids_str) {
			booking_ids_arr = booking_ids_str.split("-");
		}

		for (let index = 0; index < booking_ids_arr.length; index++) {
			let id = booking_ids_arr[index];
			let get_order_data = ".get_order_data_" + id;
			let order_data = jQuery(get_order_data).first();
			let item = order_data.data("data");
			if (typeof item != "undefined") {
				items.push(item);
			}
		}

		return {
			items,
			booking_ids_arr,
		};
	},

	ajaxGetQuotesById: function () {
		jQuery.LoadingOverlay("show");
		var time_type = jQuery(".select_time_type").val();
		var pickup_time = jQuery(".select_pickup_time").val();
		let now = moment(new Date());
		let orderSelected = order_list.getOrderSelected();

		form_total_fees = 0;
		save_value_pickup_time_quick_choice_id = "";
		save_value_pickup_time_datetimepicker = now.format("DD-MMM-YYYY HH:mm");

		var data = {
			time_type: time_type,
			pickup_time: pickup_time,
			action: "getQuoteByBoookingOrders",
			booking_ids_arr: orderSelected.booking_ids_arr,
		};

		jQuery.ajax({
			url: jQuery("#urlForm").data("admin-url-ajax"),
			type: "POST",
			dataType: "json",
			async: true,
			data: data,
			success: function (quotes_response, textStatus, jqXHR) {
				if (quotes_response.length) {
					order_list.showConfirmBooking(quotes_response, orderSelected.items);
				} else {
					mscAlert({
						title: "Not Found Vehicle",
						// subtitle: '.',  // default: ''
						okText: "Close", // default: OK
					});
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {
				console.log("AJAX call failed.");
				console.log(textStatus + ": " + errorThrown);
			},
			complete: function () {
				jQuery.LoadingOverlay("hide");
			},
		});
	},
	getQuickChoicesOption: function (quick_choices) {
		let quick_choices_option_html = "";
		let active_pickup_time_quick_choices = "";
		let active_pickup_time_calendar = "active";

		if (quick_choices.length) {
			for (
				let index_quick_choice = 0;
				index_quick_choice < quick_choices.length;
				index_quick_choice++
			) {
				const element_quick_choice = quick_choices[index_quick_choice];
				let schedule_time = element_quick_choice.schedule_time;
				let hours = element_quick_choice.schedule_time / 60;
				let unit = "minute";
				if (hours >= 1) {
					schedule_time = hours;
					unit = "hour";
				}
				let plural = schedule_time > 1 ? "s" : "";

				quick_choices_option_html +=
					'<option    value="' + element_quick_choice.id + '">';
				quick_choices_option_html +=
					"Pickup in " + schedule_time + " " + unit + plural;
				quick_choices_option_html += "</option>";
			}

			quick_choices_option_html += '<option value="0">Schedule Pickup</option>';
			active_pickup_time_quick_choices = "active";
			active_pickup_time_calendar = "";
		}

		return {
			quick_choices_option_html: quick_choices_option_html,
			active_pickup_time_quick_choices: active_pickup_time_quick_choices,
			active_pickup_time_calendar: active_pickup_time_calendar,
		};
	},

	showConfirmBooking: function (
		quotes_response,
		items,
		selected_vehicle_type = {}
	) {
		order_list.formConfirmBooking(
			quotes_response,
			items,
			selected_vehicle_type
		);

		if (
			typeof save_value_pickup_time_quick_choice_id != "undefined" &&
			save_value_pickup_time_quick_choice_id != ""
		) {
			jQuery("#select_pickup_time_quick_choices")
				.val(save_value_pickup_time_quick_choice_id)
				.change();
		}
		order_list.reloadLibaryOnForm();
	},

	reloadLibaryOnForm: function () {
		let minTime = moment(new Date());
		let curent_pickup_time = moment(
			save_value_pickup_time_datetimepicker,
			"DD-MMM-YYYY HH:mm",
			true
		);
		let slected_curent_pickup_time =
			curent_pickup_time > minTime ? curent_pickup_time : minTime;

		jQuery("#pickup_time_datetimepicker").datetimepicker({
			format: "d-M-Y H:i",
			timepicker: true,
			minDate: new Date(),
			value: slected_curent_pickup_time.format("DD-MMM-YYYY HH:mm"),
			minTime: new Date(),
			step: 30,
			onSelectDate: function (ct, $input) {
				let selected_date = moment(ct).format("DD");
				let now = moment(new Date()).format("DD");
				minTime = now == selected_date ? new Date() : "00:00";
				this.setOptions({
					minTime: minTime,
					value: $input.val(),
				});
			},
		});

		jQuery(".woocommerce-help-tip").darkTooltip();
	},

	formConfirmBooking: function (
		quotes_response,
		items,
		selected_vehicle_type = {}
	) {
		let titlePopup = "Select Vehicle ";
		let options = "";
		let quick_choices = [];
		let deliveree_services_extra_services_on = jQuery(
			"#deliveree_services_extra_services_on"
		);
		let is_extra_services_on = deliveree_services_extra_services_on.length;
		let confirmButtonText = is_extra_services_on ? "Next" : "Confirm Booking ";
		let default_quotes_response = quotes_response.find(
			(o) => o.is_default_vehicle_id
		);
		default_quotes_response =
			typeof default_quotes_response == "undefined"
				? quotes_response[0]
				: default_quotes_response;
		let curent_vehicle_type =
			Object.keys(selected_vehicle_type).length === 0
				? default_quotes_response.vehicle_type_response
				: selected_vehicle_type;
		form_total_fees = curent_vehicle_type.total_fees;

		let { order_ids } = order_list.calculateSelectVecicle(items);

		for (let index = 0; index < quotes_response.length; index++) {
			const element = quotes_response[index];
			delete element.vehicle_type_id;
			let vehicle_type_response = element.vehicle_type_response;
			let checked = "";
			let table_detail_select_vecicle = "";
			let {
				pay,
				label_pay,
				class_pay,
				cargo_utilized,
				total_customer_pay,
				total_weight,
			} = order_list.calculateSelectVecicle(items, vehicle_type_response);

			if (
				curent_vehicle_type.vehicle_type_id ==
				vehicle_type_response.vehicle_type_id
			) {
				checked = "checked";
				quick_choices = vehicle_type_response.vehicle_type.quick_choices;
				let quick_choices_option =
					order_list.getQuickChoicesOption(quick_choices);
				table_detail_select_vecicle = order_list.getTableDetailSelectVecicle(
					quick_choices_option,
					label_pay,
					class_pay,
					pay,
					curent_vehicle_type,
					total_customer_pay,
					cargo_utilized,
					total_weight,
					order_ids
				);
			}

			options +=
				'<tr class="row_confirm_select_vecicle  ' + checked + ' valign="top">';
			options += '<th  scope="row"  titledesc">';
			options +=
				"<input " +
				checked +
				'  type="radio" name="confirm_select_vecicle" class="confirm_select_vecicle" value="' +
				vehicle_type_response.vehicle_type_id +
				'" data-vehicle-type-response="' +
				JSON.stringify(vehicle_type_response).replaceAll('"', "'") +
				'" data-item="' +
				JSON.stringify(element).replaceAll('"', "'") +
				'" >';
			options += '<span class="wrapper_name_price" >';
			options += vehicle_type_response.vehicle_type_name;
			options +=
				'<span class="price-on-top"> ' +
				order_list.numberWithCommas(
					vehicle_type_response.total_fees,
					vehicle_type_response.currency
				) +
				"</span>";
			options += "</span>";
			options += "</th>";
			options += '<td class="forminp forminp-deliveree-readonly">';

			if (element.is_default_vehicle_id) {
				options += '<span class="default-on-top">DEFAULT</span>';
			}

			options +=
				'<span class="cargo-utilised"> Cargo Utilised <strong><span class="cargo-utilised-detail-vehicle-form-' +
				vehicle_type_response.vehicle_type_id +
				'">' +
				cargo_utilized +
				" %</span></strong></span>";
			options += "</td>";
			options += "</tr>";
			options += '<tr class="tr-wrapper-detail-vehicle" valign="top">';
			options +=
				'<td class="wrapper-detail-vehicle wrapper-detail-vehicle-form-' +
				vehicle_type_response.vehicle_type_id +
				'" colspan="2">';
			options += table_detail_select_vecicle;
			options += "</td>";
			options += "</tr>";
		}

		Swal.fire({
			title: titlePopup,
			html:
				"<div>" +
				'<table id="select-vehicle-form" class="form-table">' +
				"<tbody>" +
				options +
				"</tbody>" +
				"</table>" +
				"</div>",
			focusConfirm: false,
			confirmButtonText: confirmButtonText,
			cancelButtonText: "Back",
			showCancelButton: true,
			reverseButtons: true,
			customClass: {
				container: "select-vehicle",
				popup: "popup-select-vehicle",
				header: "header-select-vehicle",
				title: "title-select-vehicle",
				closeButton: "...",
				icon: "...",
				image: "...",
				content: "content-select-vehicle",
				htmlContainer: "...",
				input: "...",
				inputLabel: "...",
				validationMessage: "...",
				actions: "actions-select-vehicle",
				confirmButton: "confirmButton-select-vehicle",
				denyButton: "denyButton-select-vehicle",
				cancelButton: "cancelButton-select-vehicle",
				loader: "...",
				footer: "....",
			},
			preConfirm: () => {},
		}).then((result) => {
			if (result.isConfirmed) {
				save_value_pickup_time_quick_choice_id = jQuery(
					"#select_pickup_time_quick_choices"
				)
					.find(":selected")
					.val();
				let selected_vehicle_type = jQuery(
					".confirm_select_vecicle:checked"
				).data("vehicle-type-response");
				save_value_pickup_time_datetimepicker = jQuery(
					"#pickup_time_datetimepicker"
				).val();

				selected_vehicle_type = selected_vehicle_type.replaceAll("'", '"');
				selected_vehicle_type = JSON.parse(selected_vehicle_type);

				if (is_extra_services_on) {
					order_list.getExtraServices(
						quotes_response,
						selected_vehicle_type,
						order_ids
					);
				} else {
					let now = moment(new Date());
					let curent_pickup_time = moment(
						save_value_pickup_time_datetimepicker,
						"DD-MMM-YYYY HH:mm"
					);
					let time_type = curent_pickup_time > now ? "schedule" : "now";
					let pickup_time =
						curent_pickup_time > now ? curent_pickup_time.toISOString() : "";
					let optimize_route = jQuery("#optimize_route").val();

					let data = {
						order_ids: order_ids,
						vehicle_type_id: selected_vehicle_type.vehicle_type_id,
						time_type: time_type,
						pickup_time: pickup_time,
						vehicle_type: selected_vehicle_type,
						quick_choice_id: save_value_pickup_time_quick_choice_id,
						optimize_route: optimize_route,
					};

					order_list.dontShowPopupCreateBooking(data);
				}
			}
		});
	},
	getTableDetailSelectVecicle: function (
		quick_choices_option,
		label_pay,
		class_pay,
		pay,
		curent_vehicle_type,
		total_customer_pay,
		cargo_utilized,
		total_weight,
		order_ids
	) {
		return (
			'<table id="detail-vehicle-form" class="form-table">' +
			"<tbody>" +
			'<tr valign="top">' +
			'<th scope="row" class="titledesc">' +
			'<label for="">Pickup Time</label>' +
			"</th>" +
			'<td class="forminp forminp-deliveree-readonly">' +
			'<fieldset class="pickup-time-quick-choices ' +
			quick_choices_option.active_pickup_time_quick_choices +
			'"> <select id="select_pickup_time_quick_choices"  >' +
			quick_choices_option.quick_choices_option_html +
			"</select> </fieldset>" +
			'<fieldset class="pickup-time-calendar ' +
			quick_choices_option.active_pickup_time_calendar +
			'">   <span class="icon-block"><i class="icon-calendar" ></i></span> <input class="datetimepicker"  id="pickup_time_datetimepicker" name="d" type="text" > </fieldset>' +
			"</td>" +
			"</tr>" +
			'<tr valign="top">' +
			'<th scope="row" class="titledesc">' +
			'<label for="">Distance</label>' +
			"</th>" +
			'<td class="forminp forminp-deliveree-readonly">' +
			'<fieldset id="form_total_distance"> ' +
			order_ids.length +
			" stops &#183;" +
			order_list.numberWithCommas(curent_vehicle_type.total_distance, "") +
			" km</fieldset>" +
			"</td>" +
			"</tr>" +
			'<tr valign="top">' +
			'<th scope="row" class="titledesc">' +
			'<label for="">Total Orders</label>' +
			"</th>" +
			'<td class="forminp forminp-deliveree-readonly">' +
			"<fieldset> " +
			order_ids.length +
			"</fieldset>" +
			"</td>" +
			"</tr>" +
			'<tr valign="top">' +
			'<th scope="row" class="titledesc">' +
			'<label for="">Total weights</label>' +
			"</th>" +
			'<td class="forminp forminp-deliveree-readonly">' +
			"<fieldset> " +
			total_weight +
			" kg</fieldset>" +
			"</td>" +
			"</tr>" +
			'<tr valign="top">' +
			'<th scope="row" class="titledesc">' +
			'<label for="">Customer(s) Pays <span class="woocommerce-help-tip" data-tooltip="The price your customer must pay for the ' +
			window.delivereeConfig.deliveree_name +
			' booking."></span></label>' +
			"</th>" +
			'<td class="forminp forminp-deliveree-readonly">' +
			'<fieldset id="form_paid_shipping"> ' +
			order_list.numberWithCommas(
				total_customer_pay,
				curent_vehicle_type.currency
			) +
			"</fieldset>" +
			"</td>" +
			"</tr>" +
			'<tr valign="top">' +
			'<th scope="row" class="titledesc">' +
			'<label for="">' +
			window.delivereeConfig.deliveree_name +
			' Receives <span class="woocommerce-help-tip" data-tooltip="Total price you pay to ' +
			window.delivereeConfig.deliveree_name +
			' for this booking."></span></label>' +
			"</th>" +
			'<td  class="forminp forminp-deliveree-readonly">' +
			'<fieldset> <span  id="form_total_fees"> ' +
			order_list.numberWithCommas(
				curent_vehicle_type.total_fees,
				curent_vehicle_type.currency
			) +
			"</span> </fieldset>" +
			"</td>" +
			"</tr>" +
			'<tr valign="top">' +
			'<td colspan="2" style="padding: 0 1.6em;">' +
			'<div style=" height: 1px; border-bottom: 1px solid #dddddd; "></div>' +
			"</td>" +
			"</tr>" +
			'<tr valign="top">' +
			'<th scope="row" class="titledesc">' +
			'<label for=""><span id="label_form_you_receive">' +
			label_pay +
			'</span> <span class="woocommerce-help-tip" data-tooltip="If value is positive, you receive this premium. If value is negative, you pay this discount."></span></label>' +
			"</th>" +
			'<td class="forminp forminp-deliveree-readonly">' +
			'<fieldset class="' +
			class_pay +
			'" id="form_you_receive">  ' +
			order_list.numberWithCommas(pay, curent_vehicle_type.currency) +
			"</fieldset>" +
			"</td>" +
			"</tr>" +
			"</tbody>" +
			"</table>"
		);
	},
	calculateSelectVecicle: function (items, curent_vehicle_type = {}) {
		let total_customer_pay =
			(total_weight =
			cargo_utilized =
			best_fit_id =
			pay =
				0);
		let order_ids = [];
		let label_pay = (class_pay = "");
		let currency = "";
		for (let index = 0; index < items.length; index++) {
			const element_item = items[index];
			total_customer_pay += parseFloat(element_item.paid_shipping);
			total_weight += parseFloat(element_item.weight);
			order_ids.push(parseInt(element_item.order_id));
		}

		if (items.length) {
			let vehicle_type_first_order = JSON.parse(items[0].vehicle_type);
			currency = vehicle_type_first_order.currency;
		}

		if (Object.keys(curent_vehicle_type).length) {
			best_fit_id = curent_vehicle_type.vehicle_type_id;
			currency = curent_vehicle_type.currency;
			pay = total_customer_pay - curent_vehicle_type.total_fees;
			label_pay = pay >= 0 ? "You Receive" : "You Pay";
			class_pay = pay >= 0 ? "" : ""; //you_pay
			cargo_utilized =
				(total_weight / curent_vehicle_type.vehicle_type.cargo_weight) * 100;
			cargo_utilized = cargo_utilized.toFixed(2);
		}

		return {
			best_fit_id,
			pay,
			label_pay,
			class_pay,
			cargo_utilized,
			total_customer_pay,
			order_ids,
			total_weight,
			currency,
		};
	},

	onChangeSelectVecicle: function () {
		jQuery("body").on("change", ".confirm_select_vecicle", function (event) {
			jQuery(".row_confirm_select_vecicle").removeClass("checked");
			let row_confirm_select_vecicle = jQuery(this).parents(
				".row_confirm_select_vecicle"
			);
			row_confirm_select_vecicle.addClass("checked");

			let orderSelected = order_list.getOrderSelected();
			let vehicle_type_response = jQuery(this).data("vehicle-type-response");
			vehicle_type_response = vehicle_type_response.replaceAll("'", '"');
			vehicle_type_response = JSON.parse(vehicle_type_response);

			let curent_vehicle_type = vehicle_type_response;

			let { pay, label_pay, cargo_utilized, order_ids } =
				order_list.calculateSelectVecicle(
					orderSelected.items,
					curent_vehicle_type
				);

			order_list.setQuickChoices(
				curent_vehicle_type.vehicle_type.quick_choices
			);

			let total_fees = parseInt(curent_vehicle_type.total_fees);
			let total_distance = order_list.numberWithCommas(
				curent_vehicle_type.total_distance,
				""
			);
			jQuery("#form_total_distance").html(
				order_ids.length + " stops &#183;" + total_distance + " km"
			);
			jQuery(
				".cargo-utilised-detail-vehicle-form-" +
					curent_vehicle_type.vehicle_type_id
			).html(cargo_utilized + " %");
			jQuery("#form_total_fees").html(
				order_list.numberWithCommas(total_fees, curent_vehicle_type.currency)
			);
			form_total_fees = total_fees;

			jQuery("#label_form_you_receive").html(label_pay);
			jQuery("#form_you_receive").html(
				order_list.numberWithCommas(pay, curent_vehicle_type.currency)
			);

			let detail_vehicle_form = jQuery("#detail-vehicle-form").clone();
			jQuery(".wrapper-detail-vehicle").html("");
			jQuery(
				".wrapper-detail-vehicle-form-" + curent_vehicle_type.vehicle_type_id
			).html(detail_vehicle_form);
			order_list.reloadLibaryOnForm();
		});
	},
	setQuickChoices: function (quick_choices) {
		let quick_choices_option = order_list.getQuickChoicesOption(quick_choices);
		let now = moment(new Date());
		jQuery(".pickup-time-calendar").removeClass("active");
		jQuery(".pickup-time-quick-choices").removeClass("active");
		jQuery("#select_pickup_time_quick_choices").html(
			quick_choices_option.quick_choices_option_html
		);
		jQuery(".pickup-time-quick-choices").addClass(
			quick_choices_option.active_pickup_time_quick_choices
		);
		jQuery(".pickup-time-calendar").addClass(
			quick_choices_option.active_pickup_time_calendar
		);
		jQuery("#pickup_time_datetimepicker").val(now.format("DD-MMM-YYYY HH:mm"));
	},
	onQuickChoices: function () {
		jQuery("body").on(
			"change",
			"#select_pickup_time_quick_choices",
			function (event) {
				let pickup_time_quick_choices = jQuery(this).find(":selected").val();
				if (pickup_time_quick_choices == "0") {
					jQuery(".pickup-time-calendar").addClass("active");
				} else {
					let now = moment(new Date());
					jQuery(".pickup-time-calendar").removeClass("active");
					jQuery("#pickup_time_datetimepicker").val(
						now.format("DD-MMM-YYYY HH:mm")
					);
				}
			}
		);
	},
	getExtraServices: function (
		quotes_response = [],
		selected_vehicle_type,
		order_ids = []
	) {
		// let selected_vehicle_type = JSON.parse(item.vehicle_type);
		let vehicle_type_id = selected_vehicle_type.vehicle_type_id;
		const $delivereeApiKey = window.delivereeConfig.delivereeApiKey.api_key;
		var urlVehicleType =
			window.delivereeConfig.delivereeApiKey.api_url +
			"/vehicle_types/" +
			vehicle_type_id +
			"/extra_services?time_type=now";
		jQuery.ajax({
			headers: {
				"Content-Type": "application/json",
				Authorization: $delivereeApiKey,
				"Accept-Language": "en",
			},
			method: "GET",
			url: urlVehicleType.replace(/(https?:\/\/)|(\/)+/g, "$1$2"),
			success: function (response) {
				let extraServices = response.data;
				order_list.showPopupExtraServices(
					extraServices,
					quotes_response,
					selected_vehicle_type,
					order_ids
				);
			},
			error: (jqXHR, textStatus, errorThrown) => {
				console.log("The following error occured: " + textStatus, errorThrown);
			},
		});
	},

	showPopupExtraServices: function (
		extraServices,
		quotes_response,
		selected_vehicle_type,
		order_ids
	) {
		let tbody = "";
		// let selected_vehicle_type = JSON.parse(item.vehicle_type);
		let extra_services_goods_insurance = {
			extra_requirement_id: 0,
			selected_amount: 1,
			extra_requirement_pricing_id: 0,
		};

		let insurance_policy_link = jQuery("#insurance_policy_link").data(
			"insurance-policy-link"
		);

		for (let index = 0; index < extraServices.length; index++) {
			const element = extraServices[index];
			let unit_price =
				element.unit_price == 0
					? "-"
					: order_list.numberWithCommas(
							element.unit_price,
							selected_vehicle_type.currency
					  );

			if (element.pricing_method != "by_options") {
				tbody += '<tr valign="top">';
				tbody += '<th scope="row" class="titledesc">';
				tbody += "<label >" + element.name + "</label>";
				tbody += "<br/>";
				tbody += '<div class="unit-price" >' + unit_price + "</div>";
				tbody += "</th>";
				tbody += '<td class="forminp forminp-deliveree-readonly">';
				tbody +=
					'<fieldset> <input class="input-extra-services" value="' +
					element.id +
					'" data-unit-price="' +
					element.unit_price +
					'" type="checkbox" /> </fieldset>';
				tbody += "</td>";
				tbody += "</tr>";
			} else {
				tbody += '<tr class="goods-insurance" valign="top">';
				tbody += '<th scope="row" class="titledesc">';
				tbody += "<label >" + element.name + "</label>";
				tbody += "</th>";
				tbody += '<td class="forminp forminp-deliveree-readonly">';
				tbody += "</td>";
				tbody += "</tr>";
				tbody += '<tr class="goods-insurance small-title" valign="top">';
				tbody += '<th scope="row" class="titledesc">';
				tbody += "<label > Coverage</label>";
				tbody += "</th>";
				tbody += '<td class="forminp forminp-deliveree-readonly">Price</td>';
				tbody += "</tr>";

				extra_services_goods_insurance.extra_requirement_id = element.id;

				for (
					let index_pricings = 0;
					index_pricings < element.pricings.length;
					index_pricings++
				) {
					const pricings = element.pricings[index_pricings];
					let checked = "";
					if (pricings.fees === 0) {
						checked = "row-small-table-checked";
						extra_services_goods_insurance.extra_requirement_pricing_id =
							pricings.id;
					}

					tbody +=
						'<tr data-fees="' +
						pricings.fees +
						'" data-id="' +
						pricings.id +
						'" class="goods-insurance row-small-table ' +
						checked +
						'" valign="top">';
					tbody += '<th scope="row" class="titledesc">';
					tbody += "<label >" + pricings.display_level_price + "</label>";
					tbody += "</th>";
					tbody +=
						'<td class="forminp forminp-deliveree-readonly">' +
						pricings.display_fees +
						"</td>";
					tbody += "</tr>";
				}

				tbody += '<tr class="goods-insurance" >';
				tbody +=
					'<td colspan="2" style="text-align:left;    padding: 7px 0;" ><input checked="checked" id="insurance-policy" type="checkbox" > <label for="insurance-policy"> I agree with the <a target="_blank" href="' +
					insurance_policy_link +
					'">Insurance Policy<a/></label></td>';
				tbody += "</tr>";
			}
		}

		Swal.fire(
			{
				title: "Select Extra Service #",
				html:
					'<div style="  padding: 0px 1.6em; ">' +
					'<table id="select-extra-service" class="form-table">' +
					"<tbody>" +
					tbody +
					"</tbody>" +
					"</table>" +
					'<table id="footer-extra-service" class="form-table">' +
					"<tbody>" +
					'<tr valign="top">' +
					'<th scope="row" class="titledesc">' +
					'<label > Extra Services <span class="woocommerce-help-tip" data-tooltip="The total price for extra services on this booking."></span></label>' +
					"</th>" +
					'<td class="forminp forminp-deliveree-readonly">' +
					window.delivereeConfig.deliveree_name +
					' Receives <span class="woocommerce-help-tip" data-tooltip="' +
					window.delivereeConfig.deliveree_name +
					"'s total price for this booking, including extra services.\"></span></td>" +
					"</tr>" +
					'<tr valign="top">' +
					'<th scope="row" class="titledesc">' +
					'<label ><span class="footer-price">' +
					selected_vehicle_type.currency +
					'<span/> <span data-total-extra-services="0"   data-extra-services-goods-insurance="' +
					JSON.stringify(extra_services_goods_insurance).replaceAll('"', "'") +
					'" data-list-extra-services="[]"  id="total_extra_services" class="footer-price">0<span/></label>' +
					"</th>" +
					'<td class="forminp forminp-deliveree-readonly"><span class="footer-price">' +
					selected_vehicle_type.currency +
					'<span/> <span id="total_fees_extra_services" data-total-fees-extra-services="' +
					form_total_fees +
					'" class="footer-price">' +
					order_list.numberWithCommas(form_total_fees, "") +
					"<span/></td>" +
					"</tr>" +
					"</tbody>" +
					"</table>" +
					"</div>",
				focusConfirm: false,
				confirmButtonText: "Confirm Booking",
				cancelButtonText: "Back",
				showCancelButton: true,
				reverseButtons: true,
				preConfirm: () => {},
				customClass: {
					container: "select-vehicle",
					popup: "popup-select-vehicle",
					header: "header-select-vehicle",
					title: "title-select-vehicle",
					closeButton: "...",
					icon: "...",
					image: "...",
					content: "content-select-vehicle",
					htmlContainer: "...",
					input: "...",
					inputLabel: "...",
					validationMessage: "...",
					actions: "actions-select-vehicle",
					confirmButton: "confirmButton-select-vehicle",
					denyButton: "denyButton-select-vehicle",
					cancelButton: "cancelButton-select-vehicle",
					loader: "...",
					footer: "....",
				},
			},
			function () {}
		).then((result) => {
			if (result.isConfirmed) {
				// let confirm_vehicle_type = JSON.parse(item.vehicle_type);
				let now = moment(new Date());
				let curent_pickup_time = moment(
					save_value_pickup_time_datetimepicker,
					"DD-MMM-YYYY HH:mm",
					true
				); //.add(save_value_pickup_time_quick_choice_id, 'minutes');
				let time_type = curent_pickup_time > now ? "schedule" : "now";
				let pickup_time =
					curent_pickup_time > now ? curent_pickup_time.toISOString() : "";
				let extra_services = [];
				let element_extra_services = jQuery("#total_extra_services");
				let list_extra_services = element_extra_services.data(
					"list-extra-services"
				);
				let string_extra_services_goods_insurance = element_extra_services.data(
					"extra-services-goods-insurance"
				);
				let extra_services_goods_insurance = JSON.parse(
					string_extra_services_goods_insurance.replaceAll("'", '"')
				);
				let optimize_route = jQuery("#optimize_route").val();

				for (let index = 0; index < list_extra_services.length; index++) {
					let extra_service_id = list_extra_services[index];
					extra_services.push({
						extra_requirement_id: parseInt(extra_service_id),
						selected_amount: 1,
					});
				}

				extra_services.push(extra_services_goods_insurance);

				let data = {
					order_ids: order_ids,
					vehicle_type_id: selected_vehicle_type.vehicle_type_id,
					time_type: time_type,
					pickup_time: pickup_time,
					extra_services: extra_services,
					vehicle_type: selected_vehicle_type,
					quick_choice_id: save_value_pickup_time_quick_choice_id,
					optimize_route: optimize_route,
				};

				order_list.dontShowPopupCreateBooking(data);
			} else if (result.isDismissed) {
				let orderSelected = order_list.getOrderSelected();
				order_list.showConfirmBooking(
					quotes_response,
					orderSelected.items,
					selected_vehicle_type
				);
			}
		});

		jQuery(".woocommerce-help-tip").darkTooltip();

		jQuery("body").on("change", "#insurance-policy", function (event) {
			let isChecked = jQuery(this).is(":checked");
			if (isChecked) {
				jQuery(".confirmButton-select-vehicle").prop("disabled", false);
			} else {
				jQuery(".confirmButton-select-vehicle").prop("disabled", true);
			}
		});
	},
	addExtraServices: function () {
		jQuery("body").on("change", ".input-extra-services", function (event) {
			let unit_price = jQuery(this).data("unit-price");
			let extra_service_id = jQuery(this).val();
			let element_extra_services = jQuery("#total_extra_services");
			let total_extra_services = element_extra_services.data(
				"total-extra-services"
			);
			let list_extra_services = element_extra_services.data(
				"list-extra-services"
			);

			if (jQuery(this).is(":checked")) {
				total_extra_services += unit_price;
				list_extra_services.push(extra_service_id);
			} else {
				let index = list_extra_services.indexOf(extra_service_id);
				if (index > -1) {
					list_extra_services.splice(index, 1);
					total_extra_services -= unit_price;
				}
			}

			element_extra_services.data("total-extra-services", total_extra_services);
			element_extra_services.data("list-extra-services", list_extra_services);
			element_extra_services.html(
				order_list.numberWithCommas(total_extra_services, "")
			);

			let total_extra_fees_services = jQuery("#total_fees_extra_services").data(
				"total-fees-extra-services"
			);
			jQuery("#total_fees_extra_services").html(
				order_list.numberWithCommas(
					total_extra_fees_services + total_extra_services,
					""
				)
			);
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
		mscConfirm(
			"Create Booking",
			"Are you sure create booking with order " + data["order_id"],
			function () {
				order_list.dontShowPopupCreateBooking(data);
			}
		);
	},
	affterCreateBooking: function (order_ids) {
		mscConfirm({
			title: "Booking Created",
			subtitle:
				"Your booking with the order number " +
				order_ids +
				" has been created successfully. Please go to the Bookings section to check the booking status.", // default: ''
			okText: "Go to Bookings",
			cancelText: "Ok", // default: Cancel,
			dismissOverlay: true, // default: false, closes dialog box when clicked on overlay.
			onOk: function () {
				let site_url = jQuery("#get_site_url").data("site-url");
				window.location.href =
					site_url + "/wp-admin/admin.php?page=bookings_deliveree";
			},
			onCancel: function () {
				window.location.reload();
			},
		});
	},
	dontShowPopupCreateBooking: function (data) {
		jQuery.LoadingOverlay("show");

		data.action = "createOrderMultiple";
		return jQuery
			.ajax({
				url: jQuery("#urlForm").data("admin-url-ajax"),
				type: "POST",
				dataType: "json",
				async: true,
				data: data,
			})
			.done(function (response) {
				jQuery.LoadingOverlay("hide");
				if (response.status) {
					order_list.affterCreateBooking(response.order_ids);
				} else {
					mscAlert(response.message);
				}
			});
	},
	deleteBoookingOrders: function () {
		jQuery("body").on("click", "a.delete-order-item", function (event) {
			event.preventDefault();
			const valid = jQuery(this).attr("data-id");
			const order_id = jQuery(this).attr("data-order-id");
			mscConfirm(
				"Are You Sure?",
				"You’re about to delete order number #" +
					order_id +
					". After deleting, the order status in your platform will be shown as “Cancelled”.",
				function () {
					jQuery.LoadingOverlay("show");

					jQuery
						.ajax({
							url: jQuery("#urlForm").data("admin-url-ajax"),
							type: "POST",
							dataType: "json",
							async: true,
							data: {
								action: "deleteBoookingOrders",
								booking_ids_arr: [valid],
							},
						})
						.done(function (response) {
							jQuery.LoadingOverlay("hide");
							location.reload();
						});
				}
			);
		});
	},
	trashBoookingOrders: function () {
		jQuery("body").on("click", "a.move-to-trash", function (event) {
			event.preventDefault();
			const valid = jQuery(this).attr("data-id");
			jQuery.LoadingOverlay("show");

			jQuery
				.ajax({
					url: jQuery("#urlForm").data("admin-url-ajax"),
					type: "POST",
					dataType: "json",
					async: true,
					data: {
						action: "trashBoookingOrders",
						ids: [valid],
					},
				})
				.done(function (response) {
					jQuery.LoadingOverlay("hide");
					location.reload();
				});
		});
	},
	restoreBoookingOrders: function () {
		jQuery("body").on("click", "a.restore-boooking-orders", function (event) {
			event.preventDefault();
			const valid = jQuery(this).attr("data-id");
			jQuery.LoadingOverlay("show");

			jQuery
				.ajax({
					url: jQuery("#urlForm").data("admin-url-ajax"),
					type: "POST",
					dataType: "json",
					async: true,
					data: {
						action: "restoreBoookingOrders",
						ids: [valid],
					},
				})
				.done(function (response) {
					jQuery.LoadingOverlay("hide");
					location.reload();
				});
		});
	},

	addExtraServicesGoodsInsurance: function () {
		jQuery("body").on("click", ".row-small-table", function (event) {
			let element_extra_services = jQuery("#total_extra_services");
			let total_extra_services = element_extra_services.data(
				"total-extra-services"
			);
			let list_extra_services = element_extra_services.data(
				"list-extra-services"
			);
			let string_extra_services_goods_insurance = element_extra_services.data(
				"extra-services-goods-insurance"
			);
			let extra_services_goods_insurance = JSON.parse(
				string_extra_services_goods_insurance.replaceAll("'", '"')
			);
			let ischecked = jQuery(this).hasClass("row-small-table-checked");
			let all_row_small_table = jQuery(".row-small-table");
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
					if (
						extra_service_id ==
						extra_services_goods_insurance.extra_requirement_pricing_id
					) {
						let fees = jQuery(row_small_table).data("fees");
						total_extra_services -= fees;
					}
				}

				jQuery(this).addClass("row-small-table-checked");
				total_extra_services += checked_fees;
				extra_services_goods_insurance.extra_requirement_pricing_id =
					checked_extra_service_id;
			}

			element_extra_services.data("total-extra-services", total_extra_services);
			element_extra_services.data("list-extra-services", list_extra_services);
			element_extra_services.data(
				"extra-services-goods-insurance",
				JSON.stringify(extra_services_goods_insurance).replaceAll('"', "'")
			);
			element_extra_services.html(
				order_list.numberWithCommas(total_extra_services, "")
			);

			let total_extra_fees_services = jQuery("#total_fees_extra_services").data(
				"total-fees-extra-services"
			);
			jQuery("#total_fees_extra_services").html(
				order_list.numberWithCommas(
					total_extra_fees_services + total_extra_services,
					""
				)
			);
		});
	},
};

(function ($) {
	var gb_tbody = jQuery("form#group-booking-table #the-list");
	gb_tbody.sortable({
		items: "tr",
		cursor: "move",
		axis: "y",
		handle: "td.sort_group_booking",
		scrollSensitivity: 40,
		update: function (event, ui) {
			// let btnUpdateSort = jQuery(".update-sort-booking-group");
			// if (!btnUpdateSort.hasClass("show")) btnUpdateSort.addClass("show");
			var elmArray = document.querySelectorAll(".warraper-arrow-sort");
			var gb_ids_array = [];
			elmArray.forEach(function (item) {
				var gb_id = item.getAttribute("data-booking-id");
				gb_ids_array.push(gb_id);
			});

			let url = new URL(window.location.href);
			let gb_ids_str = "";
			if (gb_ids_array) gb_ids_str = gb_ids_array.join("-");
			url.searchParams.set("group_booking_ids", gb_ids_str);
			window.location.href = url.toString();
		},
	});

	jQuery("tbody").on("click", ".toggle-row", function () {
		jQuery(this).closest("tr").toggleClass("is-expanded");
	});
	$("body").on("change", ".select_vehicle_type_id", function () {
		var option = $("option:selected", this).attr("data-value");
		$(this).closest("tr").find(".booking_fee").text(option);
	});

	$("body").on("click", ".confirmBooking", function () {
		var orderIdsMessage = $(this).attr("data-order-ids-for-message");
		var tr = $(this).closest("tr");
		var time_type = $(".select_time_type").val();
		var pickup_time = $(".select_pickup_time").val();

		if (time_type == "schedule" && pickup_time === "") {
			mscAlert("Please select pickup time");
			return false;
		}

		var data = {
			order_id: orderIdsMessage,
			vehicle_type_id: tr.find(".select_vehicle_type_id").val(),
			time_type: time_type,
		};

		if (time_type == "schedule") {
			let correctDateTimeStr = "";
			try {
				const dateTimePartsArr = pickup_time.split(" ");
				const datePartsArr = dateTimePartsArr[0].split("/");
				const timePartsArr = dateTimePartsArr[1].split(":");
				correctDateTimeStr =
					datePartsArr[2] +
					"/" +
					datePartsArr[1] +
					"/" +
					datePartsArr[0] +
					" " +
					timePartsArr[0] +
					":" +
					timePartsArr[1] +
					" GMT+" +
					$gmtoffset;
			} catch (e) {
				alert("Time format is incorrect !");
				console.log(e);
				return false;
			}
			data["pickup_time"] = new Date(correctDateTimeStr).toISOString();
		}

		order_list.createBooking(data);
	});

	var d = new Date();
	var logic = function (currentDateTime) {
		if (currentDateTime.getDate() == d.getDate()) {
			this.setOptions({
				minTime: d.getHours() + ":" + d.getMinutes(),
			});
		} else {
			this.setOptions({
				minTime: "00:00",
			});
		}
	};

	$(".pickup_time_datepicker").datetimepicker({
		format: "d/m/Y H:i",
		minDate: new Date(),
		minTime: d.getHours() + ":" + d.getMinutes(),
		onChangeDateTime: logic,
	});

	$("body").on("click", ".collapse", (e) => {
		$("tr" + e.currentTarget.dataset.show).toggle();
		$(e.target).closest("thead").next("tbody").toggle();
		$(e.target)
			.closest("thead")
			.find(".collapse-select")
			.toggleClass("dashicons-arrow-up");
		if (e.currentTarget.dataset.show == ".selected") {
			$("tr.optimise-route").toggle();
		}
	});

	$("body").on("click", ".viewErrorMessage", function (event) {
		var message = $(this).closest("div").find("p.error-message").text();
		Swal.fire({
			text: message,
		});
	});
})(jQuery);
