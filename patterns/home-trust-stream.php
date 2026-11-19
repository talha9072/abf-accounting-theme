<?php
/**
 * Title: Home — Trustpilot proof
 * Slug: abf-accounting/home-trust-stream
 * Categories: abf-sections
 * Description: "Trusted by UK businesses" with the rating badge and a "Read our reviews on Trustpilot" link; Trustpilot's review carousel sits beside it once switched on in ABF settings.
 *
 * The scrolling columns of copied review cards were removed (client, 19 Nov 2026: Trustpilot's
 * rules don't allow copying reviews). See inc/trustpilot.php.
 *
 * @package ABF_Accounting
 */

$abf_accounting_box   = abf_accounting_trustbox('tp_rating_template', 'light', 24, 'abf-stream__trustbox');
$abf_accounting_badge = $abf_accounting_box
    ? abf_accounting_block_html($abf_accounting_box)
    : abf_accounting_block_p('abf-stream__badge', '<strong>' . esc_html__('Excellent', 'abf-accounting') . '</strong> ' . esc_html__('4.7 out of 5', 'abf-accounting') . ' <span class="abf-stream__badge-star" aria-hidden="true">★</span> <span class="abf-stream__badge-brand">Trustpilot</span>')
        . abf_accounting_block_p('abf-stream__count', __('27 reviews', 'abf-accounting'));

$abf_accounting_carousel = abf_accounting_trustbox('tp_carousel_template', 'light', 500, 'abf-stream__trustbox');

$abf_accounting_inner = abf_accounting_block_group(
    'abf-stream__intro',
    abf_accounting_block_h(2, 'abf-stream__title', __('Trusted by UK <br>businesses.', 'abf-accounting'))
    . $abf_accounting_badge
    . abf_accounting_block_p('abf-stream__text', __('Real reviews from ABF customers, written on Trustpilot, an independent review site.', 'abf-accounting'))
    . abf_accounting_block_p('abf-stream__link', abf_accounting_trustpilot_link('abf-tp-link'))
)
    . ($abf_accounting_carousel ? abf_accounting_block_group('abf-stream__widget', abf_accounting_block_html($abf_accounting_carousel)) : '');

echo abf_accounting_block_section('abf-stream ' . ($abf_accounting_carousel ? 'abf-stream--widget' : 'abf-stream--link'), $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
