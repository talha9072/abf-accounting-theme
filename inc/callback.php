<?php
/**
 * Request a callback pop-up (Figma "Request a callback / Step 1–3", 19 Nov 2026):
 * Your details (name, email, UK mobile, preferred date and time, message) → Verify (4-digit
 * code by email, the same code system as Buy now) → Done ("We will call you on …").
 *
 * Every link to the Contact page's form (abf_accounting_callback_url() = /contact/#callback-form)
 * opens the pop-up; without JavaScript it still goes to the form on Contact.
 * After the code is checked ABF gets the request by email (filter
 * `abf_accounting_contact_recipient`, as the Contact form) and the visitor gets a confirmation.
 * Logged-in customers skip Verify, as in Buy now. reCAPTCHA v3 joins in when its keys are
 * saved in ABF settings. JS: assets/js/callback.js.
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

const ABF_CALLBACK_TZ        = 'Europe/London';
const ABF_CALLBACK_FIRST     = '09:00';             // first slot
const ABF_CALLBACK_LAST      = '17:00';             // last slot
const ABF_CALLBACK_STEP      = 30;                  // minutes between slots
const ABF_CALLBACK_MAX_DAYS  = 60;                  // how far ahead a date can be
const ABF_CALLBACK_MAX_CHARS = 1000;                // message length

/** Where every "Request a callback" button points (the pop-up opens instead when JS runs). */
function abf_accounting_callback_url(): string
{
    return home_url('/contact/#callback-form');
}

/** "09:00" … "17:00" every 30 minutes. */
function abf_callback_slots(): array
{
    $slots = array();
    $start = (int) substr(ABF_CALLBACK_FIRST, 0, 2) * 60 + (int) substr(ABF_CALLBACK_FIRST, 3, 2);
    $end   = (int) substr(ABF_CALLBACK_LAST, 0, 2) * 60 + (int) substr(ABF_CALLBACK_LAST, 3, 2);
    for ($m = $start; $m <= $end; $m += ABF_CALLBACK_STEP) {
        $slots[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }

    return $slots;
}

function abf_callback_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone(ABF_CALLBACK_TZ));
}

/**
 * The visitor's details, checked. Date is optional when the time is "As soon as possible";
 * a chosen time needs a weekday date, today or later, and not already gone.
 *
 * @return array|WP_Error
 */
function abf_callback_details(WP_REST_Request $r)
{
    $name    = trim(preg_replace('/\s+/', ' ', sanitize_text_field((string) $r->get_param('name'))));
    $email   = sanitize_email((string) $r->get_param('email'));
    $phone   = abf_buy_uk_mobile((string) $r->get_param('phone'));
    $date    = sanitize_text_field((string) $r->get_param('date'));
    $time    = sanitize_text_field((string) $r->get_param('time'));
    $message = trim(sanitize_textarea_field((string) $r->get_param('message')));
    $errors  = array();

    if (mb_strlen($name) < 2 || ! str_contains($name, ' ') || mb_strlen($name) > 80) {
        $errors['name'] = __('Please enter your first and last name.', 'abf-accounting');
    }
    if (! is_email($email)) {
        $errors['email'] = __('Please enter a valid email address.', 'abf-accounting');
    }
    if ('' === $phone) {
        $errors['phone'] = __('Please enter a UK mobile number.', 'abf-accounting');
    }
    if (mb_strlen($message) > ABF_CALLBACK_MAX_CHARS) {
        /* translators: %d: number of characters. */
        $errors['message'] = sprintf(__('Please keep your message under %d characters.', 'abf-accounting'), ABF_CALLBACK_MAX_CHARS);
    }

    $asap = '' === $time || 'asap' === $time;
    if (! $asap && ! in_array($time, abf_callback_slots(), true)) {
        $errors['time'] = __('Please choose a time from the list.', 'abf-accounting');
    }

    $day = null;
    if ('' !== $date) {
        $tz  = new DateTimeZone(ABF_CALLBACK_TZ);
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $tz);
        $now = abf_callback_now();
        if (! $day || $day->format('Y-m-d') !== $date) {
            $errors['date'] = __('Please choose a date.', 'abf-accounting');
            $day            = null;
        } elseif ($day < $now->setTime(0, 0)) {
            $errors['date'] = __('Please choose today or a later date.', 'abf-accounting');
        } elseif ($day > $now->setTime(0, 0)->modify('+' . ABF_CALLBACK_MAX_DAYS . ' days')) {
            $errors['date'] = __('Please choose a date in the next two months.', 'abf-accounting');
        } elseif ((int) $day->format('N') > 5) {
            $errors['date'] = __('We call Monday to Friday. Please choose a weekday.', 'abf-accounting');
        } elseif (! $asap && empty($errors['time'])) {
            [$h, $m] = array_map('intval', explode(':', $time));
            if ($day->setTime($h, $m) <= $now) {
                $errors['time'] = __('That time has passed. Please choose a later time.', 'abf-accounting');
            }
        }
    } elseif (! $asap) {
        $errors['date'] = __('Please choose a date for this time.', 'abf-accounting');
    }

    if ($errors) {
        return abf_buy_error(__('Please check the highlighted details.', 'abf-accounting'), 400, array('fields' => $errors));
    }

    return array(
        'kind'    => 'callback',
        'name'    => $name,
        'email'   => strtolower($email),
        'phone'   => $phone,
        'date'    => $day ? $day->format('Y-m-d') : '',
        'time'    => $asap ? 'asap' : $time,
        'message' => $message,
    );
}

