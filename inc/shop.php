<?php
/**
 * WooCommerce: the six fixed-price packages, Buy now links, checkout fields and the shop pages
 * (Cart, Checkout, Order pay, Order received, My Account) from the Figma "Shop" and
 * "My Account" frames.
 *
 * Products are WooCommerce products whose SKU is the package key (company-accounts, llp, cic,
 * self-assessment, dormant, confirmation-statement). Cart, Checkout and My Account use the
 * classic shortcodes; the markup lives in woocommerce/ template overrides and assets/css/shop.css.
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Landing and pricing pages pass their own slug; this maps it to the package it sells.
 * Services without a fixed price (bookkeeping, VAT, payroll, tax planning) have no package.
 */
function abf_accounting_package_for(string $key): string
{
    $map = array(
        'annual-accounts-tax-return' => 'company-accounts',
        'limited-companies'          => 'company-accounts',
        'self-assessment-tax-return' => 'self-assessment',
        'sole-traders'               => 'self-assessment',
        'contractors'                => 'self-assessment',
        'self-employed-freelancers'  => 'self-assessment',
        'landlords'                  => 'self-assessment',
        'dormant-company-accounts'   => 'dormant',
    );

    return $map[$key] ?? $key;
}

/**
 * Product ID for a package key, or 0 when WooCommerce or the product is missing.
 */
function abf_accounting_package_product(string $package): int
{
    if (! function_exists('wc_get_product_id_by_sku') || '' === $package) {
        return 0;
    }
    $id = (int) wc_get_product_id_by_sku(abf_accounting_package_for($package));

    return $id && 'publish' === get_post_status($id) ? $id : 0;
}

/**
 * Cart or My Account page URL (falls back to /cart/ and /my-account/ without WooCommerce).
 *
 * @param string $page 'cart' or 'myaccount'.
 */
function abf_accounting_shop_url(string $page): string
{
    if (function_exists('wc_get_page_permalink')) {
        return 'cart' === $page ? wc_get_cart_url() : wc_get_page_permalink('myaccount');
    }

    return home_url('cart' === $page ? '/cart/' : '/my-account/');
}

/**
 * Number of packages in the basket (0 without WooCommerce or outside a request with a cart).
 */
