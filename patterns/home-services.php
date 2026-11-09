<?php
/**
 * Title: Home — Accounting services
 * Slug: abf-accounting/home-services
 * Categories: abf-sections
 * Description: Intro with the "06" core-areas count and a 2×3 grid of service cards.
 *
 * @package ABF_Accounting
 */

$abf_accounting_all   = abf_accounting_services();
$abf_accounting_cards = array(
    array('bookkeeping-services', 'svc-bookkeeping-services.svg', __('Clean books, organised records and a clearer view of your numbers.', 'abf-accounting')),
    array('payroll-and-pension', 'svc-payroll-and-pension.svg', __('Payroll and pension contributions handled with less admin.', 'abf-accounting')),
    array('annual-accounts-tax-return', 'svc-annual-accounts-tax-return.svg', __('Year-end accounts prepared and filed with the right authorities.', 'abf-accounting')),
    array('confirmation-statement', 'svc-confirmation-statement.svg', __('Your annual Confirmation Statement (CS01) filed with Companies House.', 'abf-accounting')),
    array('vat-return', 'svc-vat-return.svg', __('VAT returns, MTD-ready workflows and deadline support.', 'abf-accounting')),
    array('self-assessment-tax-return', 'svc-self-assessment-tax-return.svg', __('Personal tax returns for directors, landlords and self-employed clients.', 'abf-accounting')),
);

$abf_accounting_grid = '';
foreach ($abf_accounting_cards as $abf_accounting_i => [$abf_accounting_slug, $abf_accounting_icon, $abf_accounting_text]) {
    $abf_accounting_service = $abf_accounting_all[$abf_accounting_slug];
    $abf_accounting_grid   .= abf_accounting_block_group(
        'abf-service-card',
        abf_accounting_block_group(
            'abf-service-card__body',
            abf_accounting_block_group(
                'abf-service-card__head',
                abf_accounting_block_image('abf-service-card__icon', get_theme_file_uri('assets/icons/' . $abf_accounting_icon))
                . abf_accounting_block_h(3, 'abf-service-card__title', esc_html($abf_accounting_service['name']))
                . abf_accounting_block_p('abf-service-card__num', sprintf('%02d', $abf_accounting_i + 1))
            )
            . abf_accounting_block_p('abf-service-card__text', $abf_accounting_text)
        )
        . abf_accounting_block_p(
            'abf-service-card__link',
            '<a href="' . esc_url($abf_accounting_service['url']) . '">' . esc_html__('Find out more', 'abf-accounting') . '<span class="screen-reader-text"> ' . esc_html__('about', 'abf-accounting') . ' ' . esc_html($abf_accounting_service['name']) . '</span></a>'
        )
    );
}

$abf_accounting_inner = abf_accounting_block_group(
    'abf-services__intro',
    abf_accounting_heading(
        __('Accounting services', 'abf-accounting'),
        __('Everything your business needs, without the accounting maze.', 'abf-accounting'),
        __('Choose the support you need now and add more as your business grows.', 'abf-accounting')
    )
    . abf_accounting_block_group(
        'abf-services__count',
        abf_accounting_block_p('abf-services__count-num', '06')
        . abf_accounting_block_p('abf-services__count-label', __('Core accounting areas', 'abf-accounting'))
        . abf_accounting_block_p('abf-services__count-note', __('Structured around the work businesses need to stay organised, compliant and moving.', 'abf-accounting'))
    )
)
    . abf_accounting_block_group('abf-services__grid', $abf_accounting_grid);

echo abf_accounting_block_section('abf-services', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
