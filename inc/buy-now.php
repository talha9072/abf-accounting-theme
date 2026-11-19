<?php
/**
 * Buy now pop-up (Figma "Buy Now / Company" and "Buy Now / Self Assessment").
 *
 * Every Buy now / Get started / File now button opens a step-by-step pop-up instead of the
 * cart: find the company on Companies House → your details → a 4-digit code by email and
 * text → order summary → Pay by bank with GoCardless (hosted page). The order is created when
 * the customer presses "Continue to your bank"; GoCardless webhooks mark it paid.
 *
 * Server side: REST routes under /wp-json/abf/v1/, the "Pay by bank" WooCommerce gateway
 * (used for Order Pay retries too), the GoCardless return and webhook, and the company details
 * on orders and emails. Keys live in Appearance → ABF settings → Buy now pop-up.
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

const ABF_BUY_CODE_TTL      = 10 * MINUTE_IN_SECONDS; // a code works for 10 minutes
const ABF_BUY_RESEND_WAIT   = 60;                     // seconds before "Resend code" works
const ABF_BUY_MAX_RESENDS   = 3;
const ABF_BUY_MAX_ATTEMPTS  = 5;                      // wrong codes before starting again
const ABF_BUY_VERIFIED_TTL  = HOUR_IN_SECONDS;        // time to finish the order after verifying
const ABF_BUY_GATEWAY       = 'abf_gocardless';

/* ================================================================== */
/* Services                                                            */
/* ================================================================== */

/**
 * The packages sold online, in the pop-up's order. "company" services start with the
 * Companies House search; Self Assessment and the Partnership Tax Return go straight to
 * Your details (ordinary partnerships aren't on Companies House).
 *
 * types / subtype: which Companies House companies can be picked for the service — a company
 * fits when its type is in `types` OR it has the `subtype` (Charity & CIC uses both).
 * listed: shown in the pop-up's Service list. Charity & CIC replaced the CIC card on Pricing
 * (client, 19 Nov 2026), so the old CIC-only package is kept for existing orders but no
 * longer offered in the list.
 */
function abf_buy_services(): array
{
    $guarantee = array('private-limited-guarant-nsc', 'private-limited-guarant-nsc-limited-exemption');
    $limited   = array_merge(array('ltd'), $guarantee);
    $cic       = 'community-interest-company';

    $services = array(
        'company-accounts'       => array('flow' => 'company', 'types' => $limited, 'short' => __('Company Accounts & CT', 'abf-accounting'), 'summary' => __('year-end accounts and CT600, filed with Companies House and HMRC', 'abf-accounting')),
        'llp'                    => array('flow' => 'company', 'types' => array('llp'), 'short' => __('LLP', 'abf-accounting'), 'summary' => __('LLP accounts and the SA800 partnership tax return', 'abf-accounting')),
        'cic'                    => array('flow' => 'company', 'subtype' => $cic, 'listed' => false, 'short' => __('CIC', 'abf-accounting'), 'summary' => __('annual accounts and CT600 filing, with support on CIC reporting', 'abf-accounting')),
        'charity-cic'            => array('flow' => 'company', 'types' => $guarantee, 'subtype' => $cic, 'short' => __('Charity & CIC', 'abf-accounting'), 'summary' => __('year-end accounts and CT600, plus the CIC34 report for CICs', 'abf-accounting')),
        'self-assessment'        => array('flow' => 'personal', 'short' => __('Self Assessment', 'abf-accounting'), 'summary' => __('SA100 return prepared and submitted to HMRC', 'abf-accounting')),
        'partnership-tax-return' => array('flow' => 'personal', 'short' => __('Partnership Tax Return', 'abf-accounting'), 'summary' => __('SA800 partnership tax return prepared and submitted to HMRC', 'abf-accounting')),
        'dormant'                => array('flow' => 'company', 'types' => $limited, 'short' => __('Dormant', 'abf-accounting'), 'summary' => __('dormant accounts prepared and filed with Companies House', 'abf-accounting')),
        'confirmation-statement' => array('flow' => 'company', 'types' => $limited, 'short' => __('Confirmation Statement', 'abf-accounting'), 'summary' => __('CS01 prepared and filed with Companies House', 'abf-accounting')),
    );

    return array_map(static fn (array $s): array => $s + array('types' => array(), 'subtype' => '', 'listed' => true), $services);
}

/**
 * Packages added after launch, created once (by SKU) so a theme upload is enough. Same
 * settings as the original six: virtual, one per order, no VAT, hidden from the catalogue.
 * Never touches a product that already exists; deleting one in WooCommerce keeps it deleted.
 */
function abf_buy_ensure_products(): void
{
    $products = array(
        'charity-cic'            => array(__('Charity & CIC accounts', 'abf-accounting'), 299, 6),
        'partnership-tax-return' => array(__('Partnership Tax Return (SA800)', 'abf-accounting'), 299, 7),
    );
    $done = (array) get_option('abf_buy_products', array());
    $todo = array_diff(array_keys($products), $done);
    if (! $todo || ! class_exists('WC_Product_Simple')) {
        return;
    }
    foreach ($todo as $sku) {
        if (! wc_get_product_id_by_sku($sku)) {
            [$name, $price, $order] = $products[$sku];
            $product = new WC_Product_Simple();
            $product->set_name($name);
            $product->set_sku($sku);
            $product->set_regular_price((string) $price);
            $product->set_virtual(true);
            $product->set_sold_individually(true);
            $product->set_tax_status('none');
            $product->set_catalog_visibility('hidden');
            $product->set_status('publish');
            $product->set_menu_order($order);
            $product->save();
        }
        $done[] = $sku;
    }
    update_option('abf_buy_products', array_values(array_unique($done)), false);
}
add_action('init', 'abf_buy_ensure_products', 20);

/**
 * Service details for the pop-up (name and price straight from WooCommerce), or null.
 */
function abf_buy_service(string $key): ?array
{
    $services = abf_buy_services();
    if (! isset($services[$key]) || ! function_exists('wc_get_product')) {
        return null;
    }
    $product_id = abf_accounting_package_product($key);
    $product    = $product_id ? wc_get_product($product_id) : null;
    if (! $product || ! $product->is_purchasable()) {
        return null;
    }

    return $services[$key] + array(
        'key'     => $key,
        'product' => $product,
        'name'    => html_entity_decode($product->get_name(), ENT_QUOTES, 'UTF-8'),
        'price'   => (float) $product->get_price(),
    );
}

/** "£299" / "£299.50". */
function abf_buy_money(float $amount, bool $pence = false): string
{
    return '£' . number_format($amount, ($pence || floor($amount) !== $amount) ? 2 : 0);
}