function abf_accounting_basket_count(): int
{
    return function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

/**
 * Basket count badge. Hidden while the basket is empty; WooCommerce swaps this element for a
 * fresh copy after any cart change (see abf_accounting_basket_fragment()).
 */
function abf_accounting_basket_badge(): string
{
    $count = abf_accounting_basket_count();

    /* translators: %d: number of packages (read after the word Basket). */
    $items = sprintf(_n('%d item', '%d items', $count, 'abf-accounting'), $count);

    return '<span class="abf-basket-count"' . ($count ? '' : ' hidden') . '><span aria-hidden="true">' . esc_html((string) $count) . '</span><span class="screen-reader-text">' . esc_html($items) . '</span></span>';
}

/**
 * Header basket and My account buttons (Figma "Button / Basket", "Button / My account").
 */
function abf_accounting_header_icons(): string
{
    $basket  = '<svg viewBox="0 0 20 20" width="20" height="20" fill="none" aria-hidden="true" focusable="false"><path d="M4.167 6.667h11.666l-1 9.333a1.667 1.667 0 0 1-1.666 1.5H6.833a1.667 1.667 0 0 1-1.666-1.5l-1-9.333Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.5 8.333v-2.5a2.5 2.5 0 0 1 5 0v2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
    $account = '<svg viewBox="0 0 20 20" width="20" height="20" fill="none" aria-hidden="true" focusable="false"><path d="M13.167 7.083a3.167 3.167 0 1 1-6.334 0 3.167 3.167 0 0 1 6.334 0Z" stroke="currentColor" stroke-width="1.5"/><path d="M3.75 16.667C4.833 13.667 7.25 12.167 10 12.167s5.167 1.5 6.25 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';

    $on_cart    = function_exists('is_cart') && is_cart();
    $on_account = function_exists('is_account_page') && is_account_page();

    return '<div class="abf-header__icons">'
        . '<a class="abf-header__icon abf-header__basket" href="' . esc_url(abf_accounting_shop_url('cart')) . '"' . ($on_cart ? ' aria-current="page"' : '') . '>' . $basket . '<span class="screen-reader-text">' . esc_html__('Basket', 'abf-accounting') . '</span>' . abf_accounting_basket_badge() . '</a>'
        . '<a class="abf-header__icon abf-header__account" href="' . esc_url(abf_accounting_shop_url('myaccount')) . '"' . ($on_account ? ' aria-current="page"' : '') . ' aria-label="' . esc_attr__('My account', 'abf-accounting') . '">' . $account . '</a>'
        . '</div>';
}

if (! class_exists('WooCommerce')) {
    return;
}

/**
 * Keeps the header count right on cached pages: WooCommerce's cart-fragments script replaces
 * the badge (and the basket label) after the page loads and after every cart change.
 */
function abf_accounting_basket_fragment(array $fragments): array
{
    $fragments['span.abf-basket-count'] = abf_accounting_basket_badge();

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'abf_accounting_basket_fragment');

function abf_accounting_cart_fragments_script(): void
{
    wp_enqueue_script('wc-cart-fragments');
}
add_action('wp_enqueue_scripts', 'abf_accounting_cart_fragments_script', 30);

/* ---------- Buying a package ---------- */

/**
 * A package that is already in the cart is not added twice (packages are sold individually);
 * the customer simply lands on the checkout without an error.
 */
function abf_accounting_skip_duplicate_package(bool $passed, int $product_id): bool
{
    if ($passed && WC()->cart) {
        foreach (WC()->cart->get_cart() as $item) {
            if ((int) $item['product_id'] === $product_id) {
                return false;
            }
        }
    }

    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'abf_accounting_skip_duplicate_package', 10, 2);

/**
 * After a Buy now link adds the package, drop the ?add-to-cart= from the address bar.
 */
function abf_accounting_add_to_cart_redirect($url)
{
    $path     = static fn (string $u): string => untrailingslashit((string) wp_parse_url($u, PHP_URL_PATH));
    $request  = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
    if (isset($_REQUEST['add-to-cart']) && $path($request) === $path(wc_get_checkout_url())) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        // The checkout shows the package straight away; no 'added to your cart' banner.
        $notices = wc_get_notices();
        unset($notices['success']);
        wc_set_notices($notices);

        return wc_get_checkout_url();
    }

    return $url;
}
add_filter('woocommerce_add_to_cart_redirect', 'abf_accounting_add_to_cart_redirect');

/**
 * Order numbers read ABF-000123.
 */
function abf_accounting_order_number($number): string
{
    return 'ABF-' . str_pad((string) $number, 6, '0', STR_PAD_LEFT);
}
add_filter('woocommerce_order_number', 'abf_accounting_order_number');

/* ---------- Checkout fields (Figma "Billing details") ---------- */

/**
 * Full name, email, contact number, company (optional), street, town, postcode. The UK is
 * the only country, so its field shows no choice and is hidden by CSS.
 */
