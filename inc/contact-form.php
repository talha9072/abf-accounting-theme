<?php
/**
 * Callback request form (Contact page): [abf_callback_form].
 *
 * Posts to admin-post.php, is checked against a nonce and a honeypot, emailed to the site
 * admin (filter `abf_accounting_contact_recipient`), then the visitor goes to the Thank You page.
 * Figma mentions reCAPTCHA; that needs the client's site keys, so the honeypot guards it for now.
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

const ABF_ACCOUNTING_CONTACT_ACTION = 'abf_callback_request';

/**
 * Form markup.
 */
function abf_accounting_callback_form_shortcode(): string
{
    $status = isset($_GET['contact']) ? sanitize_key(wp_unslash($_GET['contact'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $fields = array(
        array('first_name', __('First name', 'abf-accounting'), 'text', __('Enter your first name', 'abf-accounting'), 'given-name', true),
        array('last_name', __('Last name', 'abf-accounting'), 'text', __('Enter your last name', 'abf-accounting'), 'family-name', true),
        array('email', __('Email', 'abf-accounting'), 'email', 'example@company.com', 'email', true),
        array('phone', __('Phone number', 'abf-accounting'), 'tel', __('Enter your phone number', 'abf-accounting'), 'tel-national', true),
    );

    ob_start();
    ?>
    <form class="abf-form" id="callback-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php if ('invalid' === $status) : ?>
            <p class="abf-form__notice" role="alert"><?php esc_html_e('Please fill in your name, a valid email address and your phone number.', 'abf-accounting'); ?></p>
        <?php elseif ('failed' === $status) : ?>
            <p class="abf-form__notice" role="alert"><?php esc_html_e('Sorry, your request could not be sent. Please try again or call us.', 'abf-accounting'); ?></p>
        <?php endif; ?>
        <input type="hidden" name="action" value="<?php echo esc_attr(ABF_ACCOUNTING_CONTACT_ACTION); ?>">
        <input type="hidden" name="redirect_to" value="<?php echo esc_url(get_permalink()); ?>">
        <?php wp_nonce_field(ABF_ACCOUNTING_CONTACT_ACTION, 'abf_contact_nonce'); ?>
        <div class="abf-form__hp" aria-hidden="true">
            <label for="abf-company-website"><?php esc_html_e('Leave this empty', 'abf-accounting'); ?></label>
            <input type="text" id="abf-company-website" name="company_website" tabindex="-1" autocomplete="off">
        </div>
        <div class="abf-form__grid">
            <?php foreach ($fields as [$name, $label, $type, $placeholder, $autocomplete, $required]) : ?>
                <div class="abf-form__field abf-form__field--<?php echo esc_attr($name); ?>">
                    <label for="abf-<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?><?php echo $required ? ' <span aria-hidden="true">*</span>' : ''; ?></label>
                    <div class="abf-form__control<?php echo 'phone' === $name ? ' abf-form__control--phone' : ''; ?>">
                        <?php if ('phone' === $name) : ?>
                            <span class="abf-form__prefix" aria-hidden="true">+44</span>
                        <?php endif; ?>
                        <input type="<?php echo esc_attr($type); ?>" id="abf-<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" autocomplete="<?php echo esc_attr($autocomplete); ?>"<?php echo $required ? ' required' : ''; ?>>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="abf-form__field abf-form__field--wide">
                <label for="abf-callback-time"><?php esc_html_e('Preferred callback time', 'abf-accounting'); ?></label>
                <div class="abf-form__control">
                    <input type="datetime-local" id="abf-callback-time" name="callback_time">
                </div>
            </div>
            <div class="abf-form__field abf-form__field--wide">
                <label for="abf-message"><?php esc_html_e('Additional information', 'abf-accounting'); ?></label>
                <div class="abf-form__control">
                    <textarea id="abf-message" name="message" rows="4" placeholder="<?php esc_attr_e('Please provide a short description of your business and any questions.', 'abf-accounting'); ?>"></textarea>
                </div>
            </div>
        </div>
        <p class="abf-form__consent"><?php esc_html_e('We use your details only to respond to your enquiry.', 'abf-accounting'); ?> <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>"><?php esc_html_e('Privacy Policy', 'abf-accounting'); ?></a></p>
        <button type="submit" class="wp-element-button abf-form__submit"><?php esc_html_e('Request a callback', 'abf-accounting'); ?></button>
    </form>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('abf_callback_form', 'abf_accounting_callback_form_shortcode');

/**
 * Handle a submission from logged-in and anonymous visitors.
 */
function abf_accounting_handle_callback_form(): void
{
    $back = isset($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : home_url('/contact/');
    $back = wp_validate_redirect($back, home_url('/contact/'));

    $nonce = isset($_POST['abf_contact_nonce']) ? sanitize_text_field(wp_unslash($_POST['abf_contact_nonce'])) : '';
    if (! wp_verify_nonce($nonce, ABF_ACCOUNTING_CONTACT_ACTION)) {
        wp_safe_redirect(add_query_arg('contact', 'failed', $back) . '#callback-form');
        exit;
    }

    // Honeypot filled in: pretend success so bots learn nothing.
    if (! empty($_POST['company_website'])) {
        wp_safe_redirect(home_url('/thank-you/'));
        exit;
    }

    $data = array(
        'first_name' => sanitize_text_field(wp_unslash($_POST['first_name'] ?? '')),
        'last_name'  => sanitize_text_field(wp_unslash($_POST['last_name'] ?? '')),
        'email'      => sanitize_email(wp_unslash($_POST['email'] ?? '')),
        'phone'      => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
        'time'       => sanitize_text_field(wp_unslash($_POST['callback_time'] ?? '')),
        'message'    => sanitize_textarea_field(wp_unslash($_POST['message'] ?? '')),
    );

    if ('' === $data['first_name'] || '' === $data['last_name'] || ! is_email($data['email']) || '' === $data['phone']) {
        wp_safe_redirect(add_query_arg('contact', 'invalid', $back) . '#callback-form');
        exit;
    }

    $to      = apply_filters('abf_accounting_contact_recipient', get_option('admin_email'));
    $subject = sprintf(
        /* translators: %s: sender's full name */
        __('Callback request from %s', 'abf-accounting'),
        $data['first_name'] . ' ' . $data['last_name']
    );
    $body    = sprintf(
        "Name: %s %s\nEmail: %s\nPhone: +44 %s\nPreferred callback time: %s\n\n%s\n",
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['phone'],
        $data['time'] ? $data['time'] : '-',
        $data['message'] ? $data['message'] : '-'
    );
    $headers = array('Reply-To: ' . $data['first_name'] . ' ' . $data['last_name'] . ' <' . $data['email'] . '>');

    if (! wp_mail($to, $subject, $body, $headers)) {
        wp_safe_redirect(add_query_arg('contact', 'failed', $back) . '#callback-form');
        exit;
    }

    wp_safe_redirect(home_url('/thank-you/'));
    exit;
}
add_action('admin_post_' . ABF_ACCOUNTING_CONTACT_ACTION, 'abf_accounting_handle_callback_form');
add_action('admin_post_nopriv_' . ABF_ACCOUNTING_CONTACT_ACTION, 'abf_accounting_handle_callback_form');
