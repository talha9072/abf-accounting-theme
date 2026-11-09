<?php
/**
 * Title: First-Year FREE — What’s included
 * Slug: abf-accounting/first-year-included
 * Categories: abf-sections
 * Description: Centred intro, three offer items (FREE, FREE, £120), "Who qualifies?" and the dark "head start" card, then terms.
 *
 * @package ABF_Accounting
 */

$abf_accounting_items = array(
    array('light-annual-accounts', __('FREE', 'abf-accounting'), __('Annual Accounts Preparation', 'abf-accounting'), __('Your first year’s annual accounts, prepared by your accountant.', 'abf-accounting'), false),
    array('light-corporation-tax', __('FREE', 'abf-accounting'), __('Corporation Tax Return (CT600) Preparation', 'abf-accounting'), __('Your first Corporation Tax return, prepared from the same figures.', 'abf-accounting'), false),
    array('dark-confirmation-statement', '£120', __('Client Onboarding &amp; Account Setup', 'abf-accounting'), __('A one-off set-up fee, payable upfront when you subscribe.', 'abf-accounting'), true),
);
$abf_accounting_list = '';
foreach ($abf_accounting_items as [$abf_accounting_icon, $abf_accounting_tag, $abf_accounting_title, $abf_accounting_text, $abf_accounting_dark]) {
    $abf_accounting_list .= abf_accounting_block_group(
        'abf-offer-item' . ($abf_accounting_dark ? ' is-dark' : ''),
        abf_accounting_block_group(
            'abf-offer-item__head',
            abf_accounting_block_image('abf-offer-item__icon', get_theme_file_uri('assets/icons/' . $abf_accounting_icon . '.svg'))
            . abf_accounting_block_p('abf-offer-item__tag', esc_html($abf_accounting_tag))
        )
        . abf_accounting_block_h(3, 'abf-offer-item__title', $abf_accounting_title)
        . abf_accounting_block_p('abf-offer-item__text', $abf_accounting_text)
    );
}

$abf_accounting_inner = abf_accounting_block_group(
    'abf-included__intro',
    abf_accounting_block_p('abf-pill abf-pill--mint', __('New business welcome offer', 'abf-accounting'))
    . abf_accounting_block_h(2, 'abf-title', __('What’s included?', 'abf-accounting'))
    . abf_accounting_block_p('abf-intro', __('At ABF Accounting Services Ltd, we want to help new businesses get off to a strong start. Subscribe to our annual accounts and tax return service within 30 days of your company’s registration date, and we’ll prepare your first year’s annual accounts and Corporation Tax return at no charge.', 'abf-accounting'))
)
    . abf_accounting_block_group('abf-included__items', $abf_accounting_list)
    . abf_accounting_block_group(
        'abf-included__pair',
        abf_accounting_block_group(
            'abf-qualify',
            abf_accounting_block_h(3, 'abf-qualify__title', __('Who qualifies?', 'abf-accounting'))
            . abf_accounting_block_p('abf-qualify__text', __('UK limited companies that subscribe to ABF Accounting’s annual accounts and Corporation Tax return service within 30 days of their incorporation date.', 'abf-accounting'))
        )
        . abf_accounting_block_group(
            'abf-qualify is-dark',
            abf_accounting_block_h(3, 'abf-qualify__title', __('Give your business a head start', 'abf-accounting'))
            . abf_accounting_block_p('abf-qualify__text', __('Enjoy professional accounting support from day one, with no charge for preparing your first year’s annual accounts and Corporation Tax return.', 'abf-accounting'))
            . abf_accounting_block_buttons('abf-qualify__actions', array(array(__('Claim your free first-year accounts', 'abf-accounting'), abf_accounting_buy_url('first-year-offer'))))
        )
    )
    . abf_accounting_block_p('abf-included__terms', __('Terms and conditions apply. The £120 setup fee is payable upfront. The offer covers preparation of the annual accounts and Corporation Tax return for the first accounting year; other services and applicable third-party filing fees may be charged separately.', 'abf-accounting'));

echo abf_accounting_block_section('abf-included', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