function abf_accounting_checkout_fields(array $fields): array
{
    $billing = $fields['billing'];

    // Store settings can hide the phone or company field (new installs hide company); the design
    // always shows both, so they are added back when missing.
    $billing += array(
        'billing_phone'   => array('type' => 'tel', 'validate' => array('phone'), 'autocomplete' => 'tel'),
        'billing_company' => array('type' => 'text', 'required' => false, 'autocomplete' => 'organization'),
    );

    $billing['billing_first_name'] = array_merge(
        $billing['billing_first_name'],
        array(
            'label'        => __('Full name', 'abf-accounting'),
            'placeholder'  => __('Your full name', 'abf-accounting'),
            'class'        => array('form-row-first'),
            'autocomplete' => 'name',
            'priority'     => 10,
        )
    );
    $billing['billing_email'] = array_merge(
        $billing['billing_email'],
        array(
            'label'       => __('Email address', 'abf-accounting'),
            'placeholder' => __('name@example.co.uk', 'abf-accounting'),
            'class'       => array('form-row-last'),
            'priority'    => 20,
        )
    );
    $billing['billing_phone'] = array_merge(
        $billing['billing_phone'],
        array(
            'label'       => __('Contact number', 'abf-accounting'),
            'placeholder' => __('7400 123456', 'abf-accounting'),
            'class'       => array('form-row-wide', 'abf-field--phone'),
            'required'    => true,
            'priority'    => 30,
        )
    );
    $billing['billing_company'] = array_merge(
        $billing['billing_company'],
        array(
            'label'       => __('Company name', 'abf-accounting'),
            'placeholder' => __('Your company name', 'abf-accounting'),
            'class'       => array('form-row-wide'),
            'priority'    => 40,
        )
    );
    $billing['billing_address_1'] = array_merge(
        $billing['billing_address_1'],
        array(
            'label'       => __('Street address', 'abf-accounting'),
            'placeholder' => __('House number and street name', 'abf-accounting'),
            'priority'    => 50,
        )
    );
    $billing['billing_city'] = array_merge(
        $billing['billing_city'],
        array(
            'label'       => __('Town / City', 'abf-accounting'),
            'placeholder' => __('e.g. Luton', 'abf-accounting'),
            'class'       => array('form-row-first'),
            'priority'    => 60,
        )
    );
    $billing['billing_postcode'] = array_merge(
        $billing['billing_postcode'],
        array(
            'label'       => __('Postcode', 'abf-accounting'),
            'placeholder' => __('e.g. LU3 3RP', 'abf-accounting'),
            'class'       => array('form-row-last'),
            'priority'    => 70,
        )
    );
    $billing['billing_country']['class'] = array('form-row-wide', 'abf-field--hidden');
    $billing['billing_country']['priority'] = 80;

    unset($billing['billing_last_name'], $billing['billing_address_2'], $billing['billing_state']);

    $fields['billing'] = $billing;
    unset($fields['order']['order_comments']);

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'abf_accounting_checkout_fields', 20);
add_filter('woocommerce_enable_order_notes_field', '__return_false');

/**
 * Address fields everywhere (checkout and My Account): one "Full name" field, no second
 * address line or county. Labels and order are set here too, because WooCommerce's address
 * script re-applies them from these defaults when the page loads.
 */
function abf_accounting_address_fields(array $fields): array
{
    $set = array(
        'first_name' => array(__('Full name', 'abf-accounting'), __('Your full name', 'abf-accounting'), 10),
        'company'    => array(__('Company name', 'abf-accounting'), __('Your company name', 'abf-accounting'), 40),
        'address_1'  => array(__('Street address', 'abf-accounting'), __('House number and street name', 'abf-accounting'), 50),
        'city'       => array(__('Town / City', 'abf-accounting'), __('e.g. Luton', 'abf-accounting'), 60),
        'postcode'   => array(__('Postcode', 'abf-accounting'), __('e.g. LU3 3RP', 'abf-accounting'), 70),
        'phone'      => array(__('Contact number', 'abf-accounting'), __('7400 123456', 'abf-accounting'), 30),
        'country'    => array(__('Country', 'abf-accounting'), '', 80),
    );
    foreach ($set as $key => [$label, $placeholder, $priority]) {
        if (isset($fields[$key])) {
            $fields[$key]['label']       = $label;
            $fields[$key]['placeholder'] = $placeholder;
            $fields[$key]['priority']    = $priority;
        }
    }
    $fields['first_name']['class'] = array('form-row-first');
    $fields['city']['class']       = array('form-row-first', 'address-field');
    $fields['postcode']['class']   = array('form-row-last', 'address-field');
    unset($fields['last_name'], $fields['address_2'], $fields['state']);

    return $fields;
}
add_filter('woocommerce_default_address_fields', 'abf_accounting_address_fields', 20);

/**
 * The GB locale must not add the county or relabel fields in the browser.
 */
function abf_accounting_gb_locale(array $locale): array
{
    $locale['GB'] = array(
        'state'    => array('required' => false, 'hidden' => true),
        'postcode' => array('label' => __('Postcode', 'abf-accounting')),
    );

    return $locale;
}
add_filter('woocommerce_get_country_locale', 'abf_accounting_gb_locale');

/**
 * "Full name" is stored as first and last name so receipts and Stripe get both.
 */
function abf_accounting_split_full_name(array $data): array
{
    if (isset($data['billing_first_name']) && empty($data['billing_last_name'])) {
        $parts                      = preg_split('/\s+/', trim($data['billing_first_name']), 2);
        $data['billing_first_name'] = $parts[0] ?? '';
        $data['billing_last_name']  = $parts[1] ?? '';
    }

    return $data;
}
add_filter('woocommerce_checkout_posted_data', 'abf_accounting_split_full_name');

