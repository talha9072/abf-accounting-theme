<?php
/**
 * Title: Home — Trustpilot reviews strip
 * Slug: abf-accounting/home-reviews
 * Categories: abf-sections
 * Description: "Trusted by UK businesses" with the TrustScore card and a review carousel (to be replaced by the live Trustpilot widget).
 *
 * @package ABF_Accounting
 */

$abf_accounting_reviews = array(
    array(__('My experience with ABF Accounting has been excellent', 'abf-accounting'), __('They are efficient, responsive, and always easy to reach whenever I need to contact them.', 'abf-accounting'), 'Stella Harrison · 16 Jul 2026'),
    array(__('Great service and very professional', 'abf-accounting'), __('They responded quickly, explained everything clearly, and answered all my questions.', 'abf-accounting'), 'Fola Ebiseni · 12 Jul 2026'),
    array(__('Swift response with a touch of professionalism', 'abf-accounting'), __('Unprompted 5-star review on Trustpilot', 'abf-accounting'), 'Adefunke Ifaturoti · 9 Jul 2026'),
);

$abf_accounting_cards = '';
foreach ($abf_accounting_reviews as [$abf_accounting_title, $abf_accounting_body, $abf_accounting_who]) {
    $abf_accounting_cards .= abf_accounting_block_group(
        'abf-review-card',
        abf_accounting_block_p('abf-stars abf-stars--sm', '<span class="screen-reader-text">' . esc_html__('Rated 5 out of 5', 'abf-accounting') . '</span>')
        . abf_accounting_block_h(3, 'abf-review-card__title', $abf_accounting_title)
        . abf_accounting_block_p('abf-review-card__body', $abf_accounting_body)
        . abf_accounting_block_p('abf-review-card__who', $abf_accounting_who)
    );
}

$abf_accounting_logo = abf_accounting_block_image('abf-trustpilot-logo', abf_accounting_img('trustpilot-logo.webp'), __('Trustpilot', 'abf-accounting'));

$abf_accounting_inner = abf_accounting_block_group(
    'abf-reviews__intro',
    abf_accounting_block_p('abf-eyebrow abf-eyebrow--sm', __('Trustpilot', 'abf-accounting'))
    . abf_accounting_block_h(2, 'abf-reviews__title', __('Trusted by UK businesses.', 'abf-accounting'))
    . abf_accounting_block_p('abf-reviews__lead', __('Real customer experiences, displayed through Trustpilot.', 'abf-accounting'))
)
    . abf_accounting_block_group(
        'abf-reviews__summary',
        abf_accounting_block_group('abf-reviews__logo-pill', $abf_accounting_logo)
        . abf_accounting_block_p('abf-reviews__rating', __('Excellent', 'abf-accounting') . ' <span class="abf-stars" role="img" aria-label="' . esc_attr__('Rated 5 out of 5', 'abf-accounting') . '"></span>')
        . abf_accounting_block_p('abf-reviews__score', __('TrustScore 4.7 · 27 reviews', 'abf-accounting'))
        . abf_accounting_block_p('abf-reviews__copy', __('Independent feedback from ABF customers, kept current through Trustpilot.', 'abf-accounting'))
    )
    . abf_accounting_block_group(
        'abf-reviews__carousel',
        abf_accounting_block_group(
            'abf-reviews__bar',
            $abf_accounting_logo
            . abf_accounting_block_p('abf-reviews__bar-rating', __('Excellent', 'abf-accounting') . ' <span class="abf-stars abf-stars--md" role="img" aria-label="' . esc_attr__('Rated 5 out of 5', 'abf-accounting') . '"></span> <span class="abf-reviews__bar-score">' . __('TrustScore 4.7 · 27 reviews', 'abf-accounting') . '</span>')
            . abf_accounting_block_p('abf-reviews__controls', '<button type="button" class="abf-reviews__prev" aria-label="' . esc_attr__('Previous reviews', 'abf-accounting') . '"></button><button type="button" class="abf-reviews__next" aria-label="' . esc_attr__('Next reviews', 'abf-accounting') . '"></button>')
        )
        . abf_accounting_block_p('abf-reviews__note', __('Independent reviews displayed through Trustpilot.', 'abf-accounting'))
        . abf_accounting_block_group('abf-reviews__track', $abf_accounting_cards)
    );

echo abf_accounting_block_section('abf-reviews', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