/**
 * "on Thursday 20 November at 10:00", "on Thursday 20 November, as soon as we can", or
 * "as soon as possible".
 */
function abf_callback_when(array $s): string
{
    if ('' === $s['date']) {
        return __('as soon as possible', 'abf-accounting');
    }
    $day   = DateTimeImmutable::createFromFormat('!Y-m-d', $s['date'], new DateTimeZone(ABF_CALLBACK_TZ));
    $label = $day->format('l j F');
    if ($day->format('Y') !== abf_callback_now()->format('Y')) {
        $label .= ' ' . $day->format('Y');
    }

    /* translators: 1: day and date, 2: time. */
    return 'asap' === $s['time'] ? sprintf(__('on %s, as early as we can', 'abf-accounting'), $label) : sprintf(__('on %1$s at %2$s', 'abf-accounting'), $label, $s['time']);
}

/**
 * Emails the request to ABF and a confirmation to the visitor. ABF's copy must go out; the
 * confirmation is best effort.
 */
function abf_callback_send(array $s): bool
{
    $first   = abf_buy_split_name($s['name'])[0];
    $when    = abf_callback_when($s);
    $contact = abf_accounting_contact();

    $to      = apply_filters('abf_accounting_contact_recipient', get_option('admin_email'));
    /* translators: 1: full name, 2: when to call. */
    $subject = sprintf(__('Callback request: %1$s, %2$s', 'abf-accounting'), $s['name'], $when);
    $lines   = array(
        __('A visitor asked for a callback on the website (email address verified).', 'abf-accounting'),
        '',
        __('Name:', 'abf-accounting') . ' ' . $s['name'],
        __('Email:', 'abf-accounting') . ' ' . $s['email'],
        __('Mobile:', 'abf-accounting') . ' ' . abf_buy_pretty_mobile($s['phone']) . ' (' . $s['phone'] . ')',
        __('Call:', 'abf-accounting') . ' ' . ucfirst($when),
        '',
        __('Message:', 'abf-accounting'),
        '' !== $s['message'] ? $s['message'] : '-',
    );
    $headers = array('Reply-To: ' . $s['name'] . ' <' . $s['email'] . '>');
    if (! wp_mail($to, $subject, implode("\n", $lines) . "\n", $headers)) {
        return false;
    }

    /* translators: %s: first name. */
    $body = '<p>' . esc_html(sprintf(__('Hi %s,', 'abf-accounting'), $first)) . '</p>'
        /* translators: %s: when we will call, e.g. "on Thursday 20 November at 10:00". */
        . '<p>' . esc_html(sprintf(__('Thank you for asking for a callback. An ABF accountant will call you %s on', 'abf-accounting'), $when)) . ' <strong>' . esc_html(abf_buy_pretty_mobile($s['phone'])) . '</strong>.</p>'
        /* translators: %s: phone number. */
        . '<p>' . esc_html(sprintf(__('Need to change the time? Call us on %s or reply to this email.', 'abf-accounting'), $contact['phone'])) . '</p>'
        . '<p>' . esc_html__('ABF Accounting', 'abf-accounting') . '</p>';
    $subject = __('Your callback with ABF Accounting', 'abf-accounting');
    if (function_exists('WC') && WC()->mailer()) {
        $mailer = WC()->mailer();
        $mailer->send($s['email'], $subject, $mailer->wrap_message(__('Your callback is booked', 'abf-accounting'), $body), array('Content-Type: text/html; charset=UTF-8'));
    } else {
        wp_mail($s['email'], $subject, wp_strip_all_tags(str_replace('</p>', "\n\n", $body)));
    }

    return true;
}

/** Finishes a verified request once (a second press doesn't send twice). */
function abf_callback_finish(string $token, array $s)
{
    if (empty($s['sent'])) {
        if (! abf_callback_send($s)) {
            return abf_buy_error(__('Sorry, we couldn’t send your request. Please try again, or call us on 07405 141694.', 'abf-accounting'), 502);
        }
        $s['sent'] = true;
        abf_buy_save_session($token, $s, HOUR_IN_SECONDS);
    }

    return array(
        'done'  => true,
        'first' => abf_buy_split_name($s['name'])[0],
        'when'  => abf_callback_when($s),
    );
}

