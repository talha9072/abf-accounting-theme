<?php
/**
 * Title: First-Year FREE — FAQ
 * Slug: abf-accounting/first-year-faq
 * Categories: abf-sections
 * Description: "Welcome offer questions, answered." Only the first answer is in Figma; the others follow the offer terms and need the client's check.
 *
 * @package ABF_Accounting
 */

echo abf_accounting_faq( // phpcs:ignore WordPress.Security.EscapeOutput
    array(
        array(__('Who qualifies for the offer?', 'abf-accounting'), __('UK limited companies that subscribe to ABF Accounting’s annual accounts and Corporation Tax return service within 30 days of their incorporation date.', 'abf-accounting')),
        array(__('What does the £120 set-up fee cover?', 'abf-accounting'), __('Client onboarding and setting up your account, including your dedicated accountant. It is a one-off fee, payable upfront when you subscribe.', 'abf-accounting')),
        array(__('What is prepared for free?', 'abf-accounting'), __('Your first year’s annual accounts and your first Corporation Tax return (CT600).', 'abf-accounting')),
        array(__('What if I registered more than 30 days ago?', 'abf-accounting'), __('The offer is only for companies that subscribe within 30 days of incorporation, but our standard company accounts package is £299. Get in touch and we will help you choose.', 'abf-accounting')),
        array(__('Are filing fees included?', 'abf-accounting'), __('Other services and any third-party filing fees may be charged separately. We tell you about any fee before it applies.', 'abf-accounting')),
        array(__('Can I add other services?', 'abf-accounting'), __('Yes. You can add bookkeeping, VAT and payroll support whenever you are ready.', 'abf-accounting')),
    ),
    array(
        'eyebrow' => __('Common questions', 'abf-accounting'),
        'title'   => __('Welcome offer questions, answered.', 'abf-accounting'),
        'intro'   => __('Straight answers on who qualifies, what is free and what you pay.', 'abf-accounting'),
    )
);
