<?php
/**
 * My Account dashboard (Figma "My Account / Dashboard"): greeting and three quick links.
 *
 * Based on WooCommerce templates/myaccount/dashboard.php.
 *
 * @package ABF_Accounting
 * @version 4.4.0
 */

if (! defined('ABSPATH')) {
	exit;
}

$abf_accounting_links = array(
	array('orders', __('Orders', 'abf-accounting'), __('See your orders and receipts', 'abf-accounting')),
	array('edit-address', __('Addresses', 'abf-accounting'), __('Update your billing address', 'abf-accounting')),
	array('edit-account', __('Account details', 'abf-accounting'), __('Change your name, email or password', 'abf-accounting')),
);
?>
<div class="abf-account-dashboard">
	<h2 class="abf-shop-card__title">
		<?php
		/* translators: %s: first name. */
		printf(esc_html__('Hello %s', 'abf-accounting'), esc_html($current_user->first_name ?: $current_user->display_name));
		?>
	</h2>
	<p class="abf-shop-card__text"><?php esc_html_e('From your account dashboard you can view your recent orders, manage your billing address, and change your password and account details.', 'abf-accounting'); ?></p>

	<ul class="abf-account-links">
		<?php foreach ($abf_accounting_links as [$abf_accounting_endpoint, $abf_accounting_title, $abf_accounting_text]) : ?>
			<li>
				<a href="<?php echo esc_url(wc_get_account_endpoint_url($abf_accounting_endpoint)); ?>">
					<span class="abf-account-links__title"><?php echo esc_html($abf_accounting_title); ?></span>
					<span class="abf-account-links__text"><?php echo esc_html($abf_accounting_text); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php
do_action('woocommerce_account_dashboard');
do_action('woocommerce_before_my_account'); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
do_action('woocommerce_after_my_account'); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