/**
 * Prefill "Full name" with both names for signed-in customers.
 */
function abf_accounting_full_name_value($value, string $input)
{
    if ('billing_first_name' === $input && is_user_logged_in()) {
        $customer = WC()->customer;
        if ($customer) {
            return trim($customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name());
        }
    }

    return $value;
}
add_filter('woocommerce_checkout_get_value', 'abf_accounting_full_name_value', 10, 2);

/**
 * UK numbers: the field shows +44 in front, so accept 07… and 7… and store +44 7….
 */
function abf_accounting_normalise_phone(array $data): array
{
    if (! empty($data['billing_phone'])) {
        $digits = preg_replace('/[^\d+]/', '', $data['billing_phone']);
        $digits = preg_replace('/^(\+44|0044|44)/', '', $digits);
        $digits = ltrim($digits, '0');
        if ('' !== $digits) {
            $data['billing_phone'] = '+44 ' . $digits;
        }
    }

    return $data;
}
add_filter('woocommerce_checkout_posted_data', 'abf_accounting_normalise_phone', 20);

/**
 * Terms checkbox: "I agree to the Terms & Conditions for One-Off Services". No privacy blurb.
 */
function abf_accounting_terms_text(): string
{
    $page = wc_terms_and_conditions_page_id();

    return $page
        /* translators: %s: link to the terms page. */
        ? sprintf(__('I agree to the %s', 'abf-accounting'), '<a href="' . esc_url(get_permalink($page)) . '" class="woocommerce-terms-and-conditions-link" target="_blank">' . esc_html(get_the_title($page)) . '</a>')
        : '';
}
add_filter('woocommerce_get_terms_and_conditions_checkbox_text', 'abf_accounting_terms_text');
add_filter('woocommerce_get_privacy_policy_text', 'abf_accounting_privacy_text', 10, 2);

function abf_accounting_privacy_text(string $text, string $type): string
{
    if ('checkout' === $type) {
        return '';
    }

    return 'registration' === $type ? __('Your personal data will be used to manage your account and orders, as described in our [privacy_policy].', 'abf-accounting') : $text;
}

add_filter('woocommerce_order_button_text', static fn () => __('Place order', 'abf-accounting'));
add_filter('woocommerce_pay_order_button_text', static fn () => __('Pay for order', 'abf-accounting'));

/**
 * Empty cart: back to Pricing instead of the (unused) shop page.
 */
add_filter('woocommerce_return_to_shop_redirect', static fn () => home_url('/pricing/'));
add_filter('woocommerce_return_to_shop_text', static fn () => __('View pricing', 'abf-accounting'));

/**
 * The checkout terms box would load the whole terms page inline; link to it instead.
 */
