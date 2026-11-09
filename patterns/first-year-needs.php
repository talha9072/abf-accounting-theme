<?php
/**
 * Title: First-Year FREE — What a new company needs
 * Slug: abf-accounting/first-year-needs
 * Categories: abf-sections
 * Description: "First-year compliance" checklist card beside "What a new company needs in its first year."
 *
 * @package ABF_Accounting
 */

$abf_accounting_rows = array(
    array(__('Confirmation Statement', 'abf-accounting'), 77),
    array(__('Annual accounts', 'abf-accounting'), 56),
    array(__('Corporation Tax return', 'abf-accounting'), 42),
    array(__('Key deadlines in view', 'abf-accounting'), 63),
);
$abf_accounting_list = '';
foreach ($abf_accounting_rows as [$abf_accounting_label, $abf_accounting_pct]) {
    $abf_accounting_list .= abf_accounting_block_p('abf-checklist__row', esc_html($abf_accounting_label) . '<span class="abf-checklist__track" aria-hidden="true"><span style="width:' . (int) $abf_accounting_pct . '%"></span></span>');
}

$abf_accounting_visual = abf_accounting_block_group(
    'abf-checklist',
    abf_accounting_block_group(
        'abf-checklist__head',
        abf_accounting_block_image('abf-checklist__icon', get_theme_file_uri('assets/icons/light-confirmation-statement.svg'))
        . abf_accounting_block_group(
            'abf-checklist__title',
            abf_accounting_block_p('abf-checklist__name', __('First-year compliance', 'abf-accounting'))
            . abf_accounting_block_p('abf-checklist__sub', __('Your essentials checklist', 'abf-accounting'))
        )
        . abf_accounting_block_p('abf-checklist__chip', __('Covered', 'abf-accounting'))
    )
    . $abf_accounting_list
);

echo abf_accounting_block_section( // phpcs:ignore WordPress.Security.EscapeOutput
    'abf-needs',
    abf_accounting_block_group('abf-needs__visual', $abf_accounting_visual)
    . abf_accounting_block_group(
        'abf-needs__text',
        abf_accounting_heading(
            __('Your first year', 'abf-accounting'),
            __('What a new company needs <br>in its first year.', 'abf-accounting'),
            __('Once your company is registered, the filing obligations start. A new company needs to keep its Companies House record current and file annual accounts and a Corporation Tax return.', 'abf-accounting'),
            'abf-heading--caps'
        )
        . abf_accounting_block_p('abf-needs__more', __('Our welcome offer covers your first annual accounts and Corporation Tax return, so you can focus on getting your business started.', 'abf-accounting'))
    )
);
