<?php
/**
 * Title: Home — Useful resources
 * Slug: abf-accounting/home-resources
 * Categories: abf-sections
 * Description: Featured First-Year offer card with photo, and "Explore more" rows (FAQ, Pricing).
 *
 * @package ABF_Accounting
 */

$abf_accounting_row = static fn (string $eyebrow, string $title, string $text, string $url): string => abf_accounting_block_group(
    'abf-explore-row',
    abf_accounting_block_p('abf-explore-row__eyebrow', $eyebrow)
    . abf_accounting_block_h(3, 'abf-explore-row__title', '<a href="' . esc_url($url) . '">' . $title . '</a>')
    . abf_accounting_block_p('abf-explore-row__text', $text)
);

$abf_accounting_inner = abf_accounting_heading(
    __('Useful resources', 'abf-accounting'),
    __('Useful resources for clearer accounting decisions.', 'abf-accounting'),
    __('Existing ABF resources to help you compare support, understand common questions and choose the right next step.', 'abf-accounting')
)
    . abf_accounting_block_group(
        'abf-resources__grid',
        abf_accounting_block_group(
            'abf-feature-resource',
            abf_accounting_block_image('abf-feature-resource__photo', abf_accounting_img('home-latest-insights-and-resources-smiling-woman-in-cafe.webp'), __('Smiling woman working on a laptop in a café', 'abf-accounting'))
            . abf_accounting_block_group(
                'abf-feature-resource__body',
                abf_accounting_block_p('abf-feature-resource__eyebrow', __('New business offer', 'abf-accounting'))
                . abf_accounting_block_h(3, 'abf-feature-resource__title', __('First-Year Accounts <br>&amp; Tax Return FREE', 'abf-accounting'))
                . abf_accounting_block_p('abf-feature-resource__text', __('Recently registered your company? Get your first year’s accounts and Corporation Tax return prepared for FREE. Only a £120 set-up fee applies.', 'abf-accounting'))
                . abf_accounting_block_p('abf-feature-resource__link', '<a href="' . esc_url(home_url('/first-year-accounts-free/')) . '">' . esc_html__('Explore the offer', 'abf-accounting') . '</a>')
            )
        )
        . abf_accounting_block_group(
            'abf-explore',
            abf_accounting_block_p('abf-explore__heading', __('Explore more', 'abf-accounting'))
            . $abf_accounting_row(__('FAQ', 'abf-accounting'), __('Frequently Asked Questions', 'abf-accounting'), __('Limited Company, Sole Trader, Partnership, LLP and Individual questions in one place.', 'abf-accounting'), home_url('/#faq'))
            . $abf_accounting_row(__('Pricing', 'abf-accounting'), __('Find the support that fits', 'abf-accounting'), __('Clear, fixed prices for every service.', 'abf-accounting'), home_url('/pricing/'))
        )
    );

echo abf_accounting_block_section('abf-resources', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
