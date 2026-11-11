<?php
/**
 * Title: Contact — FAQ
 * Slug: abf-accounting/contact-faq
 * Categories: abf-sections
 * Description: "Before you get in touch." Only the first answer is in Figma; the others are drafted for the client to check.
 *
 * @package ABF_Accounting
 */

echo abf_accounting_faq( // phpcs:ignore WordPress.Security.EscapeOutput
    array(
        array(__('What should I include in my message?', 'abf-accounting'), __('A short description of your business, the service or question you need help with, and any deadline you are working to. That helps us prepare before we call.', 'abf-accounting')),
        array(__('Can I choose a time for the callback?', 'abf-accounting'), __('Yes. Pick a preferred date and time on the form and we will call you then, or as close to it as we can.', 'abf-accounting')),
        array(__('Can I ask about one-off work?', 'abf-accounting'), __('Yes. Many services are one-off, such as a Self Assessment return or a Confirmation Statement. Tell us what you need and we will confirm the price first.', 'abf-accounting')),
        array(__('I am already an ABF client. Who do I contact?', 'abf-accounting'), __('Contact your dedicated accountant directly, or use the phone number or email on this page and we will pass your message on.', 'abf-accounting')),
        array(__('Do you work with businesses outside Luton?', 'abf-accounting'), __('Yes. ABF works online with businesses across the UK, with support by phone and email.', 'abf-accounting')),
        array(__('How quickly will you get back to me?', 'abf-accounting'), __('We aim to reply as soon as possible, usually within one working day.', 'abf-accounting')),
    ),
    array(
        'eyebrow' => __('Common questions', 'abf-accounting'),
        'title'   => __('Before you get in touch.', 'abf-accounting'),
        'intro'   => __('A few things people often ask before sending an enquiry.', 'abf-accounting'),
        // Already on Contact, so no "Contact us" link under these questions.
        'contact_link' => false,
    )
);
