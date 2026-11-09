<?php
/**
 * Title: Status — Thank You
 * Slug: abf-accounting/status-thank-you
 * Categories: abf-sections
 * Description: Full-screen office backdrop with "Thank you." and Back to homepage / View pricing.
 *
 * @package ABF_Accounting
 */

echo abf_accounting_status_page( // phpcs:ignore WordPress.Security.EscapeOutput
    __('Enquiry received', 'abf-accounting'),
    __('Thank you.', 'abf-accounting'),
    __('Your enquiry has been received. A member of the ABF Accounting team will review your message and get back to you as soon as possible.', 'abf-accounting'),
    array(__('View pricing', 'abf-accounting'), home_url('/pricing/'))
);
