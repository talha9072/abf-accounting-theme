<?php
/**
 * Title: First-Year FREE — Why claim the offer
 * Slug: abf-accounting/first-year-why
 * Categories: abf-sections
 * Description: "Start your company year on the right footing." with six icon cards.
 *
 * @package ABF_Accounting
 */

echo abf_accounting_block_section( // phpcs:ignore WordPress.Security.EscapeOutput
    'abf-why',
    abf_accounting_heading(
        __('Why claim the offer', 'abf-accounting'),
        __('Start your company year <br>on the right footing.', 'abf-accounting'),
        __('Get your first-year filings handled properly, with an accountant on your side from the start.', 'abf-accounting'),
        'abf-heading--caps'
    )
    . abf_accounting_icon_cards(
        array(
            array('why-1', __('Avoid late penalties', 'abf-accounting'), __('Deal with the required filings before they become a last-minute problem.', 'abf-accounting')),
            array('why-2', __('Stay compliant', 'abf-accounting'), __('Keep your Companies House and HMRC obligations on your radar.', 'abf-accounting')),
            array('why-3', __('Handled professionally', 'abf-accounting'), __('Use ABF filing support instead of working through the admin alone.', 'abf-accounting')),
            array('why-4', __('Clear fixed prices', 'abf-accounting'), __('Published prices shown up front, so you know the cost before you start.', 'abf-accounting')),
            array('why-5', __('A dedicated accountant', 'abf-accounting'), __('One accountant who knows your new company from the start.', 'abf-accounting')),
            array('why-6', __('Room to grow', 'abf-accounting'), __('Add bookkeeping, VAT and payroll support when you are ready.', 'abf-accounting')),
        )
    )
);
