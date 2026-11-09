<?php
/**
 * Title: Home — Limited company year
 * Slug: abf-accounting/home-company-year
 * Categories: abf-sections
 * Description: "One company. Several filing responsibilities." with four gradient stage cards and the company-year timeline.
 *
 * @package ABF_Accounting
 */

$abf_accounting_stages = array(
    array('1', 'stage-bookkeeping.svg', __('Bookkeeping &amp; records', 'abf-accounting'), __('Bookkeeping and records stay organised.', 'abf-accounting'), __('All year round', 'abf-accounting')),
    array('2', 'stage-annual-accounts.svg', __('Annual accounts', 'abf-accounting'), __('Year-end accounts are prepared.', 'abf-accounting'), __('After your year end', 'abf-accounting')),
    array('3', 'stage-corporation-tax.svg', __('Corporation Tax', 'abf-accounting'), __('CT600 and tax calculations are filed.', 'abf-accounting'), __('After your year end', 'abf-accounting')),
    array('4', 'stage-confirmation-statement.svg', __('Confirmation Statement', 'abf-accounting'), __('Companies House information stays current.', 'abf-accounting'), __('Once every 12 months', 'abf-accounting')),
);

$abf_accounting_cards = '';
foreach ($abf_accounting_stages as [$abf_accounting_num, $abf_accounting_icon, $abf_accounting_title, $abf_accounting_text, $abf_accounting_when]) {
    $abf_accounting_cards .= abf_accounting_block_group(
        'abf-stage',
        abf_accounting_block_group(
            'abf-stage__card',
            abf_accounting_block_p('abf-stage__num', $abf_accounting_num)
            . abf_accounting_block_image('abf-stage__icon', get_theme_file_uri('assets/icons/' . $abf_accounting_icon))
            . abf_accounting_block_h(3, 'abf-stage__title', $abf_accounting_title)
            . abf_accounting_block_p('abf-stage__text', $abf_accounting_text)
        )
        . abf_accounting_block_p('abf-stage__when', $abf_accounting_when)
    );
}

$abf_accounting_inner = abf_accounting_block_group(
    'abf-year__intro',
    abf_accounting_heading(
        __('Limited company accounts', 'abf-accounting'),
        __('One company. <span class="abf-text-green">Several filing responsibilities.</span> One accounting team.', 'abf-accounting'),
        __('Dedicated support for bookkeeping, year-end accounts, Corporation Tax and Companies House filings — with clear pricing and one point of contact.', 'abf-accounting')
    )
    . abf_accounting_block_p('abf-year__context', __('A clear filing journey for the recurring work around a limited company.', 'abf-accounting'))
)
    . abf_accounting_block_group(
        'abf-year__journey',
        abf_accounting_block_p('abf-eyebrow', __('Your company year', 'abf-accounting'))
        . abf_accounting_block_h(3, 'abf-year__heading', __('Know what is due, what ABF is handling and what needs your input.', 'abf-accounting'))
        . abf_accounting_block_group('abf-year__stages', $abf_accounting_cards)
        . abf_accounting_block_p('abf-year__note', __('VAT, payroll and other filing responsibilities can be added where they apply to your business.', 'abf-accounting'))
    );

echo abf_accounting_block_section('abf-year', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
