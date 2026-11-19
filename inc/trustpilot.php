<?php
/**
 * Trustpilot in the review sections (client, 19 Nov 2026): reviews are NOT copied into the
 * site (Trustpilot's rules). The sections show ABF's rating, a "Read our reviews on
 * Trustpilot" link, and Trustpilot's own widgets (TrustBoxes) once they are available.
 *
 * ABF's free Trustpilot plan only allows the "Review Collector" TrustBox (checked against
 * Trustpilot's widget API on 19 Nov 2026: rating and carousel TrustBoxes answer "BusinessUnit
 * does not have access"). So the widgets are switched on from Appearance → ABF settings →
 * Trustpilot by pasting a TrustBox template ID — no code change when the plan changes.
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

const ABF_ACCOUNTING_TRUSTPILOT_URL = 'https://uk.trustpilot.com/review/abfaccounting.com';

/**
 * A TrustBox for a saved template ID ('' when the setting is empty). Trustpilot's script swaps
 * the link inside for the widget; without it (or with JS off) the link stays.
 *
 * @param string $setting tp_rating_template or tp_carousel_template.
 * @param string $theme   light or dark.
 * @param int    $height  Height in px (Trustpilot's recommended height for the template).
 * @param string $class   Extra class.
 */
function abf_accounting_trustbox(string $setting, string $theme, int $height, string $class = ''): string
{
    $template = abf_accounting_setting($setting);
    $unit     = abf_accounting_setting('tp_business_unit');
    if ('' === $template || '' === $unit) {
        return '';
    }

    return sprintf(
        '<div class="trustpilot-widget abf-trustbox %1$s" data-locale="en-GB" data-template-id="%2$s" data-businessunit-id="%3$s" data-style-height="%4$dpx" data-style-width="100%%" data-theme="%5$s" data-stars="4,5"><a href="%6$s" target="_blank" rel="noopener">%7$s</a></div>',
        esc_attr($class),
        esc_attr($template),
        esc_attr($unit),
        $height,
        esc_attr($theme),
        esc_url(ABF_ACCOUNTING_TRUSTPILOT_URL),
        esc_html__('Trustpilot', 'abf-accounting')
    );
}

/** The "Read our reviews on Trustpilot" link (opens Trustpilot in a new tab). */
function abf_accounting_trustpilot_link(string $class = 'abf-tp-link'): string
{
    return sprintf(
        '<a class="%1$s" href="%2$s" target="_blank" rel="noopener">%3$s<span class="screen-reader-text"> %4$s</span></a>',
        esc_attr($class),
        esc_url(ABF_ACCOUNTING_TRUSTPILOT_URL),
        esc_html__('Read our reviews on Trustpilot', 'abf-accounting'),
        esc_html__('(opens in a new tab)', 'abf-accounting')
    );
}

/** Trustpilot's widget script, only when a TrustBox is switched on in ABF settings. */
function abf_accounting_trustpilot_script(): void
{
    if ('' === abf_accounting_setting('tp_rating_template') && '' === abf_accounting_setting('tp_carousel_template')) {
        return;
    }
    wp_enqueue_script('abf-trustpilot', 'https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js', array(), null, array('in_footer' => true, 'strategy' => 'async')); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
}
add_action('wp_enqueue_scripts', 'abf_accounting_trustpilot_script');
