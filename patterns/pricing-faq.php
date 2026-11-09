<?php
/**
 * Title: Pricing — FAQ
 * Slug: abf-accounting/pricing-faq
 * Categories: abf-sections
 * Description: "Pricing questions, answered." Only the first answer is in Figma; the others are drafted for the client to check.
 *
 * @package ABF_Accounting
 */

echo abf_accounting_faq( // phpcs:ignore WordPress.Security.EscapeOutput
    array(
        array(__('Are there any extra charges on top of these prices?', 'abf-accounting'), __('No. The prices shown are what you pay. Additional fees may only apply for complex returns, such as foreign income or capital gains.', 'abf-accounting')),
        array(__('What happens after I click Get started?', 'abf-accounting'), __('Your accountant gets in touch to confirm what we need from you and when your filing is due, then the work starts.', 'abf-accounting')),
        array(__('How long does each service take?', 'abf-accounting'), __('Most services take 1–5 business days once we have all the information we need. Each package shows its typical timescale.', 'abf-accounting')),
        array(__('Are there any hidden fees?', 'abf-accounting'), __('No. The price is agreed before any work starts. If your return needs specialist work, we tell you the cost first.', 'abf-accounting')),
        array(__('Can I get help with more than one service?', 'abf-accounting'), __('Yes. Many clients combine accounts, the Confirmation Statement and Self Assessment, and one accountant can look after all of them.', 'abf-accounting')),
        array(__('What if my return is complex?', 'abf-accounting'), __('Tell us about it before you start. We review your situation and confirm any additional fee before we do the work.', 'abf-accounting')),
    ),
    array(
        'eyebrow' => __('Common questions', 'abf-accounting'),
        'title'   => __('Pricing questions, answered.', 'abf-accounting'),
        'intro'   => __('Straight answers on prices, VAT, switching plans and what is included.', 'abf-accounting'),
    )
);
