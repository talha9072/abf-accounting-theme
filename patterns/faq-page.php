<?php
/**
 * Title: FAQ page — Hero and all questions
 * Slug: abf-accounting/faq-page
 * Categories: abf-sections
 * Description: The /faq/ page (Figma "FAQ"): "Questions, answered." on the mint band, then one accordion per topic. Answers from the Figma developer note.
 *
 * @package ABF_Accounting
 */

$abf_accounting_topics = abf_accounting_faq_topics();

$abf_accounting_out = abf_accounting_block_section(
    'abf-blog-hero abf-faq-hero',
    abf_accounting_block_p('abf-eyebrow abf-blog-hero__eyebrow', __('FAQ', 'abf-accounting'))
    . abf_accounting_block_h(1, 'abf-blog-hero__title', __('Questions, answered.', 'abf-accounting'))
);

$abf_accounting_last = count($abf_accounting_topics) - 1;
foreach (array_values($abf_accounting_topics) as $abf_accounting_i => $abf_accounting_topic) {
    $abf_accounting_out .= abf_accounting_faq(
        array_map(static fn ($qa) => array(esc_html($qa[0]), esc_html($qa[1])), $abf_accounting_topic['items']),
        array(
            'eyebrow'      => esc_html($abf_accounting_topic['eyebrow']),
            'title'        => esc_html($abf_accounting_topic['title']),
            'intro'        => esc_html($abf_accounting_topic['intro']),
            'card'         => 0 === $abf_accounting_i,
            'contact_link' => $abf_accounting_last === $abf_accounting_i,
            'anchor'       => $abf_accounting_topic['anchor'],
            'class'        => 'abf-faq--topic',
        )
    );
}

echo $abf_accounting_out; // phpcs:ignore WordPress.Security.EscapeOutput
