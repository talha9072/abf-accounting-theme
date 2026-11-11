<?php
/**
 * Title: Home — How ABF works
 * Slug: abf-accounting/home-how-it-works
 * Categories: abf-sections
 * Description: Heading row and four feature cards with a gradient "included" panel, then Request a callback.
 *
 * @package ABF_Accounting
 */

$abf_accounting_features = array(
    array('clock', __('Quick <br>turnaround', 'abf-accounting'), array(__('No last-minute panic', 'abf-accounting'), __('Deadline reminders included', 'abf-accounting'), __('Filings kept on track', 'abf-accounting'))),
    array('laptop', __('Digital-first <br>service', 'abf-accounting'), array(__('Share documents online', 'abf-accounting'), __('Records kept in one place', 'abf-accounting'), __('Approve drafts digitally', 'abf-accounting'))),
    array('user', __('Dedicated <br>accountant', 'abf-accounting'), array(__('One consistent point of contact', 'abf-accounting'), __('Support by phone and email', 'abf-accounting'), __('Knows your business and deadlines', 'abf-accounting'))),
    array('flex', __('Fixed, <br>transparent prices', 'abf-accounting'), array(__('Prices shown up front', 'abf-accounting'), __('No hidden fees', 'abf-accounting'), __('Scope agreed before work starts', 'abf-accounting'))),
);

$abf_accounting_cards = '';
foreach ($abf_accounting_features as [$abf_accounting_icon, $abf_accounting_title, $abf_accounting_ticks]) {
    $abf_accounting_cards .= abf_accounting_block_group(
        'abf-feature',
        abf_accounting_block_group(
            'abf-feature__top',
            abf_accounting_block_group('abf-feature__icon', abf_accounting_block_image('abf-feature__icon-img', get_theme_file_uri('assets/icons/' . $abf_accounting_icon . '.svg')))
            . abf_accounting_block_h(3, 'abf-feature__title', $abf_accounting_title)
        )
        . abf_accounting_block_list('abf-feature__ticks', $abf_accounting_ticks)
    );
}

$abf_accounting_inner = abf_accounting_block_group(
    'abf-how__header',
    abf_accounting_heading(__('How ABF works', 'abf-accounting'), __('Quick, digital and personal accounting support.', 'abf-accounting'), '', 'abf-heading--caps')
    . abf_accounting_block_p('abf-how__lead', __('We keep your accounts and returns moving, so deadlines don’t turn into a last-minute panic. Choose the service you need and talk to your accountant whenever you need.', 'abf-accounting'))
)
    . abf_accounting_block_group('abf-how__cards', $abf_accounting_cards)
    . abf_accounting_block_buttons('abf-how__actions', array(array(__('Request a callback', 'abf-accounting'), home_url('/contact/'), 'abf-button--phone')));

echo abf_accounting_block_section('abf-how', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