remove_action('woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30);

/* ---------- My Account ---------- */

function abf_accounting_account_menu(array $items): array
{
    unset($items['downloads']);
    $labels = array(
        'dashboard'       => __('Dashboard', 'abf-accounting'),
        'orders'          => __('Orders', 'abf-accounting'),
        'edit-address'    => __('Addresses', 'abf-accounting'),
        'edit-account'    => __('Account details', 'abf-accounting'),
        'payment-methods' => __('Payment methods', 'abf-accounting'),
        'customer-logout' => __('Log out', 'abf-accounting'),
    );
    $out    = array();
    foreach ($labels as $key => $label) {
        if (isset($items[$key])) {
            $out[$key] = $label;
            unset($items[$key]);
        }
    }

    // Anything a plugin adds goes before Log out.
    $logout = $out['customer-logout'] ?? null;
    unset($out['customer-logout']);
    $out += $items;
    if ($logout) {
        $out['customer-logout'] = $logout;
    }

    return $out;
}
add_filter('woocommerce_account_menu_items', 'abf_accounting_account_menu', 20);

/**
 * Log out goes straight to the Log in page (no "Are you sure?" screen).
 */
add_filter('woocommerce_logout_default_redirect_url', static fn () => wc_get_page_permalink('myaccount'));

/**
 * Billing only: services need no shipping address.
 */
add_filter('woocommerce_my_account_get_addresses', static fn () => array('billing' => __('Billing address', 'abf-accounting')));

/**
 * Status badge on the Orders table.
 */
function abf_accounting_order_status_badge(WC_Order $order): string
{
    $status = $order->get_status();

    return '<span class="abf-badge abf-badge--' . esc_attr($status) . '">' . esc_html(wc_get_order_status_name($status)) . '</span>';
}

/* ---------- Page hero (Figma "Section / Shop / Hero" and "Section / Account / Hero") ---------- */

/**
 * Kicker, title and line for the current shop or account screen.
 *
 * @return array{0:string,1:string,2:string}
 */
function abf_accounting_shop_hero_copy(): array
{
    global $wp;

    if (is_cart()) {
        return array(__('Cart', 'abf-accounting'), __('Your cart', 'abf-accounting'), __('Check your package, then go to checkout.', 'abf-accounting'));
    }

    if (is_checkout_pay_page()) {
        return array(__('Order', 'abf-accounting'), __('Pay for your order', 'abf-accounting'), __('Your order is ready. Pay securely to confirm it.', 'abf-accounting'));
    }

    if (is_checkout()) {
        return array(__('Checkout', 'abf-accounting'), __('Checkout', 'abf-accounting'), __('Add your details and pay securely.', 'abf-accounting'));
    }

    $kicker = __('My account', 'abf-accounting');

    if (! is_user_logged_in()) {
        if (is_wc_endpoint_url('lost-password')) {
            return isset($_GET['reset-link-sent']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                ? array($kicker, __('Check your email', 'abf-accounting'), __('We’ve sent a link to choose a new password.', 'abf-accounting'))
                : array($kicker, __('Reset your password', 'abf-accounting'), __('We’ll email you a link to choose a new password.', 'abf-accounting'));
        }

        return array($kicker, __('Log in or create an account', 'abf-accounting'), __('View your orders, receipts and saved details.', 'abf-accounting'));
    }

    if (is_wc_endpoint_url('view-order')) {
        $order = wc_get_order(absint($wp->query_vars['view-order'] ?? 0));
        if ($order && (int) $order->get_customer_id() === get_current_user_id()) {
            return array(
                $kicker,
                /* translators: %s: order number. */
                sprintf(__('Order %s', 'abf-accounting'), $order->get_order_number()),
                /* translators: 1: date, 2: status. */
                sprintf(__('Placed on %1$s. Status: %2$s.', 'abf-accounting'), wc_format_datetime($order->get_date_created(), 'j F Y'), wc_get_order_status_name($order->get_status()))
            );
        }
    }

    $screens = array(
        'orders'             => array(__('Orders', 'abf-accounting'), __('All your ABF orders in one place.', 'abf-accounting')),
        'edit-address'       => array(__('Addresses', 'abf-accounting'), __('The billing address used on your receipts.', 'abf-accounting')),
        'edit-account'       => array(__('Account details', 'abf-accounting'), __('Update your name, email and password.', 'abf-accounting')),
        'payment-methods'    => array(__('Payment methods', 'abf-accounting'), __('Cards saved securely with Stripe.', 'abf-accounting')),
        'add-payment-method' => array(__('Add payment method', 'abf-accounting'), __('Cards saved securely with Stripe.', 'abf-accounting')),
    );
    foreach ($screens as $endpoint => [$title, $line]) {
        if (is_wc_endpoint_url($endpoint)) {
            return array($kicker, $title, $line);
        }
    }

    $name = wp_get_current_user()->first_name ?: wp_get_current_user()->display_name;

    /* translators: %s: first name. */
    return array($kicker, __('Dashboard', 'abf-accounting'), sprintf(__('Hello %s. Manage your orders and details here.', 'abf-accounting'), $name));
}

function abf_accounting_shop_hero(): string
{
    [$kicker, $title, $line] = abf_accounting_shop_hero_copy();

    $inner = abf_accounting_block_p('abf-shop-hero__kicker', esc_html($kicker))
        . abf_accounting_block_h(1, 'abf-shop-hero__title', esc_html($title))
        . abf_accounting_block_p('abf-shop-hero__text', esc_html($line));

    return abf_accounting_block_cover('abf-shop-hero', get_theme_file_uri('assets/img/status-office-backdrop.webp'), abf_accounting_block_group('abf-container abf-shop-hero__content', $inner), 305);
}

/**
 * Trustpilot line under the cart and order summaries.
 */
function abf_accounting_shop_trust(): string
{
    return '<div class="abf-shop-trust"><span class="abf-shop-trust__stars" aria-hidden="true"></span><span class="abf-shop-trust__rating">' . esc_html__('4.7 Trustpilot', 'abf-accounting') . '</span><span class="abf-shop-trust__note">' . esc_html__('Rated by ABF customers', 'abf-accounting') . '</span></div>';
}

/* ---------- Order received (Figma "Shop / Order Received") ---------- */

/**
 * Full-screen confirmation with the order reference, package and amount paid. The order key
 * in the link must match, as on WooCommerce's own thank-you page.
 */
function abf_accounting_order_received(): string
{
    global $wp;

    $order_id = absint($wp->query_vars['order-received'] ?? 0);
    $key      = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $order    = $order_id ? wc_get_order($order_id) : false;

    if (! $order || ! hash_equals($order->get_order_key(), $key)) {
        return abf_accounting_status_page(
            __('Order', 'abf-accounting'),
            __('We could not find that order.', 'abf-accounting'),
            __('If you have just paid, check your email for the receipt, or get in touch and we will help.', 'abf-accounting'),
            array(__('Contact us', 'abf-accounting'), home_url('/contact/'))
        );
    }

    // Let the payment gateway finish its work; its own thank-you text is not shown.
    ob_start();
    do_action('woocommerce_before_thankyou', $order->get_id());
    do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id());
    do_action('woocommerce_thankyou', $order->get_id());
    ob_end_clean();

    $failed = $order->has_status('failed');
    $names  = array();
    foreach ($order->get_items() as $item) {
        $names[] = $item->get_name();
    }

    $rows = array(
        array(__('Order reference', 'abf-accounting'), $order->get_order_number()),
        array(_n('Package', 'Packages', count($names), 'abf-accounting'), implode(', ', $names)),
        array($order->is_paid() ? __('Amount paid', 'abf-accounting') : __('Amount', 'abf-accounting'), wp_strip_all_tags(wc_price($order->get_total(), array('currency' => $order->get_currency())))),
    );
    $list = '';
    foreach ($rows as [$label, $value]) {
        $list .= '<div class="abf-receipt__row"><dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd></div>';
    }

    if ($failed) {
        $kicker = __('Payment not taken', 'abf-accounting');
        $title  = __('Your payment did not go through.', 'abf-accounting');
        $text   = __('No money has been taken. You can try again with another card, or contact us and we will help.', 'abf-accounting');
        $first  = array(__('Try again', 'abf-accounting'), $order->get_checkout_payment_url(), 'abf-button--lg');
    } else {
        $kicker = __('Order confirmed', 'abf-accounting');
        $title  = __('Thank you. Your order is confirmed.', 'abf-accounting');
        $text   = __('We’ve emailed your receipt and what happens next.', 'abf-accounting');
        $first  = array(__('Back to homepage', 'abf-accounting'), home_url('/'), 'abf-button--lg');
    }

    $inner = abf_accounting_block_group(
        'abf-container abf-status__content',
        abf_accounting_block_p('abf-status__kicker', esc_html($kicker))
        . abf_accounting_block_h(1, 'abf-status__title', esc_html($title))
        . abf_accounting_block_p('abf-status__text', esc_html($text))
        . abf_accounting_block_html('<dl class="abf-receipt">' . $list . '</dl>')
        . abf_accounting_block_buttons(
            'abf-status__actions',
            array(
                $first,
                array(__('Contact us', 'abf-accounting'), home_url('/contact/'), 'abf-button--lg abf-button--ghost'),
            )
        )
    );

    return abf_accounting_block_cover('abf-status abf-status--order', get_theme_file_uri('assets/img/status-office-backdrop.webp'), $inner, 988);
}

/**
 * The order-received screen uses the theme's "order-confirmation" template (header, no footer).
 */
function abf_accounting_order_received_template(array $templates): array
{
    if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
        array_unshift($templates, 'order-confirmation');
    }

    return $templates;
}
add_filter('page_template_hierarchy', 'abf_accounting_order_received_template');

