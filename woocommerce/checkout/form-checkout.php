<?php
/**
 * Checkout form (Figma "Shop / Checkout"): billing details and payment in the left card, the
 * order summary in the mint card on the right.
 *
 * Based on WooCommerce templates/checkout/form-checkout.php.
 *
 * @package ABF_Accounting
 * @version 9.4.0
 */

if (! defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_before_checkout_form', $checkout);

if (! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce'))); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
    return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout abf-shop-layout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>">

	<div class="abf-shop-card abf-checkout__main">
		<?php if ($checkout->get_checkout_fields()) : ?>

			<?php do_action('woocommerce_checkout_before_customer_details'); ?>

			<div id="customer_details">
				<?php do_action('woocommerce_checkout_billing'); ?>
				<?php do_action('woocommerce_checkout_shipping'); ?>
			</div>

			<?php do_action('woocommerce_checkout_after_customer_details'); ?>

		<?php endif; ?>

		<h2 class="abf-shop-card__title abf-checkout__payment-title"><?php esc_html_e('Payment', 'abf-accounting'); ?></h2>

		<?php woocommerce_checkout_payment(); ?>
	</div>

	<aside class="abf-shop-summary" aria-labelledby="order_review_heading">
		<?php do_action('woocommerce_checkout_before_order_review_heading'); ?>

		<h2 id="order_review_heading" class="abf-shop-summary__title"><?php esc_html_e('Your order', 'abf-accounting'); ?></h2>
		<a class="abf-shop-summary__edit" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Edit cart', 'abf-accounting'); ?></a>

		<?php do_action('woocommerce_checkout_before_order_review'); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php woocommerce_order_review(); ?>
		</div>

		<?php do_action('woocommerce_checkout_after_order_review'); ?>

		<p class="abf-shop-summary__note"><?php esc_html_e('No VAT is added to our prices.', 'abf-accounting'); ?></p>
		<?php echo abf_accounting_shop_trust(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</aside>

</form>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
