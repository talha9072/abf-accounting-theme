<?php
/**
 * Title: About — Hero, who we are and how we work
 * Slug: abf-accounting/about-intro
 * Categories: abf-sections
 * Description: Centred hero, "Who we are" photo and text, and the dark "How we work with every client" values.
 *
 * @package ABF_Accounting
 */

$abf_accounting_values = array(
    array('about-value-1', __('Hassle-free process', 'abf-accounting'), __('We make the accounting process simple, saving you time and effort.', 'abf-accounting')),
    array('about-value-2', __('Affordable, transparent pricing', 'abf-accounting'), __('Clear pricing with no hidden fees or surprise costs.', 'abf-accounting')),
    array('about-value-3', __('Quick turnaround', 'abf-accounting'), __('We work fast and efficiently, so deadlines never become last-minute panic.', 'abf-accounting')),
    array('about-value-4', __('Expert support, tailored to you', 'abf-accounting'), __('Guidance, strategy or hands-on help, designed around your goals.', 'abf-accounting')),
);
$abf_accounting_list   = '';
foreach ($abf_accounting_values as [$abf_accounting_icon, $abf_accounting_title, $abf_accounting_text]) {
    $abf_accounting_list .= abf_accounting_block_group(
        'abf-value',
        abf_accounting_block_image('abf-value__icon', get_theme_file_uri('assets/icons/' . $abf_accounting_icon . '.svg'))
        . abf_accounting_block_h(3, 'abf-value__title', $abf_accounting_title)
        . abf_accounting_block_p('abf-value__text', $abf_accounting_text)
    );
}

echo abf_accounting_block_section( // phpcs:ignore WordPress.Security.EscapeOutput
    'abf-about-hero',
    abf_accounting_block_p('abf-eyebrow abf-eyebrow--caps', __('About ABF', 'abf-accounting'))
    . abf_accounting_block_h(1, 'abf-page-title', __('Accounting support built around <br>real people.', 'abf-accounting'))
    . abf_accounting_block_p('abf-page-lead', __('ABF Accounting is a full-service accounting firm based in Luton, providing tailored financial support for entrepreneurs, small businesses and individuals across the UK.', 'abf-accounting'))
);

echo abf_accounting_block_section( // phpcs:ignore WordPress.Security.EscapeOutput
    'abf-who',
    abf_accounting_block_image('abf-who__photo', abf_accounting_img('about-who-we-are-smiling-woman-accountant.webp'), __('Smiling accountant at her desk', 'abf-accounting'))
    . abf_accounting_block_group(
        'abf-who__text',
        abf_accounting_heading(
            __('Who we are', 'abf-accounting'),
            __('Clarity, compliance and confidence in every financial decision.', 'abf-accounting'),
            __('Whether you are a startup working through your first filings or an established business looking to stay on top of tax, we give you a dedicated accountant and a clear plan so you can focus on what matters most.', 'abf-accounting'),
            'abf-heading--caps'
        )
        . abf_accounting_block_p('abf-who__note', __('ABF Accounting Services Ltd is registered in England and Wales (company number 15299101), with its registered office in Luton.', 'abf-accounting'))
    )
);

echo abf_accounting_block_section( // phpcs:ignore WordPress.Security.EscapeOutput
    'abf-values abf-section--dark',
    abf_accounting_heading(__('Why choose ABF', 'abf-accounting'), __('How we work with every client.', 'abf-accounting'), '', 'abf-heading--caps abf-heading--center abf-heading--light')
    . abf_accounting_block_group('abf-values__list', $abf_accounting_list)
);
