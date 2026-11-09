<?php
/**
 * Title: First-Year FREE — How the offer works and who it is for
 * Slug: abf-accounting/first-year-steps
 * Categories: abf-sections
 * Description: Four-step timeline and the "Starter support for new businesses" audience cards.
 *
 * @package ABF_Accounting
 */

$abf_accounting_audiences = abf_accounting_audiences();

echo abf_accounting_timeline( // phpcs:ignore WordPress.Security.EscapeOutput
    __('How it works', 'abf-accounting'),
    __('How the welcome offer works.', 'abf-accounting'),
    array(
        array(__('Register your company', 'abf-accounting'), __('Your company is incorporated with Companies House.', 'abf-accounting')),
        array(__('Subscribe within 30 days', 'abf-accounting'), __('Join ABF’s annual accounts and tax return service and pay the £120 set-up fee.', 'abf-accounting')),
        array(__('Share your records', 'abf-accounting'), __('Keep your records with us through your first accounting year.', 'abf-accounting')),
        array(__('First-year filings, FREE', 'abf-accounting'), __('Your first annual accounts and CT600 are prepared at no charge.', 'abf-accounting')),
    )
);

// "View Company Formation" has no page yet (client decision pending), so that card has no link.
echo abf_accounting_audience_cards( // phpcs:ignore WordPress.Security.EscapeOutput
    __('Who it is for', 'abf-accounting'),
    __('Starter support for new businesses.', 'abf-accounting'),
    array(
        array(__('New limited companies', 'abf-accounting'), __('Your first Confirmation Statement, accounts and tax return covered.', 'abf-accounting'), __('View limited company support', 'abf-accounting'), $abf_accounting_audiences['limited-companies']['url']),
        array(__('Company formation', 'abf-accounting'), __('Not registered yet? Start with company formation.', 'abf-accounting'), '', ''),
        array(__('Contractors', 'abf-accounting'), __('Setting up a company for your contracting work.', 'abf-accounting'), __('View contractor support', 'abf-accounting'), $abf_accounting_audiences['contractors']['url']),
        array(__('Growing businesses', 'abf-accounting'), __('Compare our services and prices.', 'abf-accounting'), __('View pricing', 'abf-accounting'), home_url('/pricing/')),
    )
);
