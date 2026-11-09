<?php
/**
 * Title: Home — What ABF takes care of
 * Slug: abf-accounting/home-takes-care
 * Categories: abf-sections
 * Description: Teal-to-green band with the message on the left and three numbered glass points on the right.
 *
 * @package ABF_Accounting
 */

$abf_accounting_points = array(
    array('01', 'icon-dark-corporation-tax.svg', __('Tax and filings, handled', 'abf-accounting'), __('Support for Self Assessment, Corporation Tax and other filing obligations, with clear deadlines.', 'abf-accounting')),
    array('02', 'icon-dark-confirmation-statement.svg', __('No last-minute surprises', 'abf-accounting'), __('Deadline reminders and responsive support help you stay ahead instead of reacting at the last minute.', 'abf-accounting')),
    array('03', 'icon-dark-bookkeeping.svg', __('Your records, kept organised', 'abf-accounting'), __('Bookkeeping support keeps your numbers clear and your records ready for the next filing.', 'abf-accounting')),
);

$abf_accounting_list = '';
foreach ($abf_accounting_points as [$abf_accounting_num, $abf_accounting_icon, $abf_accounting_title, $abf_accounting_text]) {
    $abf_accounting_list .= abf_accounting_block_group(
        'abf-care__point',
        abf_accounting_block_p('abf-care__num', $abf_accounting_num)
        . abf_accounting_block_image('abf-care__icon', get_theme_file_uri('assets/icons/' . $abf_accounting_icon))
        . abf_accounting_block_group(
            'abf-care__copy',
            abf_accounting_block_h(3, 'abf-care__title', $abf_accounting_title)
            . abf_accounting_block_p('abf-care__text', $abf_accounting_text)
        )
    );
}

$abf_accounting_inner = abf_accounting_block_group(
    'abf-care__message',
    abf_accounting_block_p('abf-eyebrow abf-eyebrow--caps', __('What ABF takes care of', 'abf-accounting'))
    . abf_accounting_block_h(2, 'abf-care__heading', __('Everything you need, <br>explained clearly.', 'abf-accounting'))
    . abf_accounting_block_p('abf-care__lead', __('ABF brings the key accounting tasks together, so you always know what is happening, what is due and what comes next.', 'abf-accounting'))
)
    . abf_accounting_block_group('abf-care__points', $abf_accounting_list);

echo abf_accounting_block_section('abf-care abf-section--dark', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