/* ================================================================== */
/* Companies House (server side; the key never reaches the browser)    */
/* ================================================================== */

function abf_buy_ch_get(string $path, array $query = array())
{
    $key = abf_accounting_setting('ch_api_key');
    if ('' === $key) {
        return new WP_Error('abf_ch_off', __('Company search is not set up yet. Please contact us and we will help.', 'abf-accounting'));
    }
    $url      = 'https://api.company-information.service.gov.uk' . $path . ($query ? '?' . http_build_query($query) : '');
    $response = wp_remote_get(
        $url,
        array(
            'timeout' => 12,
            'headers' => array('Authorization' => 'Basic ' . base64_encode($key . ':')), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        )
    );
    if (is_wp_error($response)) {
        return new WP_Error('abf_ch_down', __('We could not reach Companies House. Please try again in a moment.', 'abf-accounting'));
    }
    $code = wp_remote_retrieve_response_code($response);
    if (404 === $code) {
        return array();
    }
    if (200 !== $code) {
        return new WP_Error('abf_ch_error', __('Companies House did not answer. Please try again in a moment.', 'abf-accounting'));
    }

    return (array) json_decode((string) wp_remote_retrieve_body($response), true);
}

/** "12 Example Street, London EC1A 1AA" from a Companies House address. */
function abf_buy_ch_address(array $a): string
{
    $street = trim(implode(' ', array_filter(array($a['premises'] ?? '', $a['address_line_1'] ?? ''))));
    $parts  = array_filter(array($street, $a['address_line_2'] ?? '', trim(($a['locality'] ?? '') . ' ' . ($a['postal_code'] ?? ''))));

    return implode(', ', array_map('trim', $parts));
}

/** Subtype strings from a Companies House field that may be a string, a list, or a list of objects. */
function abf_buy_subtypes($value): array
{
    $out = array();
    foreach ((array) $value as $v) {
        $out[] = is_array($v) ? (string) ($v['type'] ?? '') : (string) $v;
    }

    return array_values(array_filter($out));
}

/** Active, and the right kind of company for the service: its type is listed, or it has the subtype (CIC). */
function abf_buy_fits(string $status, string $type, array $subtypes, array $service): bool
{
    if ('active' !== $status) {
        return false;
    }

    return in_array($type, $service['types'], true)
        || ('' !== $service['subtype'] && in_array($service['subtype'], $subtypes, true));
}

/**
 * One company's profile, cached for an hour: name, number, address, status, type.
 *
 * @return array|WP_Error|null Null when the number doesn't exist.
 */
function abf_buy_company(string $number)
{
    $number = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $number));
    if (! preg_match('/^[A-Z0-9]{8}$/', $number)) {
        return null;
    }
    $cached = get_transient('abf_ch_co_' . $number);
    if (is_array($cached)) {
        return $cached;
    }
    $c = abf_buy_ch_get('/company/' . rawurlencode($number));
    if (is_wp_error($c)) {
        return $c;
    }
    if (! $c || empty($c['company_name'])) {
        return null;
    }
    $profile = array(
        'name'     => $c['company_name'],
        'number'   => $c['company_number'],
        'address'  => abf_buy_ch_address((array) ($c['registered_office_address'] ?? array())),
        'raw'      => (array) ($c['registered_office_address'] ?? array()),
        'status'   => (string) ($c['company_status'] ?? ''),
        'type'     => (string) ($c['type'] ?? ''),
        'subtypes' => abf_buy_subtypes(array_merge((array) ($c['subtype'] ?? array()), (array) ($c['company_subtype'] ?? array()))),
    );
    set_transient('abf_ch_co_' . $number, $profile, HOUR_IN_SECONDS);

    return $profile;
}

/** The profile fits the service (active, type or CIC subtype). */
function abf_buy_profile_fits(array $profile, array $service): bool
{
    return abf_buy_fits($profile['status'], $profile['type'], $profile['subtypes'], $service);
}

/* ================================================================== */
/* Small guards                                                        */
/* ================================================================== */

/** Per-IP limit: true while under $max hits in $window seconds. */
function abf_buy_rate_ok(string $bucket, int $max, int $window): bool
{
    $ip  = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'x';
    $key = 'abf_rl_' . md5($bucket . '|' . $ip);
    $n   = (int) get_transient($key);
    if ($n >= $max) {
        return false;
    }
    set_transient($key, $n + 1, $window);

    return true;
}

/** UK mobile → +447XXXXXXXXX, or '' when it isn't one. */
function abf_buy_uk_mobile(string $raw): string
{
    $digits = preg_replace('/\D/', '', $raw);
    if (str_starts_with($digits, '0044')) {
        $digits = substr($digits, 4);
    } elseif (str_starts_with($digits, '44')) {
        $digits = substr($digits, 2);
    }
    if (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }

    return preg_match('/^7\d{9}$/', $digits) ? '+44' . $digits : '';
}

/** "+447400123456" → "07400 123456". */
function abf_buy_pretty_mobile(string $e164): string
{
    $n = '0' . substr($e164, 3);

    return substr($n, 0, 5) . ' ' . substr($n, 5);
}

/** [first, last] from a full name. */
function abf_buy_split_name(string $full): array
{
    $parts = preg_split('/\s+/', trim($full)) ?: array('');
    $first = array_shift($parts);

    return array($first, implode(' ', $parts));
}

/* ================================================================== */
/* Verification codes                                                  */
/* ================================================================== */

function abf_buy_session_key(string $token): string
{
    return 'abf_buy_' . hash('sha256', $token);
}

function abf_buy_session(string $token): ?array
{
    if (! preg_match('/^[A-Za-z0-9]{32}$/', $token)) {
        return null;
    }
    $s = get_transient(abf_buy_session_key($token));

    return is_array($s) ? $s : null;
}

function abf_buy_save_session(string $token, array $s, int $ttl): void
{
    set_transient(abf_buy_session_key($token), $s, $ttl);
}

/** Text messages are optional: on only when a Twilio account is saved in ABF settings. */
function abf_buy_sms_ready(): bool
{
    return '' !== abf_accounting_setting('twilio_sid') && '' !== abf_accounting_setting('twilio_token') && '' !== abf_accounting_setting('twilio_from');
}

