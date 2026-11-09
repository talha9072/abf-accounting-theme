<?php
/**
 * Title: Article — Need accounting support?
 * Slug: abf-accounting/post-inline-cta
 * Categories: abf-sections
 * Block Types: core/post-content
 * Description: Dark teal call-to-action strip inside an article with a Contact ABF button.
 *
 * @package ABF_Accounting
 */

echo abf_accounting_block_group( // phpcs:ignore WordPress.Security.EscapeOutput
    'abf-inline-cta',
    abf_accounting_block_group(
        'abf-inline-cta__copy',
        abf_accounting_block_p('abf-inline-cta__eyebrow', __('Need accounting support?', 'abf-accounting'))
        . abf_accounting_block_p('abf-inline-cta__title', __('Talk to ABF about the accounting work behind this topic.', 'abf-accounting'))
    )
    . abf_accounting_block_buttons('abf-inline-cta__actions', array(array(__('Contact ABF', 'abf-accounting'), home_url('/contact/'), 'abf-button--arrow')))
);
