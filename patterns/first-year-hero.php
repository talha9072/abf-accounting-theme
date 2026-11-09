<?php
/**
 * Title: First-Year FREE — Hero and price card
 * Slug: abf-accounting/first-year-hero
 * Categories: abf-sections
 * Description: Breadcrumb, "Your first year’s accounts & tax return — FREE", buttons, assurances and the dark £120 offer card.
 *
 * The three "Claim" buttons are not linked to a checkout yet (Figma note: waiting on Ashley); they go to Contact.
 *
 * @package ABF_Accounting
 */

$abf_accounting_claim = abf_accounting_buy_url('first-year-offer');

$abf_accounting_message = abf_accounting_block_p('abf-breadcrumb', '<a href="' . esc_url(home_url('/first-year-accounts-free/')) . '">' . esc_html__('First-Year Accounts', 'abf-accounting') . '</a><span aria-hidden="true">/</span><span aria-current="page">' . esc_html__('New business welcome offer', 'abf-accounting') . '</span>')
    . abf_accounting_block_h(1, 'abf-page-title', __('Your first year’s accounts <br>&amp; tax return — FREE.', 'abf-accounting'))
    . abf_accounting_block_p('abf-offer-hero__price', __('You pay only a <span class="abf-text-green">one-off set-up fee of £120</span>', 'abf-accounting'))
    . abf_accounting_block_p('abf-page-lead', __('Recently registered your company? Get your first year’s annual accounts and Corporation Tax return prepared for FREE when you subscribe to ABF Accounting within 30 days of registration.', 'abf-accounting'))
    . abf_accounting_block_buttons(
        'abf-offer-hero__actions',
        array(
            array(__('Claim your free first-year accounts', 'abf-accounting'), $abf_accounting_claim, 'abf-button--lg'),
            array(__('Request a callback', 'abf-accounting'), home_url('/contact/'), 'abf-button--phone abf-button--outline-green'),
        )
    )
    . abf_accounting_block_list('abf-assurances', array(__('Subscribe within 30 days', 'abf-accounting'), __('£120 set-up fee', 'abf-accounting'), __('UK limited companies', 'abf-accounting')));

$abf_accounting_card = abf_accounting_block_group(
    'abf-price-card__head',
    abf_accounting_block_group(
        'abf-price-card__title-row',
        abf_accounting_block_image('abf-price-card__icon', get_theme_file_uri('assets/icons/dark-self-assessment.svg'))
        . abf_accounting_block_p('abf-price-card__label', __('New business offer', 'abf-accounting'))
    )
    . abf_accounting_block_p('abf-price-card__price', '<span>' . esc_html__('only', 'abf-accounting') . '</span> <strong>£120</strong> <span>' . esc_html__('one-off set-up fee', 'abf-accounting') . '</span>')
    . abf_accounting_block_p('abf-price-card__who', __('Subscribe within 30 days of company registration.', 'abf-accounting'))
)
    . abf_accounting_block_list(
        'abf-price-card__list',
        array(
            __('Annual accounts preparation — FREE for year one', 'abf-accounting'),
            __('Corporation Tax return (CT600) — FREE for year one', 'abf-accounting'),
            __('Client onboarding &amp; account set-up — £120', 'abf-accounting'),
            __('Dedicated accountant from day one', 'abf-accounting'),
        )
    )
    . abf_accounting_block_group(
        'abf-price-card__foot',
        abf_accounting_block_buttons('abf-price-card__actions', array(array(__('Claim your offer', 'abf-accounting'), $abf_accounting_claim, 'abf-button--block')))
        . abf_accounting_block_p('abf-price-card__small', __('Terms and conditions apply. The £120 set-up fee is payable upfront.', 'abf-accounting'))
    );

echo abf_accounting_block_section( // phpcs:ignore WordPress.Security.EscapeOutput
    'abf-offer-hero',
    abf_accounting_block_group('abf-offer-hero__message', $abf_accounting_message)
    . abf_accounting_block_group('abf-price-card', $abf_accounting_card)
);
