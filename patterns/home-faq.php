<?php
/**
 * Title: Home — FAQ
 * Slug: abf-accounting/home-faq
 * Categories: abf-sections
 * Description: "Questions people ask before switching": intro with the Still deciding card, and the accordion.
 *
 * Only the first answer is written in Figma; the others are drafted from the agreed prices and
 * service list and need the client's check.
 *
 * @package ABF_Accounting
 */

$abf_accounting_c = abf_accounting_contact();

echo abf_accounting_faq( // phpcs:ignore WordPress.Security.EscapeOutput
    array(
        array(__('Can I switch to ABF from my current accountant?', 'abf-accounting'), __('Yes. We explain what we need from you and your previous accountant, confirm the scope and price, and handle the handover with you so nothing gets missed.', 'abf-accounting')),
        array(__('What is included in each service?', 'abf-accounting'), __('Each service page lists what we prepare, what we file and what we need from you. The scope is confirmed with you before any work starts.', 'abf-accounting')),
        array(__('Will I have a dedicated accountant?', 'abf-accounting'), __('Yes. One accountant looks after your accounts, so you have a consistent point of contact by phone and email.', 'abf-accounting')),
        array(__('How much do your services cost?', 'abf-accounting'), __('Prices are fixed and shown up front: company accounts and tax return from £299, Self Assessment £150, dormant accounts £80 and the Confirmation Statement £75. See the Pricing page for every service.', 'abf-accounting')),
        array(__('What do I need to send you to get started?', 'abf-accounting'), __('Your company or personal details and the records for the period, such as bank statements, sales and expenses. We send you a clear list once we confirm the service.', 'abf-accounting')),
        array(__('Do you work with businesses outside Luton?', 'abf-accounting'), __('Yes. ABF works online with businesses across the UK, with support by phone and email.', 'abf-accounting')),
    ),
    array(
        'eyebrow' => __('Common questions', 'abf-accounting'),
        'title'   => __('Questions people ask before switching.', 'abf-accounting'),
        'intro'   => __('Straight answers on how ABF works, what is included and what happens once you get in touch.', 'abf-accounting'),
    )
);