/* ---------- REST: /callback, /callback/check (resend uses Buy now's /code/resend) ---------- */

function abf_callback_register_routes(): void
{
    $public = array('permission_callback' => '__return_true');
    register_rest_route('abf/v1', '/callback', $public + array('methods' => 'POST', 'callback' => 'abf_callback_rest_start'));
    register_rest_route('abf/v1', '/callback/check', $public + array('methods' => 'POST', 'callback' => 'abf_callback_rest_check'));
}
add_action('rest_api_init', 'abf_callback_register_routes');

/** POST /callback — check the details (and reCAPTCHA), then send a code or, when logged in, finish. */
function abf_callback_rest_start(WP_REST_Request $r)
{
    if ('' !== trim((string) $r->get_param('website'))) {
        // Honeypot filled in: pretend all is well, send nothing.
        return array('token' => wp_generate_password(32, false, false), 'verified' => false, 'resendIn' => ABF_BUY_RESEND_WAIT, 'resendsLeft' => ABF_BUY_MAX_RESENDS);
    }
    $details = abf_callback_details($r);
    if (is_wp_error($details)) {
        return $details;
    }
    if (! abf_buy_rate_ok('callback', 8, HOUR_IN_SECONDS)) {
        return abf_buy_error(__('Too many requests. Please try again in an hour, or call us on 07405 141694.', 'abf-accounting'), 429);
    }
    if (! abf_accounting_recaptcha_passes((string) $r->get_param('recaptcha'))) {
        return abf_buy_error(__('We couldn’t check that you’re not a robot. Please try again, or call us on 07405 141694.', 'abf-accounting'), 403);
    }

    $token = wp_generate_password(32, false, false);
    if (is_user_logged_in()) {
        $session = $details + array('verified' => true, 'user' => get_current_user_id());
        abf_buy_save_session($token, $session, HOUR_IN_SECONDS);
        $done = abf_callback_finish($token, $session);

        return is_wp_error($done) ? $done : array('token' => $token, 'verified' => true) + $done;
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
        return abf_buy_error(__('We couldn’t send your code. Please check your email address, or call us on 07405 141694.', 'abf-accounting'), 502);
    }
    abf_buy_save_session($token, $session, ABF_BUY_CODE_TTL + 5 * MINUTE_IN_SECONDS);

    return array('token' => $token, 'verified' => false, 'sent' => $sent, 'resendIn' => ABF_BUY_RESEND_WAIT, 'resendsLeft' => ABF_BUY_MAX_RESENDS);
}

/** POST /callback/check — the code (same rules as Buy now), then the request goes to ABF. */
function abf_callback_rest_check(WP_REST_Request $r)
{
    $token = (string) $r->get_param('token');
    $s     = abf_buy_session($token);
    if (! $s || 'callback' !== ($s['kind'] ?? '')) {
        return abf_buy_error(__('Your code has expired. Please go back and start again.', 'abf-accounting'), 410, array('restart' => true));
    }
    $checked = abf_buy_rest_check($r);
    if (is_wp_error($checked)) {
        return $checked;
    }

    return abf_callback_finish($token, abf_buy_session($token) ?? $s);
}

/* ---------- Front end ---------- */

function abf_callback_config(): array
{
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
    $now   = abf_callback_now();
    $terms = get_page_by_path('terms-and-conditions-of-engagement');

    return array(
        'api'       => esc_url_raw(rest_url('abf/v1/')),
        'nonce'     => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
        'user'      => $user,
        'slots'     => abf_callback_slots(),
        'today'     => $now->format('Y-m-d'),
        'now'       => $now->format('H:i'),
        'maxDate'   => $now->modify('+' . ABF_CALLBACK_MAX_DAYS . ' days')->format('Y-m-d'),
        'maxChars'  => ABF_CALLBACK_MAX_CHARS,
        'privacy'   => home_url('/privacy-policy/'),
        'terms'     => $terms ? get_permalink($terms) : home_url('/terms-and-conditions-of-engagement/'),
        'phone'     => '07405 141694',
        'phoneUri'  => 'tel:+447405141694',
        'sms'       => abf_buy_sms_ready(),
        'recaptcha' => abf_accounting_recaptcha_enabled() ? abf_accounting_setting('recaptcha_site') : '',
        'logo'      => abf_accounting_img('abf-logo.svg'),
    );
}

function abf_callback_assets(): void
{
    if (is_admin() || ! function_exists('WC')) {
        return;
    }
    wp_enqueue_script('abf-accounting-callback', get_theme_file_uri('assets/js/callback.js'), array(), wp_get_theme()->get('Version'), array('in_footer' => true, 'strategy' => 'defer'));
    wp_add_inline_script('abf-accounting-callback', 'window.abfCallback = ' . wp_json_encode(abf_callback_config()) . ';', 'before');
}
add_action('wp_enqueue_scripts', 'abf_callback_assets', 26);
