<?php
/**
 * Title: Trustpilot reviews
 * Slug: abf-accounting/home-trust-stream
 * Categories: abf-sections
 * Description: Figma "Section / Trustpilot Reviews" (19 Nov 2026): "Trusted by UK businesses." with a "Read our reviews on Trustpilot" button, and a "Worked with ABF?" card with Trustpilot's free "Review us on Trustpilot" TrustBox. No typed rating or copied reviews (client).
 *
 * @package ABF_Accounting
 */

$abf_accounting_inner = abf_accounting_block_group(
    'abf-tp__intro',
    abf_accounting_block_h(2, 'abf-tp__title', __('Trusted by UK <br>businesses.', 'abf-accounting'))
    . abf_accounting_block_p('abf-tp__text', __('Read what ABF customers say about us on Trustpilot, the independent review site.', 'abf-accounting'))
    . abf_accounting_block_p('abf-tp__link', abf_accounting_trustpilot_link('abf-tp-link'))
)
    . abf_accounting_block_group(
        'abf-tp__card',
        abf_accounting_block_p('abf-tp__eyebrow', __('Worked with ABF?', 'abf-accounting'))
        . abf_accounting_block_h(3, 'abf-tp__card-title', __('Tell others how it went.', 'abf-accounting'))
        . abf_accounting_block_p('abf-tp__card-text', __('Your review helps other business owners choose the right accountant. It only takes a minute.', 'abf-accounting'))
        . abf_accounting_block_html(abf_accounting_trustpilot_collector())
    );

echo abf_accounting_block_section('abf-tp', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
