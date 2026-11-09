<?php
/**
 * Title: Article — Key point
 * Slug: abf-accounting/post-key-point
 * Categories: abf-sections
 * Block Types: core/post-content
 * Description: Mint callout with a green left rule for a definition or important takeaway inside an article.
 *
 * @package ABF_Accounting
 */

echo abf_accounting_block_group( // phpcs:ignore WordPress.Security.EscapeOutput
    'abf-key-point',
    abf_accounting_block_p('abf-key-point__label', __('Key point', 'abf-accounting'))
    . abf_accounting_block_p('abf-key-point__text', __('A highlighted point, definition or important takeaway sits in this callout when the article needs it.', 'abf-accounting'))
);