/**
 * Shop pages use the theme stylesheet only; WooCommerce's own layout styles are dropped.
 */
function abf_accounting_shop_styles(array $styles): array
{
    unset($styles['woocommerce-layout'], $styles['woocommerce-smallscreen'], $styles['woocommerce-general']);

    return $styles;
}
add_filter('woocommerce_enqueue_styles', 'abf_accounting_shop_styles');

function abf_accounting_shop_assets(): void
{
    // WooCommerce's block-theme sheet caps the pages at 1000px and tables at 70%.
    wp_dequeue_style('woocommerce-blocktheme');
    wp_enqueue_style('abf-accounting-shop', get_theme_file_uri('assets/css/shop.css'), array('abf-accounting-frontend'), wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'abf_accounting_shop_assets', 20);

/* ---------- Small template hooks ---------- */

/**
 * "Secure payments by Stripe" above the terms checkbox (checkout and order pay).
 */
function abf_accounting_secure_note(): void
{
    echo '<p class="abf-secure-note">' . esc_html__('Secure payments by Stripe', 'abf-accounting') . '</p>';
}
add_action('woocommerce_checkout_before_terms_and_conditions', 'abf_accounting_secure_note');

/**
 * Orders table: Order, Date, Status, Total and an unlabelled actions column.
 */
function abf_accounting_orders_columns(array $columns): array
{
    $labels = array(
        'order-number'  => __('Order', 'abf-accounting'),
        'order-date'    => __('Date', 'abf-accounting'),
        'order-status'  => __('Status', 'abf-accounting'),
        'order-total'   => __('Total', 'abf-accounting'),
        'order-actions' => '',
    );

    return array_intersect_key($labels, $columns) + $columns;
}
add_filter('woocommerce_account_orders_columns', 'abf_accounting_orders_columns');

add_action(
    'woocommerce_my_account_my_orders_column_order-number',
    static function (WC_Order $order): void {
        echo '<a href="' . esc_url($order->get_view_order_url()) . '">' . esc_html($order->get_order_number()) . '</a>';
    }
);
add_action(
    'woocommerce_my_account_my_orders_column_order-date',
    static function (WC_Order $order): void {
        echo '<time datetime="' . esc_attr($order->get_date_created()->date('c')) . '">' . esc_html(wc_format_datetime($order->get_date_created(), 'j F Y')) . '</time>';
    }
);
add_action(
    'woocommerce_my_account_my_orders_column_order-status',
    static function (WC_Order $order): void {
        echo abf_accounting_order_status_badge($order); // phpcs:ignore WordPress.Security.EscapeOutput
    }
);

/**
 * An unpaid order shows only Pay; others show View.
 */
function abf_accounting_order_actions(array $actions): array
{
    unset($actions['cancel']);
    if (isset($actions['pay'])) {
        unset($actions['view']);
    }

    return $actions;
}
add_filter('woocommerce_my_account_my_orders_actions', 'abf_accounting_order_actions');

/**
 * View order: the hero already says when it was placed and its status.
 */
add_filter('woocommerce_order_details_status', '__return_empty_string');

add_action(
    'woocommerce_view_order',
    static function (): void {
        echo '<a class="abf-back-link" href="' . esc_url(wc_get_account_endpoint_url('orders')) . '">' . esc_html__('Back to orders', 'abf-accounting') . '</a>';
    },
    30
);

add_action(
    'woocommerce_after_lost_password_form',
    static function (): void {
        echo '<a class="abf-back-link" href="' . esc_url(wc_get_page_permalink('myaccount')) . '">' . esc_html__('Back to log in', 'abf-accounting') . '</a>';
    }
);

add_filter('woocommerce_my_account_my_address_description', static fn () => __('This address is used on your receipts and invoices.', 'abf-accounting'));

/**
 * Dates read "2 November 2026" across WooCommerce screens.
 */
add_filter('woocommerce_date_format', static fn () => 'j F Y');

/**
 * Account details wording from Figma ("Change password", short labels).
 */
function abf_accounting_account_strings(string $translation, string $text, string $domain): string
{
    if ('woocommerce' !== $domain) {
        return $translation;
    }
    $map = array(
        'Password change'                                => __('Change password', 'abf-accounting'),
        'Current password (leave blank to leave unchanged)' => __('Current password', 'abf-accounting'),
        'New password (leave blank to leave unchanged)'  => __('New password', 'abf-accounting'),
    );

    return $map[$text] ?? $translation;
}
add_filter('gettext', 'abf_accounting_account_strings', 10, 3);

/**
 * Empty cart: there is no shop page, so offer Pricing instead.
 */
add_action(
    'woocommerce_cart_is_empty',
    static function (): void {
        // WooCommerce prints its own (already relabelled) button when a shop page is set.
        if (wc_get_page_id('shop') > 0) {
            return;
        }
        echo '<p class="abf-empty-cart"><a class="button" href="' . esc_url(home_url('/pricing/')) . '">' . esc_html__('View pricing', 'abf-accounting') . '</a></p>';
    },
    20
);

/* ---------- Order emails in ABF colours ---------- */

/**
 * WooCommerce email design (WooCommerce → Settings → Emails) set from the theme, so staging
 * and live get the same look: ABF logo, green buttons and links, mint page, ink text, and the
 * company details in the footer.
 */
function abf_accounting_email_design(): array
{
    $contact = abf_accounting_contact();

    return array(
        'woocommerce_email_auto_sync_with_theme'  => 'no',
        'woocommerce_email_header_image'          => get_theme_file_uri('assets/img/abf-logo-email.png'),
        'woocommerce_email_header_image_width'    => 187,
        'woocommerce_email_header_alignment'      => 'left',
        'woocommerce_email_base_color'            => '#03A84E',
        'woocommerce_email_background_color'      => '#F3FBF5',
        'woocommerce_email_body_background_color' => '#FFFFFF',
        'woocommerce_email_text_color'            => '#09242D',
        'woocommerce_email_footer_text_color'     => '#64748B',
        'woocommerce_email_footer_text'           => sprintf(
            /* translators: 1: phone, 2: email, 3: address, 4: company number. */
            __('Questions? Call %1$s or email %2$s.<br>ABF Accounting Services Ltd · %3$s · Company no. %4$s', 'abf-accounting'),
            $contact['phone'],
            $contact['email'],
            implode(', ', $contact['address']),
            $contact['company_no']
        ),
    );
}

/**
 * Serves the design values in place of the saved WooCommerce options.
 *
 * @param mixed  $value  Pre-option value (false = not short-circuited).
 * @param string $option Option name.
 */
function abf_accounting_email_option($value, string $option)
{
    return abf_accounting_email_design()[$option] ?? $value;
}

foreach (array('woocommerce_email_auto_sync_with_theme', 'woocommerce_email_header_image', 'woocommerce_email_header_image_width', 'woocommerce_email_header_alignment', 'woocommerce_email_base_color', 'woocommerce_email_background_color', 'woocommerce_email_body_background_color', 'woocommerce_email_text_color', 'woocommerce_email_footer_text_color', 'woocommerce_email_footer_text') as $abf_accounting_option) {
    add_filter('pre_option_' . $abf_accounting_option, 'abf_accounting_email_option', 10, 2);
}
unset($abf_accounting_option);

add_filter('woocommerce_email_from_name', static fn () => 'ABF Accounting');

/**
 * Manrope is not available in most mail apps; keep a clean sans-serif and ABF's rounded buttons.
 */
function abf_accounting_email_styles(string $css): string
{
    return $css . '
        body, table, td, p, h1, h2, h3, a, #template_container, #body_content_inner { font-family: "Manrope", "Segoe UI", Helvetica, Arial, sans-serif !important; }
        #template_header { background-color: #0D333F !important; border-radius: 0 !important; }
        #template_header h1 { color: #FFFFFF !important; font-weight: 800 !important; letter-spacing: -0.02em; }
        #template_container { border: 1px solid #DEEBE2 !important; border-radius: 16px !important; overflow: hidden; box-shadow: none !important; }
        #template_header_image { padding: 24px 0 8px !important; }
        h2 { color: #09242D !important; font-weight: 700 !important; }
        a { color: #03A84E !important; }
        #template_header_image img { width: 187px !important; max-width: 187px !important; height: auto !important; }
        .td { border-color: #DEEBE2 !important; }
    ';
}
add_filter('woocommerce_email_styles', 'abf_accounting_email_styles', 20);

/**
 * WooCommerce adds a mini-cart and an account icon to block-theme headers automatically.
 * The ABF header (Figma) has neither, so those hooked blocks are switched off.
 */
function abf_accounting_no_hooked_shop_blocks(array $hooked_blocks): array
{
    return array_values(array_diff($hooked_blocks, array('woocommerce/mini-cart', 'woocommerce/customer-account')));
}
add_filter('hooked_block_types', 'abf_accounting_no_hooked_shop_blocks', 20);
