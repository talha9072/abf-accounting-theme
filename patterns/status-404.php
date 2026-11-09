<?php
/**
 * Title: Status — 404
 * Slug: abf-accounting/status-404
 * Categories: abf-sections
 * Inserter: no
 * Description: "We could not find that page." with the large 404 mark.
 *
 * @package ABF_Accounting
 */

echo abf_accounting_status_page( // phpcs:ignore WordPress.Security.EscapeOutput
    __('Page not found', 'abf-accounting'),
    __('We could not find that page.', 'abf-accounting'),
    __('The page may have moved, or the link may be out of date. Head back to the homepage or get in touch with the team.', 'abf-accounting'),
    array(__('Contact us', 'abf-accounting'), home_url('/contact/')),
    true
);
