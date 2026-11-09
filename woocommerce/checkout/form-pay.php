<?php
/**
 * Pay for order (Figma "Shop / Order Pay"): payment card on the left, Order details on the right.
 *
 * Based on WooCommerce templates/checkout/form-pay.php.
 *
 * @package ABF_Accounting
 * @version 10.9.0
 */

defined('ABSPATH') || exit;

$abf_accounting_names = array();
foreach ($order->get_items() as $abf_accounting_item) {
	if (apply_filters('woocommerce_order_item_visible', true, $abf_accounting_item)) { // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$abf_accounting_names[] = $abf_accounting_item->get_name();
	}
}
$abf_accounting_rows = array(
	array(__('Order number', 'abf-accounting'), $order->get_order_number()),
	array(__('Date', 'abf-accounting'), wc_format_datetime($order->get_date_created(), 'j F Y')),
	array(_n('Package', 'Packages', count($abf_accounting_names), 'abf-accounting'), implode(', ', $abf_accounting_names)),
);
?>
<form id="order_review" method="post" class="abf-shop-layout">

	<div class="abf-shop-card abf-checkout__main">
		<h2 class="abf-shop-card__title"><?php esc_html_e('Payment', 'abf-accounting'); ?></h2>

		<?php do_action('woocommerce_pay_order_before_payment'); ?>

		<div id="payment">
			<?php if ($order->needs_payment()) : ?>
				<ul class="wc_payment_methods payment_methods methods" aria-label="<?php esc_attr_e('Payment methods', 'woocommerce'); ?>">
					<?php
					if (! empty($available_gateways)) {
						foreach ($available_gateways as $gateway) {
							wc_get_template('checkout/payment-method.php', array('gateway' => $gateway));
						}
					} else {
						echo '<li>';
						wc_print_notice(apply_filters('woocommerce_no_available_payment_methods_message', esc_html__('Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce')), 'notice'); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
						echo '</li>';
					}
					?>
				</ul>
			<?php endif; ?>
			<div class="form-row">
				<input type="hidden" name="woocommerce_pay" value="1" />

				<?php wc_get_template('checkout/terms.php'); ?>

				<?php do_action('woocommerce_pay_order_before_submit'); ?>

				<?php echo apply_filters('woocommerce_pay_order_button_html', '<button type="submit" class="button alt" id="place_order" value="' . esc_attr($order_button_text) . '" data-value="' . esc_attr($order_button_text) . '">' . esc_html($order_button_text) . '</button>'); // phpcs:ignore WordPress.Security.EscapeOutput, WooCommerce.Commenting.CommentHooks.MissingHookComment ?>

				<?php do_action('woocommerce_pay_order_after_submit'); ?>

				<?php wp_nonce_field('woocommerce-pay', 'woocommerce-pay-nonce'); ?>
			</div>
		</div>
	</div>

	<aside class="abf-shop-summary">
		<h2 class="abf-shop-summary__title"><?php esc_html_e('Order details', 'abf-accounting'); ?></h2>
		<div class="abf-summary">
			<?php foreach ($abf_accounting_rows as [$abf_accounting_label, $abf_accounting_value]) : ?>
				<div class="abf-summary__row abf-summary__row--detail">
					<span class="abf-summary__label"><?php echo esc_html($abf_accounting_label); ?></span>
					<span class="abf-summary__value"><?php echo esc_html($abf_accounting_value); ?></span>
				</div>
			<?php endforeach; ?>
			<div class="abf-summary__row abf-summary__row--total">
				<span class="abf-summary__label"><?php esc_html_e('Total', 'woocommerce'); ?></span>
				<span class="abf-summary__value"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
			</div>
		</div>
		<?php echo abf_accounting_shop_trust(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</aside>
</form>