/** Emails the code, and texts it too when SMS is set up. Returns which channels went out. */
function abf_buy_send_code(array $s, string $code): array
{
    $sent = array('email' => false, 'sms' => false);

    /* translators: %s: 4-digit code. */
    $subject = sprintf(__('Your ABF Accounting code: %s', 'abf-accounting'), $code);
    $body    = '<p>' . esc_html(sprintf(/* translators: %s: first name. */ __('Hi %s,', 'abf-accounting'), abf_buy_split_name($s['name'])[0])) . '</p>'
        . '<p>' . esc_html__('Your verification code is:', 'abf-accounting') . '</p>'
        . '<p style="font-size:32px;font-weight:700;letter-spacing:8px;margin:16px 0">' . esc_html($code) . '</p>'
        . '<p>' . esc_html__('It works for 10 minutes. If you didn’t ask for this, you can ignore this email.', 'abf-accounting') . '</p>';
    if (function_exists('WC') && WC()->mailer()) {
        $mailer = WC()->mailer();
        $sent['email'] = (bool) $mailer->send($s['email'], $subject, $mailer->wrap_message(__('Your verification code', 'abf-accounting'), $body), array('Content-Type: text/html; charset=UTF-8'));
    } else {
        $sent['email'] = wp_mail($s['email'], $subject, wp_strip_all_tags(str_replace('</p>', "\n\n", $body)));
    }

    if (abf_buy_sms_ready()) {
        $sid   = abf_accounting_setting('twilio_sid');
        $token = abf_accounting_setting('twilio_token');
        $from  = abf_accounting_setting('twilio_from');
        $response = wp_remote_post(
            'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json',
            array(
                'timeout' => 12,
                'headers' => array('Authorization' => 'Basic ' . base64_encode($sid . ':' . $token)), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
                'body'    => array(
                    'To'   => $s['phone'],
                    'From' => $from,
                    /* translators: %s: 4-digit code. */
                    'Body' => sprintf(__('Your ABF Accounting code is %s. It works for 10 minutes.', 'abf-accounting'), $code),
                ),
            )
        );
        $sent['sms'] = ! is_wp_error($response) && in_array(wp_remote_retrieve_response_code($response), array(200, 201), true);
        if (! $sent['sms']) {
            error_log('[abf-buy] Twilio text failed: ' . (is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response))); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    return $sent;
}

/* ================================================================== */
/* REST routes                                                         */
/* ================================================================== */

function abf_buy_error(string $message, int $status = 400, array $data = array()): WP_Error
{
    return new WP_Error('abf_buy', $message, array('status' => $status) + $data);
}

function abf_buy_register_routes(): void
{
    $public = array('permission_callback' => '__return_true');
    register_rest_route('abf/v1', '/companies', $public + array('methods' => 'GET', 'callback' => 'abf_buy_rest_companies'));
    register_rest_route('abf/v1', '/code', $public + array('methods' => 'POST', 'callback' => 'abf_buy_rest_code'));
    register_rest_route('abf/v1', '/code/resend', $public + array('methods' => 'POST', 'callback' => 'abf_buy_rest_resend'));
    register_rest_route('abf/v1', '/code/check', $public + array('methods' => 'POST', 'callback' => 'abf_buy_rest_check'));
    register_rest_route('abf/v1', '/summary', $public + array('methods' => 'POST', 'callback' => 'abf_buy_rest_summary'));
    register_rest_route('abf/v1', '/order', $public + array('methods' => 'POST', 'callback' => 'abf_buy_rest_order'));
    register_rest_route('abf/v1', '/order/status', $public + array('methods' => 'POST', 'callback' => 'abf_buy_rest_order_status'));
    register_rest_route('abf/v1', '/order/retry', $public + array('methods' => 'POST', 'callback' => 'abf_buy_rest_retry'));
    register_rest_route('abf/v1', '/gocardless/webhook', $public + array('methods' => 'POST', 'callback' => 'abf_buy_gc_webhook'));
}
add_action('rest_api_init', 'abf_buy_register_routes');

/** GET /companies?q=&service= — active companies that fit the service. */
function abf_buy_rest_companies(WP_REST_Request $r)
{
    $service = abf_buy_service((string) $r->get_param('service'));
    $q       = trim(sanitize_text_field((string) $r->get_param('q')));
    if (! $service || 'company' !== $service['flow']) {
        return abf_buy_error(__('Choose a company service first.', 'abf-accounting'));
    }
    if (mb_strlen($q) < 2 || mb_strlen($q) > 120) {
        return abf_buy_error(__('Type at least 2 letters of your company name, or its number.', 'abf-accounting'));
    }
    if (! abf_buy_rate_ok('search', 60, MINUTE_IN_SECONDS)) {
        return abf_buy_error(__('Too many searches. Please wait a minute and try again.', 'abf-accounting'), 429);
    }

    $results = array();
    $compact = strtoupper(preg_replace('/\s+/', '', $q));
    if (preg_match('/^(\d{1,8}|[A-Z]{2}\d{6})$/', $compact)) {
        // A company number (leading zeros optional).
        $number  = ctype_digit($compact) ? str_pad($compact, 8, '0', STR_PAD_LEFT) : $compact;
        $profile = abf_buy_company($number);
        if (is_wp_error($profile)) {
            return abf_buy_error($profile->get_error_message(), 502);
        }
        if ($profile && abf_buy_profile_fits($profile, $service)) {
            $results[] = array('name' => $profile['name'], 'number' => $profile['number']);
        }
    }

    if (! $results) {
        // One search per filter (Companies House ANDs type and subtype): by type, and by
        // subtype — Charity & CIC runs both and merges them.
        $base    = array(
            'company_name_includes' => $q,
            'company_status'        => 'active',
            'size'                  => 20,
        );
        $queries = array();
        if ($service['types']) {
            $queries[] = $base + array('company_type' => implode(',', $service['types']));
        }
        if ($service['subtype']) {
            $queries[] = $base + array('company_subtype' => $service['subtype']);
        }
        $seen = array();
        foreach ($queries as $query) {
            $data = abf_buy_ch_get('/advanced-search/companies', $query);
            if (is_wp_error($data)) {
                return abf_buy_error($data->get_error_message(), 502);
            }
            foreach ((array) ($data['items'] ?? array()) as $item) {
                $number = (string) ($item['company_number'] ?? '');
                if (isset($seen[$number])) {
                    continue;
                }
                if (abf_buy_fits((string) ($item['company_status'] ?? ''), (string) ($item['company_type'] ?? ''), abf_buy_subtypes($item['company_subtype'] ?? ($query['company_subtype'] ?? array())), $service)) {
                    $seen[$number] = true;
                    $results[]     = array('name' => $item['company_name'], 'number' => $number);
                }
            }
        }
        // Closest names first: exact, then starts-with, then the rest (Companies House order).
        $needle = strtoupper($q);
        usort(
            $results,
            static function ($a, $b) use ($needle): int {
                $rank = static fn ($n): int => strtoupper($n) === $needle ? 0 : (str_starts_with(strtoupper($n), $needle) ? 1 : 2);

                return $rank($a['name']) <=> $rank($b['name']);
            }
        );
    }

    return array('results' => array_slice($results, 0, 20));
}

/** Customer details from a request, validated. */
function abf_buy_details(WP_REST_Request $r)
{
    $service = abf_buy_service((string) $r->get_param('service'));
    $name    = trim(preg_replace('/\s+/', ' ', sanitize_text_field((string) $r->get_param('name'))));
    $email   = sanitize_email((string) $r->get_param('email'));
    $phone   = abf_buy_uk_mobile((string) $r->get_param('phone'));
    $errors  = array();

    if (! $service) {
        return abf_buy_error(__('Please choose a service.', 'abf-accounting'));
    }
    if (mb_strlen($name) < 2 || ! str_contains($name, ' ') || mb_strlen($name) > 80) {
        $errors['name'] = __('Please enter your first and last name.', 'abf-accounting');
    }
    if (! is_email($email)) {
        $errors['email'] = __('Please enter a valid email address.', 'abf-accounting');
    }
    if ('' === $phone) {
        $errors['phone'] = __('Please enter a UK mobile number.', 'abf-accounting');
    }

    $company = null;
    if ('company' === $service['flow']) {
        $company = abf_buy_company((string) $r->get_param('company'));
        if (is_wp_error($company)) {
            return abf_buy_error($company->get_error_message(), 502);
        }
        if (! $company || ! abf_buy_profile_fits($company, $service)) {
            return abf_buy_error(__('Please find and select your company again.', 'abf-accounting'), 400, array('step' => 'find'));
        }
    }
    if ($errors) {
        return abf_buy_error(__('Please check the highlighted details.', 'abf-accounting'), 400, array('fields' => $errors));
    }

    return array(
        'service' => $service['key'],
        'name'    => $name,
        'email'   => strtolower($email),
        'phone'   => $phone,
        'company' => $company ? $company['number'] : '',
    );
}

/** POST /code — check the details and send a code (logged-in customers skip it). */
function abf_buy_rest_code(WP_REST_Request $r)
{
    $details = abf_buy_details($r);
    if (is_wp_error($details)) {
        return $details;
    }
    $token = wp_generate_password(32, false, false);

    if (is_user_logged_in()) {
        abf_buy_save_session($token, $details + array('verified' => true, 'user' => get_current_user_id()), ABF_BUY_VERIFIED_TTL);

        return array('token' => $token, 'verified' => true);
    }
    if (! abf_buy_rate_ok('code', 8, HOUR_IN_SECONDS)) {
        return abf_buy_error(__('Too many codes requested. Please try again in an hour, or call us.', 'abf-accounting'), 429);
    }

    $code    = str_pad((string) wp_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $session = $details + array(
        'verified' => false,
        'code'     => wp_hash_password($code),
        'expires'  => time() + ABF_BUY_CODE_TTL,
        'sent_at'  => time(),
        'resends'  => 0,
        'attempts' => 0,
    );
    $sent = abf_buy_send_code($session, $code);
    if (! $sent['email'] && ! $sent['sms']) {
        return abf_buy_error(__('We couldn’t send your code. Please check your details or call us on 07405 141694.', 'abf-accounting'), 502);
    }
    abf_buy_save_session($token, $session, ABF_BUY_CODE_TTL + 5 * MINUTE_IN_SECONDS);

    return array('token' => $token, 'verified' => false, 'sent' => $sent, 'resendIn' => ABF_BUY_RESEND_WAIT, 'resendsLeft' => ABF_BUY_MAX_RESENDS);
}

/** POST /code/resend — a new code (60 s apart, 3 times at most). */
function abf_buy_rest_resend(WP_REST_Request $r)
{
    $token = (string) $r->get_param('token');
    $s     = abf_buy_session($token);
    if (! $s || $s['verified']) {
        return abf_buy_error(__('Your code has expired. Please go back and start again.', 'abf-accounting'), 410, array('restart' => true));
    }
    $wait = $s['sent_at'] + ABF_BUY_RESEND_WAIT - time();
    if ($wait > 0) {
        /* translators: %d: seconds. */
        return abf_buy_error(sprintf(__('You can ask for a new code in %d seconds.', 'abf-accounting'), $wait), 429, array('resendIn' => $wait));
    }
    if ($s['resends'] >= ABF_BUY_MAX_RESENDS) {
        return abf_buy_error(__('You’ve asked for the most codes we can send. Please go back and start again, or call us.', 'abf-accounting'), 429, array('resendsLeft' => 0));
    }
    $code          = str_pad((string) wp_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $s['code']     = wp_hash_password($code);
    $s['expires']  = time() + ABF_BUY_CODE_TTL;
    $s['sent_at']  = time();
    $s['attempts'] = 0;
    ++$s['resends'];
    $sent = abf_buy_send_code($s, $code);
    abf_buy_save_session($token, $s, ABF_BUY_CODE_TTL + 5 * MINUTE_IN_SECONDS);

    return array('sent' => $sent, 'resendIn' => ABF_BUY_RESEND_WAIT, 'resendsLeft' => ABF_BUY_MAX_RESENDS - $s['resends']);
}

/** POST /code/check — 5 tries, 10 minutes. */
function abf_buy_rest_check(WP_REST_Request $r)
{
    $token = (string) $r->get_param('token');
    $code  = preg_replace('/\D/', '', (string) $r->get_param('code'));
    $s     = abf_buy_session($token);
    if (! $s) {
        return abf_buy_error(__('Your code has expired. Please go back and start again.', 'abf-accounting'), 410, array('restart' => true));
    }
    if ($s['verified']) {
        return array('verified' => true);
    }
    if (time() > $s['expires']) {
        return abf_buy_error(__('That code has expired. Press “Resend code” for a new one.', 'abf-accounting'), 410);
    }
    if (4 !== strlen($code)) {
        return abf_buy_error(__('Enter all 4 digits of your code.', 'abf-accounting'));
    }
    if (! wp_check_password($code, $s['code'])) {
        ++$s['attempts'];
        if ($s['attempts'] >= ABF_BUY_MAX_ATTEMPTS) {
            delete_transient(abf_buy_session_key($token));

            return abf_buy_error(__('Too many wrong codes. Please go back and start again.', 'abf-accounting'), 429, array('restart' => true));
        }
        abf_buy_save_session($token, $s, max(60, $s['expires'] - time() + 5 * MINUTE_IN_SECONDS));
        $left = ABF_BUY_MAX_ATTEMPTS - $s['attempts'];

        /* translators: %d: tries left. */
        return abf_buy_error(sprintf(_n('That code isn’t right. %d try left.', 'That code isn’t right. %d tries left.', $left, 'abf-accounting'), $left));
    }
    $s['verified'] = true;
    unset($s['code']);
    abf_buy_save_session($token, $s, ABF_BUY_VERIFIED_TTL);

    return array('verified' => true);
}

/** Verified session for the current request, or an error. */
function abf_buy_verified(WP_REST_Request $r)
{
    $s = abf_buy_session((string) $r->get_param('token'));
    if (! $s || empty($s['verified'])) {
        return abf_buy_error(__('Please verify your details again.', 'abf-accounting'), 401, array('restart' => true));
    }

    return $s;
}

/** Paid orders this company already has for this product ("on 12 March 2026"), or ''. */
function abf_buy_repeat_date(string $company, int $product_id): string
{
    if ('' === $company) {
        return '';
    }
    $orders = wc_get_orders(
        array(
            'limit'      => 20,
            'status'     => array('processing', 'completed', 'on-hold'),
            'meta_query' => array(array('key' => '_abf_company_number', 'value' => $company)), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            'orderby'    => 'date',
            'order'      => 'DESC',
        )
    );
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            if ((int) $item->get_product_id() === $product_id) {
                return wc_format_datetime($order->get_date_created(), 'j F Y');
            }
        }
    }

    return '';
}

/** POST /summary — company details and repeat-order warning for the Order summary step. */
function abf_buy_rest_summary(WP_REST_Request $r)
{
    $s = abf_buy_verified($r);
    if (is_wp_error($s)) {
        return $s;
    }
    $service = abf_buy_service($s['service']);
    $company = $s['company'] ? abf_buy_company($s['company']) : null;
    if (is_wp_error($company)) {
        return abf_buy_error($company->get_error_message(), 502);
    }

    return array(
        'company' => $company ? array('name' => $company['name'], 'number' => $company['number'], 'address' => $company['address']) : null,
        'name'    => $s['name'],
        'repeat'  => $company ? abf_buy_repeat_date($company['number'], $service['product']->get_id()) : '',
    );
}

/* ================================================================== */
/* Orders                                                              */
/* ================================================================== */

/** Existing customer for the email, or a new WooCommerce account (with "set your password" email). */
function abf_buy_customer(array $s): int
{
    if (! empty($s['user'])) {
        return (int) $s['user'];
    }
    $user = get_user_by('email', $s['email']);
    if ($user) {
        return (int) $user->ID;
    }
    [$first, $last] = abf_buy_split_name($s['name']);
    $yes            = static fn () => 'yes';
    add_filter('pre_option_woocommerce_registration_generate_password', $yes);
    add_filter('pre_option_woocommerce_registration_generate_username', $yes);
    $id = wc_create_new_customer($s['email'], '', '', array('first_name' => $first, 'last_name' => $last));
    remove_filter('pre_option_woocommerce_registration_generate_password', $yes);
    remove_filter('pre_option_woocommerce_registration_generate_username', $yes);
    if (is_wp_error($id)) {
        error_log('[abf-buy] Could not create customer: ' . $id->get_error_message()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

        return 0;
    }
    update_user_meta($id, 'billing_phone', $s['phone']);

    return (int) $id;
}

/** POST /order — create the WooCommerce order and send the customer to GoCardless. */
function abf_buy_rest_order(WP_REST_Request $r)
{
    $s = abf_buy_verified($r);
    if (is_wp_error($s)) {
        return $s;
    }
    if (! $r->get_param('terms')) {
        return abf_buy_error(__('Please agree to the Terms & Conditions to continue.', 'abf-accounting'), 400, array('fields' => array('terms' => true)));
    }
    if (! abf_buy_gc_ready()) {
        return abf_buy_error(__('Online payment isn’t switched on yet. Please call us on 07405 141694 and we will take your order.', 'abf-accounting'), 503);
    }
    $service = abf_buy_service($s['service']);
    $company = $s['company'] ? abf_buy_company($s['company']) : null;
    if (! $service || is_wp_error($company) || ('company' === $service['flow'] && ! $company)) {
        return abf_buy_error(__('Something went wrong with your order. Please try again.', 'abf-accounting'), 400, array('restart' => true));
    }

    // One order per verified session: pressing the button twice reuses it.
    if (! empty($s['order'])) {
        $order = wc_get_order((int) $s['order']);
    }
    if (empty($order) || ! $order->has_status(array('pending', 'failed'))) {
        [$first, $last] = abf_buy_split_name($s['name']);
        $order          = wc_create_order(array('customer_id' => abf_buy_customer($s), 'created_via' => 'abf-buy-now'));
        if (is_wp_error($order)) {
            return abf_buy_error(__('We couldn’t create your order. Please try again.', 'abf-accounting'), 500);
        }
        $order->add_product($service['product'], 1);
        $order->set_billing_first_name($first);
        $order->set_billing_last_name($last);
        $order->set_billing_email($s['email']);
        $order->set_billing_phone($s['phone']);
        $order->set_billing_country('GB');
        if ($company) {
            $a = $company['raw'];
            $order->set_billing_company($company['name']);
            $order->set_billing_address_1(trim(($a['premises'] ?? '') . ' ' . ($a['address_line_1'] ?? '')));
            $order->set_billing_address_2((string) ($a['address_line_2'] ?? ''));
            $order->set_billing_city((string) ($a['locality'] ?? ''));
            $order->set_billing_postcode((string) ($a['postal_code'] ?? ''));
            $order->update_meta_data('_abf_company_name', $company['name']);
            $order->update_meta_data('_abf_company_number', $company['number']);
            $order->update_meta_data('_abf_company_address', $company['address']);
        }
        $order->update_meta_data('_abf_service', $service['key']);
        $order->set_payment_method(ABF_BUY_GATEWAY);
        $order->set_payment_method_title(__('Pay by bank', 'abf-accounting'));
        $order->set_customer_ip_address(WC_Geolocation::get_ip_address());
        $order->set_customer_user_agent(wc_get_user_agent());
        $order->calculate_totals(false);
        $order->save();
        $s['order'] = $order->get_id();
        abf_buy_save_session((string) $r->get_param('token'), $s, ABF_BUY_VERIFIED_TTL);
    }

    $url = abf_buy_gc_start($order);
    if (is_wp_error($url)) {
        return abf_buy_error($url->get_error_message(), 502);
    }

    return array('redirect' => $url, 'order' => $order->get_order_number());
}

/** The order behind ?order=&key= (for the failed-payment screen), or null. */
function abf_buy_order_from(WP_REST_Request $r): ?WC_Order
{
    $order = wc_get_order(absint($r->get_param('order')));

    return $order && hash_equals($order->get_order_key(), (string) $r->get_param('key')) && ABF_BUY_GATEWAY === $order->get_payment_method() ? $order : null;
}

/** Pop-up data for an order: shown on the "Payment failed" screen after GoCardless. */
function abf_buy_order_view(WC_Order $order): array
{
    $items = $order->get_items();
    $item  = $items ? reset($items) : null;

    return array(
        'service' => (string) $order->get_meta('_abf_service'),
        'product' => $item ? $item->get_name() : '',
        'total'   => (float) $order->get_total(),
        'company' => $order->get_meta('_abf_company_number') ? array(
            'name'    => (string) $order->get_meta('_abf_company_name'),
            'number'  => (string) $order->get_meta('_abf_company_number'),
            'address' => (string) $order->get_meta('_abf_company_address'),
        ) : null,
        'paid'    => $order->is_paid(),
        'number'  => $order->get_order_number(),
    );
}

function abf_buy_rest_order_status(WP_REST_Request $r)
{
    $order = abf_buy_order_from($r);

    return $order ? abf_buy_order_view($order) : abf_buy_error(__('We couldn’t find that order.', 'abf-accounting'), 404);
}

/** POST /order/retry — pay again for a failed order. */
function abf_buy_rest_retry(WP_REST_Request $r)
{
    $order = abf_buy_order_from($r);
    if (! $order) {
        return abf_buy_error(__('We couldn’t find that order.', 'abf-accounting'), 404);
    }
    if ($order->is_paid()) {
        return array('redirect' => $order->get_checkout_order_received_url());
    }
    if (! $r->get_param('terms')) {
        return abf_buy_error(__('Please agree to the Terms & Conditions to continue.', 'abf-accounting'), 400, array('fields' => array('terms' => true)));
    }
    if (! abf_buy_gc_ready()) {
        return abf_buy_error(__('Online payment isn’t switched on yet. Please call us on 07405 141694 and we will take your order.', 'abf-accounting'), 503);
    }
    if (! abf_buy_rate_ok('retry', 10, HOUR_IN_SECONDS)) {
        return abf_buy_error(__('Too many attempts. Please try again later or call us.', 'abf-accounting'), 429);
    }
    $url = abf_buy_gc_start($order);

    return is_wp_error($url) ? abf_buy_error($url->get_error_message(), 502) : array('redirect' => $url);
}

/* ================================================================== */
/* GoCardless (Pay by bank, hosted page)                               */
/* ================================================================== */

function abf_buy_gc_ready(): bool
{
    return '' !== abf_accounting_setting('gc_token');
}

/** API call; returns the decoded body or WP_Error. */
function abf_buy_gc(string $method, string $path, ?array $body = null)
{
    $token = abf_accounting_setting('gc_token');
    $base  = str_starts_with($token, 'live_') ? 'https://api.gocardless.com' : 'https://api-sandbox.gocardless.com';
    $args  = array(
        'method'  => $method,
        'timeout' => 15,
        'headers' => array(
            'Authorization'      => 'Bearer ' . $token,
            'GoCardless-Version' => '2015-07-06',
            'Content-Type'       => 'application/json',
            'Accept'             => 'application/json',
        ),
    );
    if (null !== $body) {
        $args['body']                       = wp_json_encode($body);
        $args['headers']['Idempotency-Key'] = wp_generate_uuid4();
    }
    $response = wp_remote_request($base . $path, $args);
    if (is_wp_error($response)) {
        return new WP_Error('abf_gc', __('We couldn’t reach GoCardless. Please try again in a moment.', 'abf-accounting'));
    }
    $data = (array) json_decode((string) wp_remote_retrieve_body($response), true);
    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        error_log('[abf-buy] GoCardless ' . $method . ' ' . $path . ' → ' . $code . ' ' . wp_json_encode($data['error'] ?? $data)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

        return new WP_Error('abf_gc', __('GoCardless couldn’t start the payment. Please try again, or call us on 07405 141694.', 'abf-accounting'));
    }

    return $data;
}

/**
 * Billing request (one Faster Payment for the order total) + hosted flow. Returns the
 * GoCardless page to send the customer to.
 *
 * @return string|WP_Error
 */
function abf_buy_gc_start(WC_Order $order)
{
    $items = $order->get_items();
    $item  = $items ? reset($items) : null;
    /* translators: 1: order number, 2: service. */
    $description = mb_substr(sprintf(__('%1$s %2$s', 'abf-accounting'), $order->get_order_number(), $item ? $item->get_name() : ''), 0, 100);

    $br = abf_buy_gc(
        'POST',
        '/billing_requests',
        array(
            'billing_requests' => array(
                'payment_request' => array(
                    'description' => $description,
                    'amount'      => (int) round((float) $order->get_total() * 100),
                    'currency'    => 'GBP',
                    'scheme'      => 'faster_payments',
                ),
                'metadata'        => array('order_id' => (string) $order->get_id()),
            ),
        )
    );
    if (is_wp_error($br)) {
        return $br;
    }
    $br_id = (string) ($br['billing_requests']['id'] ?? '');

    $back = array('abf_gc' => $order->get_id(), 'key' => $order->get_order_key());
    $flow = abf_buy_gc(
        'POST',
        '/billing_request_flows',
        array(
            'billing_request_flows' => array(
                'redirect_uri'       => add_query_arg($back + array('abf_gc_step' => 'done'), home_url('/')),
                'exit_uri'           => add_query_arg($back + array('abf_gc_step' => 'exit'), home_url('/')),
                'links'              => array('billing_request' => $br_id),
                'prefilled_customer' => array(
                    'given_name'   => $order->get_billing_first_name(),
                    'family_name'  => $order->get_billing_last_name(),
                    'email'        => $order->get_billing_email(),
                    'company_name' => $order->get_billing_company(),
                ),
            ),
        )
    );
    if (is_wp_error($flow) || empty($flow['billing_request_flows']['authorisation_url'])) {
        return is_wp_error($flow) ? $flow : new WP_Error('abf_gc', __('GoCardless couldn’t start the payment.', 'abf-accounting'));
    }

    $order->update_meta_data('_abf_gc_billing_request', $br_id);
    $order->update_meta_data('_abf_gc_state', 'started');
    if ($order->has_status('failed')) {
        $order->set_status('pending', __('Customer is trying Pay by bank again.', 'abf-accounting'));
    }
    $order->add_order_note(sprintf(/* translators: %s: billing request ID. */ __('Sent to GoCardless (billing request %s).', 'abf-accounting'), $br_id));
    $order->save();

    return (string) $flow['billing_request_flows']['authorisation_url'];
}

/** Billing request fulfilled: the bank approved, GoCardless is confirming the payment. */
function abf_buy_gc_mark_processing(WC_Order $order, string $payment_id = ''): void
{
    if ($order->is_paid() || 'processing' === $order->get_meta('_abf_gc_state')) {
        return;
    }
    $order->update_meta_data('_abf_gc_state', 'processing');
    if ($payment_id) {
        $order->update_meta_data('_abf_gc_payment', $payment_id);
        $order->set_transaction_id($payment_id);
    }
    $order->add_order_note(__('Bank approved the payment. Waiting for GoCardless to confirm it.', 'abf-accounting'));
    $order->save();
}

function abf_buy_gc_mark_failed(WC_Order $order, string $why): void
{
    if ($order->is_paid() || $order->has_status('failed')) {
        return;
    }
    $order->update_meta_data('_abf_gc_state', 'failed');
    $order->set_status('failed', $why);
    $order->save();
}

/** Payment ID created by a fulfilled billing request (asks GoCardless when not in the event). */
function abf_buy_gc_payment_for(string $br_id): string
{
    $br = abf_buy_gc('GET', '/billing_requests/' . rawurlencode($br_id));

    return is_wp_error($br) ? '' : (string) ($br['billing_requests']['links']['payment_request_payment'] ?? '');
}

function abf_buy_order_by_meta(string $key, string $value): ?WC_Order
{
    if ('' === $value) {
        return null;
    }
    $orders = wc_get_orders(
        array(
            'limit'      => 1,
            'status'     => array_keys(wc_get_order_statuses()),
            'meta_query' => array(array('key' => $key, 'value' => $value)), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        )
    );

    return $orders ? $orders[0] : null;
}

/**
 * Back from GoCardless (?abf_gc=ORDER&key=…&abf_gc_step=done|exit). The webhook is what marks
 * the order paid; here we only show the right screen.
 */
function abf_buy_gc_return(): void
{
    if (! isset($_GET['abf_gc'], $_GET['key'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }
    $order = wc_get_order(absint($_GET['abf_gc'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $key   = sanitize_text_field(wp_unslash($_GET['key'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (! $order || ! hash_equals($order->get_order_key(), $key)) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
    $failed_url = add_query_arg(array('buy' => 'payment', 'order' => $order->get_id(), 'key' => $key), home_url('/'));
    $step       = sanitize_key(wp_unslash($_GET['abf_gc_step'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ($order->is_paid()) {
        wp_safe_redirect($order->get_checkout_order_received_url());
        exit;
    }
    $br_id  = (string) $order->get_meta('_abf_gc_billing_request');
    $br     = $br_id ? abf_buy_gc('GET', '/billing_requests/' . rawurlencode($br_id)) : null;
    $status = is_array($br) ? (string) ($br['billing_requests']['status'] ?? '') : '';

    if ('fulfilled' === $status) {
        abf_buy_gc_mark_processing($order, (string) ($br['billing_requests']['links']['payment_request_payment'] ?? ''));
        wp_safe_redirect($order->get_checkout_order_received_url());
        exit;
    }
    if ('exit' === $step || in_array($status, array('cancelled', 'failed'), true)) {
        abf_buy_gc_mark_failed($order, __('Pay by bank was not completed (customer left GoCardless or the bank declined).', 'abf-accounting'));
        wp_safe_redirect($failed_url);
        exit;
    }
    // Still being processed by the bank: show "Payment processing"; the webhook finishes it.
    abf_buy_gc_mark_processing($order);
    wp_safe_redirect($order->get_checkout_order_received_url());
    exit;
}
add_action('template_redirect', 'abf_buy_gc_return', 5);

/** POST /gocardless/webhook — signed events from GoCardless. */
function abf_buy_gc_webhook(WP_REST_Request $r): WP_REST_Response
{
    $secret = abf_accounting_setting('gc_webhook');
    $body   = $r->get_body();
    $sign   = (string) $r->get_header('webhook_signature');
    if ('' === $secret || '' === $sign || ! hash_equals(hash_hmac('sha256', $body, $secret), $sign)) {
        return new WP_REST_Response(array('error' => 'invalid signature'), 498);
    }

    foreach ((array) (json_decode($body, true)['events'] ?? array()) as $event) {
        $type   = (string) ($event['resource_type'] ?? '');
        $action = (string) ($event['action'] ?? '');
        $links  = (array) ($event['links'] ?? array());

        if ('billing_requests' === $type) {
            $order = abf_buy_order_by_meta('_abf_gc_billing_request', (string) ($links['billing_request'] ?? ''));
            if (! $order) {
                continue;
            }
            if ('fulfilled' === $action) {
                $payment = (string) ($links['payment_request_payment'] ?? '') ?: abf_buy_gc_payment_for((string) $links['billing_request']);
                abf_buy_gc_mark_processing($order, $payment);
            } elseif (in_array($action, array('cancelled', 'failed'), true)) {
                abf_buy_gc_mark_failed($order, __('GoCardless: the billing request was cancelled or failed.', 'abf-accounting'));
            }
        } elseif ('payments' === $type) {
            $payment = (string) ($links['payment'] ?? '');
            $order   = abf_buy_order_by_meta('_abf_gc_payment', $payment);
            if (! $order) {
                // The payment event can arrive before "fulfilled" was saved: ask GoCardless.
                $p     = abf_buy_gc('GET', '/payments/' . rawurlencode($payment));
                $br_id = is_array($p) ? (string) ($p['payments']['links']['billing_request'] ?? '') : '';
                $order = abf_buy_order_by_meta('_abf_gc_billing_request', $br_id);
                if ($order) {
                    $order->update_meta_data('_abf_gc_payment', $payment);
                    $order->save();
                }
            }
            if (! $order) {
                continue;
            }
            if (in_array($action, array('confirmed', 'paid_out'), true) && ! $order->is_paid()) {
                $order->update_meta_data('_abf_gc_state', 'paid');
                $order->add_order_note(__('GoCardless confirmed the payment.', 'abf-accounting'));
                $order->payment_complete($payment); // → Processing: customer and admin emails go out.
            } elseif (in_array($action, array('failed', 'cancelled', 'charged_back'), true)) {
                abf_buy_gc_mark_failed($order, sprintf(/* translators: %s: GoCardless action. */ __('GoCardless payment %s.', 'abf-accounting'), $action));
            }
        }
    }

    return new WP_REST_Response(null, 200);
}

/* ---------- The WooCommerce gateway (Order Pay retries, admin, emails) ---------- */

function abf_buy_register_gateway(array $gateways): array
{
    if (class_exists('WC_Payment_Gateway') && ! class_exists('ABF_Gateway_Pay_By_Bank')) {
        /**
         * "Pay by bank" through GoCardless (hosted page).
         */
        class ABF_Gateway_Pay_By_Bank extends WC_Payment_Gateway // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
        {
            public function __construct()
            {
                $this->id                 = ABF_BUY_GATEWAY;
                $this->method_title       = __('Pay by bank (GoCardless)', 'abf-accounting');
                $this->method_description = __('Instant bank payments through GoCardless. Keys are in Appearance → ABF settings.', 'abf-accounting');
                $this->title              = __('Pay by bank', 'abf-accounting');
                $this->description        = __('Pay securely from your bank account with GoCardless. You will approve the payment in your bank app. No card needed.', 'abf-accounting');
                $this->has_fields         = false;
                $this->enabled            = abf_buy_gc_ready() ? 'yes' : 'no';
                $this->supports           = array('products');
            }

            public function process_payment($order_id): array
            {
                $order = wc_get_order($order_id);
                $url   = $order ? abf_buy_gc_start($order) : new WP_Error('abf_gc', __('Order not found.', 'abf-accounting'));
                if (is_wp_error($url)) {
                    wc_add_notice($url->get_error_message(), 'error');

                    return array('result' => 'failure');
                }

                return array('result' => 'success', 'redirect' => $url);
            }
        }
    }
    if (class_exists('ABF_Gateway_Pay_By_Bank')) {
        $gateways[] = 'ABF_Gateway_Pay_By_Bank';
    }

    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'abf_buy_register_gateway');

/** Only "Pay by bank" is offered (no Stripe / cards). */
function abf_buy_only_pay_by_bank(array $gateways): array
{
    return isset($gateways[ABF_BUY_GATEWAY]) ? array(ABF_BUY_GATEWAY => $gateways[ABF_BUY_GATEWAY]) : array();
}
add_filter('woocommerce_available_payment_gateways', 'abf_buy_only_pay_by_bank', 100);

/* ================================================================== */
/* Company details on orders and emails                                */
/* ================================================================== */

function abf_buy_company_lines(WC_Order $order): array
{
    if (! $order->get_meta('_abf_company_number')) {
        return array();
    }

    return array(
        __('Company name', 'abf-accounting')       => (string) $order->get_meta('_abf_company_name'),
        __('Company number', 'abf-accounting')     => (string) $order->get_meta('_abf_company_number'),
        __('Registered address', 'abf-accounting') => (string) $order->get_meta('_abf_company_address'),
    );
}

function abf_buy_admin_order_company(WC_Order $order): void
{
    $lines = abf_buy_company_lines($order);
    if (! $lines) {
        return;
    }
    echo '<div class="abf-order-company"><h3>' . esc_html__('Company (Companies House)', 'abf-accounting') . '</h3>';
    foreach ($lines as $label => $value) {
        echo '<p><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</p>';
    }
    echo '</div>';
}
add_action('woocommerce_admin_order_data_after_billing_address', 'abf_buy_admin_order_company');

/** Admin "new order" and the customer's order emails. */
function abf_buy_email_company(WC_Order $order, bool $sent_to_admin, bool $plain_text): void
{
    $lines = abf_buy_company_lines($order);
    if (! $lines) {
        return;
    }
    if ($plain_text) {
        echo "\n" . esc_html(strtoupper(__('Company', 'abf-accounting'))) . "\n";
        foreach ($lines as $label => $value) {
            echo esc_html($label . ': ' . $value) . "\n";
        }

        return;
    }
    echo '<h2>' . esc_html__('Company', 'abf-accounting') . '</h2><table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #e5e7eb;margin-bottom:24px" border="1">';
    foreach ($lines as $label => $value) {
        echo '<tr><th style="text-align:left;width:40%">' . esc_html($label) . '</th><td style="text-align:left">' . esc_html($value) . '</td></tr>';
    }
    echo '</table>';
}
add_action('woocommerce_email_order_meta', 'abf_buy_email_company', 20, 3);

/* ================================================================== */
/* No cart: buttons open the pop-up                                    */
/* ================================================================== */

/**
 * Cart and the plain checkout aren't used any more (the pop-up replaces them). Order Pay and
 * Order Received stay.
 */
function abf_buy_no_cart(): void
{
    if (! function_exists('is_cart')) {
        return;
    }
    $pay_or_received = is_wc_endpoint_url('order-pay') || is_wc_endpoint_url('order-received');
    if (is_cart() || (is_checkout() && ! $pay_or_received)) {
        wp_safe_redirect(add_query_arg('buy', 'company-accounts', home_url('/pricing/')));
        exit;
    }
}
add_action('template_redirect', 'abf_buy_no_cart', 6);

/* ================================================================== */
/* Front end                                                           */
/* ================================================================== */

/** Pop-up config: services, endpoints, customer (when logged in). */
function abf_buy_config(): array
{
    $services = array();
    foreach (array_keys(abf_buy_services()) as $key) {
        $s = abf_buy_service($key);
        if ($s) {
            $services[] = array(
                'key'     => $key,
                'flow'    => $s['flow'],
                'name'    => $s['name'],
                'label'   => $s['name'] . ' — ' . abf_buy_money($s['price']),
                'price'   => $s['price'],
                'summary' => $s['summary'],
                'listed'  => $s['listed'],
                // Services with the same group search the same companies, so a picked company
                // can stay when switching between them.
                'group'   => $s['subtype'] . '|' . implode(',', $s['types']),
            );
        }
    }

    $user = null;
    if (is_user_logged_in()) {
        $u      = wp_get_current_user();
        $mobile = abf_buy_uk_mobile((string) get_user_meta($u->ID, 'billing_phone', true));
        $user   = array(
            'name'  => trim($u->first_name . ' ' . $u->last_name) ?: $u->display_name,
            'email' => $u->user_email,
            'phone' => $mobile ? substr($mobile, 3) : '',
        );
    }
    $terms = function_exists('wc_terms_and_conditions_page_id') ? wc_terms_and_conditions_page_id() : 0;

    return array(
        'api'      => esc_url_raw(rest_url('abf/v1/')),
        'nonce'    => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
        'services' => $services,
        'user'     => $user,
        'contact'  => home_url('/contact/'),
        'terms'    => $terms ? get_permalink($terms) : home_url('/terms-and-conditions-for-one-off-services/'),
        'phone'    => '07405 141694',
        'phoneUri' => 'tel:+447405141694',
        // Codes go by email only; text messages join in once a Twilio account is added in settings.
        'sms'      => abf_buy_sms_ready(),
    );
}

function abf_buy_assets(): void
{
    if (is_admin() || ! function_exists('WC')) {
        return;
    }
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style('abf-accounting-buy-now', get_theme_file_uri('assets/css/buy-now.css'), array('abf-accounting-frontend'), $version);
    wp_enqueue_script('abf-accounting-buy-now', get_theme_file_uri('assets/js/buy-now.js'), array(), $version, array('in_footer' => true, 'strategy' => 'defer'));
    wp_add_inline_script('abf-accounting-buy-now', 'window.abfBuy = ' . wp_json_encode(abf_buy_config()) . ';', 'before');
}
add_action('wp_enqueue_scripts', 'abf_buy_assets', 25);

