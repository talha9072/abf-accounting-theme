<?php
/**
 * Title: Contact — What happens next and FAQ
 * Slug: abf-accounting/contact-next
 * Categories: abf-sections
 * Description: Four-step "From enquiry to the right support" timeline. Only the first FAQ answer is in Figma; the others are drafted for the client to check.
 *
 * @package ABF_Accounting
 */

echo abf_accounting_timeline( // phpcs:ignore WordPress.Security.EscapeOutput
    __('What happens next', 'abf-accounting'),
    __('From enquiry to the right support in four steps.', 'abf-accounting'),
    array(
        array(__('Tell us what you need', 'abf-accounting'), __('Send the form, call or email with a short description.', 'abf-accounting')),
        array(__('We review your enquiry', 'abf-accounting'), __('Your message is passed to the right accountant.', 'abf-accounting')),
        array(__('We call you back', 'abf-accounting'), __('At the time you chose, to talk through your needs.', 'abf-accounting')),
        array(__('Get started', 'abf-accounting'), __('Choose a service and meet your accountant.', 'abf-accounting')),
    )
);
