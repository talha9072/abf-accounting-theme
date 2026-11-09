<?php
/**
 * Title: Home — A clearer way to work
 * Slug: abf-accounting/home-media-proof
 * Categories: abf-sections
 * Description: Dark section: heading, body and three dotted proof points beside a captioned photo.
 *
 * @package ABF_Accounting
 */

$abf_accounting_points = array(
    array(__('One dedicated accountant', 'abf-accounting'), __('The same person looks after your accounts, so you never have to explain your business twice.', 'abf-accounting')),
    array(__('Online documents and approvals', 'abf-accounting'), __('Send records and sign off drafts digitally, with everything kept in one place.', 'abf-accounting')),
    array(__('Deadline reminders', 'abf-accounting'), __('We track your HMRC and Companies House dates and remind you before anything is due.', 'abf-accounting')),
);

$abf_accounting_list = '';
foreach ($abf_accounting_points as [$abf_accounting_title, $abf_accounting_text]) {
    $abf_accounting_list .= abf_accounting_block_group(
        'abf-dot-point',
        abf_accounting_block_h(3, 'abf-dot-point__title', $abf_accounting_title)
        . abf_accounting_block_p('abf-dot-point__text', $abf_accounting_text)
    );
}

$abf_accounting_inner = abf_accounting_block_group(
    'abf-media__text',
    abf_accounting_heading(
        __('A clearer way to work', 'abf-accounting'),
        __('See how ABF makes <br>accounting simpler.', 'abf-accounting'),
        __('Share your records, review drafts and approve your filings online, with your accountant on hand by phone and email whenever a question comes up. Everything stays in one place, so there is less chasing and fewer surprises.', 'abf-accounting'),
        'abf-heading--light'
    )
    . abf_accounting_block_group('abf-media__points', $abf_accounting_list)
)
    . abf_accounting_block_group(
        'abf-media__figure',
        abf_accounting_block_image('abf-media__photo', abf_accounting_img('home-media-proof-smiling-woman-at-desk.webp'), __('Smiling woman working at her desk', 'abf-accounting'))
        . abf_accounting_block_p('abf-media__caption', __('Clear communication. Online documents. Deadline support.', 'abf-accounting'))
    );

echo abf_accounting_block_section('abf-media abf-section--dark', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
