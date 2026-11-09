<?php
/**
 * Title: Resources — Hero
 * Slug: abf-accounting/resources-hero
 * Categories: abf-sections
 * Description: "Useful guidance for clearer accounting decisions." on the mint band.
 *
 * @package ABF_Accounting
 */

$abf_accounting_inner = abf_accounting_block_p('abf-eyebrow abf-blog-hero__eyebrow', __('Resources', 'abf-accounting'))
    . abf_accounting_block_h(1, 'abf-blog-hero__title', __('Useful guidance for clearer <br>accounting decisions.', 'abf-accounting'));

echo abf_accounting_block_section('abf-blog-hero', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
