<?php
/**
 * Title: Blog — Filters
 * Slug: abf-accounting/blog-filters
 * Categories: abf-sections
 * Inserter: no
 * Description: Category chips and the article search box above the featured post (dynamic).
 *
 * @package ABF_Accounting
 */

echo abf_accounting_block_html('<div class="abf-blog-filters">' . abf_accounting_blog_chips() . abf_accounting_blog_search(__('Search ABF insights', 'abf-accounting'), 'abf-search--filters') . '</div>'); // phpcs:ignore WordPress.Security.EscapeOutput
