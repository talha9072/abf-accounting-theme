<?php
/**
 * Log in and Create an account cards (Figma "My Account / Log in & Register").
 *
 * Based on WooCommerce templates/myaccount/form-login.php.
 *
 * @package ABF_Accounting
 * @version 9.9.0
 */

if (! defined('ABSPATH')) {
	exit;
}

$abf_accounting_register = 'yes' === get_option('woocommerce_enable_myaccount_registration');
$abf_accounting_required = '&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">' . esc_html__('Required', 'woocommerce') . '</span>';

do_action('woocommerce_before_customer_login_form'); ?>

<div class="abf-account-auth<?php echo $abf_accounting_register ? '' : ' abf-account-auth--single'; ?>" id="customer_login">

	<div class="abf-shop-card">
		<h2 class="abf-shop-card__title"><?php esc_html_e('Log in', 'abf-accounting'); ?></h2>

		<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

			<?php do_action('woocommerce_login_form_start'); ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username"><?php esc_html_e('Email address', 'abf-accounting'); ?><?php echo $abf_accounting_required; // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" placeholder="<?php esc_attr_e('name@example.co.uk', 'abf-accounting'); ?>" value="<?php echo (! empty($_POST['username']) && is_string($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; // phpcs:ignore WordPress.Security ?>" required aria-required="true" />
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password"><?php esc_html_e('Password', 'woocommerce'); ?><?php echo $abf_accounting_required; // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="••••••••" required aria-required="true" />
			</p>

			<?php do_action('woocommerce_login_form'); ?>

			<div class="abf-account-auth__row">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e('Remember me', 'woocommerce'); ?></span>
				</label>
				<a class="abf-account-auth__lost" href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Lost your password?', 'woocommerce'); ?></a>
			</div>

			<p class="form-row">
				<?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e('Log in', 'woocommerce'); ?>"><?php esc_html_e('Log in', 'woocommerce'); ?></button>
			</p>

			<?php do_action('woocommerce_login_form_end'); ?>

		</form>
	</div>

	<?php if ($abf_accounting_register) : ?>
		<div class="abf-shop-card">
			<h2 class="abf-shop-card__title"><?php esc_html_e('Create an account', 'abf-accounting'); ?></h2>

			<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action('woocommerce_register_form_tag'); ?> >

				<?php do_action('woocommerce_register_form_start'); ?>

				<?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_username"><?php esc_html_e('Username', 'woocommerce'); ?><?php echo $abf_accounting_required; // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo (! empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; // phpcs:ignore WordPress.Security ?>" required aria-required="true" />
					</p>
				<?php endif; ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_email"><?php esc_html_e('Email address', 'woocommerce'); ?><?php echo $abf_accounting_required; // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" placeholder="<?php esc_attr_e('name@example.co.uk', 'abf-accounting'); ?>" value="<?php echo (! empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; // phpcs:ignore WordPress.Security ?>" required aria-required="true" />
				</p>

				<?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_password"><?php esc_html_e('Password', 'woocommerce'); ?><?php echo $abf_accounting_required; // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" placeholder="<?php esc_attr_e('Choose a password', 'abf-accounting'); ?>" required aria-required="true" />
					</p>
				<?php else : ?>
					<p><?php esc_html_e('A link to set a new password will be sent to your email address.', 'woocommerce'); ?></p>
				<?php endif; ?>

				<?php do_action('woocommerce_register_form'); ?>

				<p class="woocommerce-form-row form-row">
					<?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
					<button type="submit" class="woocommerce-Button woocommerce-button button abf-button-outline woocommerce-form-register__submit" name="register" value="<?php esc_attr_e('Register', 'woocommerce'); ?>"><?php esc_html_e('Register', 'woocommerce'); ?></button>
				</p>

				<?php do_action('woocommerce_register_form_end'); ?>

			</form>
		</div>
	<?php endif; ?>

</div>

<?php do_action('woocommerce_after_customer_login_form'); ?>
