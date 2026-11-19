<?php
/**
 * Trustpilot (client, 19 Nov 2026): no typed rating or copied reviews anywhere, and the client
 * stays on Trustpilot's free plan. The "Trustpilot Reviews" section (Figma "Section /
 * Trustpilot Reviews") links to ABF's reviews and shows Trustpilot's free "Review Collector"
 * TrustBox ("Review us on Trustpilot") — the only TrustBox the free plan allows (checked
 * against Trustpilot's widget API on 19 Nov 2026).
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

const ABF_ACCOUNTING_TRUSTPILOT_URL       = 'https://uk.trustpilot.com/review/abfaccounting.com';
const ABF_ACCOUNTING_TRUSTPILOT_REVIEW    = 'https://uk.trustpilot.com/evaluate/abfaccounting.com';
const ABF_ACCOUNTING_TRUSTPILOT_COLLECTOR = '56278e9abfbbba0bdcd568bc'; // Review Collector TrustBox (free)

/** The "Read our reviews on Trustpilot" button (opens Trustpilot in a new tab). */
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

/**
 * "Review us on Trustpilot": Trustpilot's Review Collector TrustBox. The link inside looks the
 * same and is what shows until Trustpilot's script swaps it (or when the script is blocked).
 */
function abf_accounting_trustpilot_collector(): string
{
    wp_enqueue_script('abf-trustpilot', 'https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js', array(), null, array('in_footer' => true, 'strategy' => 'async')); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion

    return sprintf(
        '<div class="trustpilot-widget abf-tp-collector" data-locale="en-GB" data-template-id="%1$s" data-businessunit-id="%2$s" data-style-height="52px" data-style-width="100%%"><a class="abf-tp-collector__fallback" href="%3$s" target="_blank" rel="noopener">%4$s <span class="abf-tp-collector__star" aria-hidden="true">★</span> <strong>Trustpilot</strong><span class="screen-reader-text"> %5$s</span></a></div>',
        esc_attr(ABF_ACCOUNTING_TRUSTPILOT_COLLECTOR),
        esc_attr(abf_accounting_setting('tp_business_unit')),
        esc_url(ABF_ACCOUNTING_TRUSTPILOT_REVIEW),
        esc_html__('Review us on', 'abf-accounting'),
        esc_html__('(opens in a new tab)', 'abf-accounting')
    );
}
