<?php
/**
 * Order summary rows (Figma "Your order"): each package with its price, Subtotal, Total.
 * The root element keeps the class WooCommerce replaces when the checkout refreshes.
 *
 * Based on WooCommerce templates/checkout/review-order.php.
 *
 * @package ABF_Accounting
 * @version 11.2.2
 */

defined('ABSPATH') || exit;
?>
<div class="abf-summary woocommerce-checkout-review-order-table">
	<?php
	do_action('woocommerce_review_order_before_cart_contents');

	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
		$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$visible  = apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		if ($_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && $visible) {
			$cart_item_name = WC()->cart->get_item_product_name($cart_item, $_product);
			?>
			<div class="abf-summary__row abf-summary__row--item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment ?>">
				<span class="abf-summary__label">
					<?php echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $cart_item_name, $cart_item, $cart_item_key)); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment ?>
					<?php echo wc_get_formatted_cart_item_data($cart_item, false, $cart_item_name); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<span class="abf-summary__value"><?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WooCommerce.Commenting.CommentHooks.MissingHookComment ?></span>
			</div>
			<?php
		}
	}

	do_action('woocommerce_review_order_after_cart_contents');
	?>

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

	<?php do_action('woocommerce_review_order_before_order_total'); ?>

	<div class="abf-summary__row abf-summary__row--total order-total">
		<span class="abf-summary__label"><?php esc_html_e('Total', 'woocommerce'); ?></span>
		<span class="abf-summary__value"><?php wc_cart_totals_order_total_html(); ?></span>
	</div>

	<?php do_action('woocommerce_review_order_after_order_total'); ?>
</div>
