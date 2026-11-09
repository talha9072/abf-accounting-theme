<?php
/**
 * Cart totals (Figma "Cart totals"): Subtotal, Total, Proceed to checkout, Stripe note and
 * the Trustpilot line. No shipping or VAT rows: packages are services with no VAT added.
 *
 * Based on WooCommerce templates/cart/cart-totals.php.
 *
 * @package ABF_Accounting
 * @version 2.3.6
 */

defined('ABSPATH') || exit;
?>
<div class="cart_totals abf-shop-summary">

	<?php do_action('woocommerce_before_cart_totals'); ?>

	<h2 class="abf-shop-summary__title"><?php esc_html_e('Cart totals', 'woocommerce'); ?></h2>

	<div class="abf-summary">
		<div class="abf-summary__row cart-subtotal">
			<span class="abf-summary__label"><?php esc_html_e('Subtotal', 'woocommerce'); ?></span>
			<span class="abf-summary__value"><?php wc_cart_totals_subtotal_html(); ?></span>
		</div>

		<?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
			<div class="abf-summary__row cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
				<span class="abf-summary__label"><?php wc_cart_totals_coupon_label($coupon); ?></span>
				<span class="abf-summary__value"><?php wc_cart_totals_coupon_html($coupon); ?></span>
			</div>
		<?php endforeach; ?>

		<?php foreach (WC()->cart->get_fees() as $fee) : ?>
			<div class="abf-summary__row fee">
				<span class="abf-summary__label"><?php echo esc_html($fee->name); ?></span>
				<span class="abf-summary__value"><?php wc_cart_totals_fee_html($fee); ?></span>
			</div>
		<?php endforeach; ?>

		<?php do_action('woocommerce_cart_totals_before_order_total'); ?>

		<div class="abf-summary__row abf-summary__row--total order-total">
			<span class="abf-summary__label"><?php esc_html_e('Total', 'woocommerce'); ?></span>
			<span class="abf-summary__value"><?php wc_cart_totals_order_total_html(); ?></span>
		</div>

		<?php do_action('woocommerce_cart_totals_after_order_total'); ?>
	</div>

	<div class="wc-proceed-to-checkout">
		<?php do_action('woocommerce_proceed_to_checkout'); ?>
	</div>

	<p class="abf-shop-summary__secure"><?php esc_html_e('Secure payments by Stripe', 'abf-accounting'); ?></p>
	<?php echo abf_accounting_shop_trust(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

	<?php do_action('woocommerce_after_cart_totals'); ?>

</div>
