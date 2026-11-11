<?php
/**
 * My Account sidebar (Figma "Account / Sidebar"): menu plus the Need help? card.
 *
 * Based on WooCommerce templates/myaccount/navigation.php.
 *
 * @package ABF_Accounting
 * @version 9.3.0
 */

if (! defined('ABSPATH')) {
	exit;
}

$abf_accounting_contact = abf_accounting_contact();

do_action('woocommerce_before_account_navigation');
?>

<div class="abf-account-sidebar">
	<nav class="woocommerce-MyAccount-navigation" aria-label="<?php esc_attr_e('Account pages', 'woocommerce'); ?>">
		<ul>
			<?php foreach (wc_get_account_menu_items() as $endpoint => $label) : ?>
				<li class="<?php echo wc_get_account_menu_item_classes($endpoint); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
					<a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>" <?php echo wc_is_current_account_menu_item($endpoint) ? 'aria-current="page"' : ''; ?>>
						<?php echo esc_html($label); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>

	<div class="abf-account-help">
		<p class="abf-account-help__title"><?php esc_html_e('Need help?', 'abf-accounting'); ?></p>
		<p class="abf-account-help__text">
			<?php
			/* translators: %s: phone number. */
			printf(esc_html__('Call %s or request a callback.', 'abf-accounting'), '<a href="' . esc_attr($abf_accounting_contact['phone_uri']) . '">' . esc_html(str_replace(' 141 ', ' 141', $abf_accounting_contact['phone'])) . '</a>');
			?>
		</p>
		<a class="abf-account-help__button" href="<?php echo esc_url(home_url('/contact/#callback-form')); ?>"><?php esc_html_e('Request a callback', 'abf-accounting'); ?></a>
	</div>
</div>

<?php do_action('woocommerce_after_account_navigation'); ?>
