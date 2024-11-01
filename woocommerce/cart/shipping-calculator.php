<?php

/**
 * Shipping Calculator
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/shipping-calculator.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 4.0.0
 */

defined('ABSPATH') || exit;

echo '<div id="woocommerce-notices-wrapper-calculator" style=" color: red; margin: 10px 0; "></div>';

do_action('woocommerce_before_shipping_calculator');

//

if (WC()->customer->get_id()) {

	$wc_customer_shipping_latitude = get_user_meta(WC()->customer->get_id(),  'wc_customer_shipping_latitude', true);
	$wc_customer_shipping_longitude = get_user_meta(WC()->customer->get_id(),  'wc_customer_shipping_longitude', true);
} else {
	$wc_customer_shipping_latitude = WC()->session->get('wc_customer_shipping_latitude');
	$wc_customer_shipping_longitude = WC()->session->get('wc_customer_shipping_longitude');
}

$deliveree_google_api_key = get_option('deliveree_google_api_key', '');

?>
<form class="woocommerce-shipping-calculator" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">

	<?php printf('<a href="#" class="shipping-calculator-button">%s</a>', esc_html(!empty($button_text) ? $button_text : __('Calculate shipping', 'deliveree-same-day'))); ?>
	<?php

	$raw_countries = get_option('woocommerce_specific_ship_to_countries');
	$selected = WC()->customer->get_shipping_country();
	if (WC()->customer->get_shipping_country() == '' && !empty($raw_countries)) {
		$selected = $raw_countries[0];
	}
	?>

	<section class="shipping-calculator-form" style="display:none;">
		<?php if ($deliveree_google_api_key != '') { ?>
			<div><a href="#" class="pin-your-address" id="pin-your-address"><?php esc_attr_e('Pin your address on the map', 'deliveree-same-day'); ?></a></div>
			<p>or</p>
			<div><?php esc_attr_e('Enter your address below', 'deliveree-same-day'); ?></div>
		<?php } ?>
		<?php if (apply_filters('woocommerce_shipping_calculator_enable_country', true)) : ?>
			<div style="display:none">
				<div><strong><?php esc_html_e('Country / region', 'deliveree-same-day'); ?></strong></div>
				<p class="form-row form-row-wide" id="calc_shipping_country_field">
					<select name="calc_shipping_country" id="calc_shipping_country" class="country_to_state country_select" rel="calc_shipping_state">
						<option value=""><?php esc_html_e('Select a country / region&hellip;', 'deliveree-same-day'); ?></option>
						<?php
						foreach (WC()->countries->get_shipping_countries() as $key => $value) {
							echo '<option value="' . esc_attr($key) . '"' . selected($selected, esc_attr($key), false) . '>' . esc_html($value) . '</option>';
						}
						?>
					</select>
				</p>
			</div>
		<?php endif; ?>
		<?php if (apply_filters('woocommerce_shipping_calculator_enable_postcode', true)) : ?>
			<div><strong><?php esc_attr_e('Postal Code', 'deliveree-same-day'); ?></strong></div>
			<p class="form-row form-row-wide" id="calc_shipping_postcode_field">
				<input type="number" class="input-text calc_shipping_get_address_google" value="<?php echo esc_attr(WC()->customer->get_shipping_postcode()); ?>" placeholder="<?php esc_attr_e('Postal Code', 'deliveree-same-day'); ?>" name="calc_shipping_postcode" id="calc_shipping_postcode" />
			</p>
		<?php endif; ?>

		<?php if (apply_filters('woocommerce_shipping_calculator_enable_state', true)) : ?>
			<div><strong><?php esc_attr_e('Province', 'deliveree-same-day'); ?></strong></div>
			<p class="form-row form-row-wide" id="calc_shipping_state_field">
				<?php
				$current_cc = WC()->customer->get_shipping_country();
				$current_r  = WC()->customer->get_shipping_state();
				$states     = WC()->countries->get_states($current_cc);

				?>
				<input type="text" class="input-text" value="<?php echo esc_attr($current_r); ?>" placeholder="<?php esc_attr_e('Province', 'deliveree-same-day'); ?>" name="calc_shipping_state" id="calc_shipping_state_input" />

			</p>
		<?php endif; ?>

		<?php if (apply_filters('woocommerce_shipping_calculator_enable_city', true)) : ?>
			<div><strong><?php esc_attr_e('City/Town', 'deliveree-same-day'); ?></strong></div>
			<p class="form-row form-row-wide" id="calc_shipping_city_field">
				<input type="text" class="input-text" value="<?php echo esc_attr(WC()->customer->get_shipping_city()); ?>" placeholder="<?php esc_attr_e('City/Town', 'deliveree-same-day'); ?>" name="calc_shipping_city" id="calc_shipping_city" />
			</p>
		<?php endif; ?>


		<?php if (apply_filters('woocommerce_shipping_calculator_enable_street_address', true)) : ?>
			<div><strong><?php esc_html_e('Street Address*', 'deliveree-same-day'); ?></strong></div>
			<p class="form-row form-row-wide" id="calc_shipping_address_1_field">
			<div id="hidden_map" style="display: none;"></div>
			<?php if ($deliveree_google_api_key != '') { ?>
				<input type="hidden" name="calc_shipping_latitude" id="calc_shipping_latitude" value="<?php echo esc_attr($wc_customer_shipping_latitude) ?>" />
				<input type="hidden" name="calc_shipping_longitude" id="calc_shipping_longitude" value="<?php echo esc_attr($wc_customer_shipping_longitude) ?>" />
			<?php } ?>

			<input style="width:100%" required type="text" class="input-text " value="<?php echo WC()->customer->get_id() ? esc_attr(WC()->customer->get_shipping_address_1()) : ''; ?>" placeholder="<?php esc_attr_e('House number and Street number', 'deliveree-same-day'); ?>" name="calc_shipping_address_1" id="calc_shipping_address_1" />
			</p>
			<p class="form-row form-row-wide" id="calc_shipping_address_2_field">
				<input type="text" class="input-text " value="<?php echo esc_attr(WC()->customer->get_shipping_address_2()); ?>" placeholder="<?php esc_attr_e('Apartment, Suite, etc., (optional)', 'deliveree-same-day'); ?>" name="calc_shipping_address_2" id="calc_shipping_address_2" />
			</p>

		<?php endif; ?>


		<p><button type="submit" name="calc_shipping" value="1" class="button"><?php esc_html_e('Update', 'deliveree-same-day'); ?></button></p>
		<?php wp_nonce_field('woocommerce-shipping-calculator', 'woocommerce-shipping-calculator-nonce'); ?>
	</section>
</form>

<?php do_action('woocommerce_after_shipping_calculator'); ?>