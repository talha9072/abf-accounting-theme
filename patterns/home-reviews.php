<?php
/**
 * Title: Home — Trustpilot reviews strip
 * Slug: abf-accounting/home-reviews
 * Categories: abf-sections
 * Description: "Trusted by UK businesses" with the TrustScore card and a panel that sends visitors to ABF's Trustpilot reviews (or Trustpilot's review carousel, once switched on in ABF settings).
 *
 * Reviews are not copied into the site (client, 19 Nov 2026: Trustpilot's rules). See inc/trustpilot.php.
 *
 * @package ABF_Accounting
 */

$abf_accounting_logo   = abf_accounting_block_image('abf-trustpilot-logo', abf_accounting_img('trustpilot-logo.webp'), __('Trustpilot', 'abf-accounting'));
$abf_accounting_rating = __('Excellent', 'abf-accounting') . ' <span class="abf-stars" role="img" aria-label="' . esc_attr__('Rated 4.7 out of 5', 'abf-accounting') . '"></span>';
$abf_accounting_score  = __('TrustScore 4.7 · 27 reviews', 'abf-accounting');

// Summary card: Trustpilot's rating TrustBox when switched on, else the rating as text.
$abf_accounting_box     = abf_accounting_trustbox('tp_rating_template', 'dark', 24, 'abf-reviews__trustbox');
$abf_accounting_summary = abf_accounting_block_group('abf-reviews__logo-pill', $abf_accounting_logo)
    . ($abf_accounting_box
        ? abf_accounting_block_html($abf_accounting_box)
        : abf_accounting_block_p('abf-reviews__rating', $abf_accounting_rating) . abf_accounting_block_p('abf-reviews__score', $abf_accounting_score))
    . abf_accounting_block_p('abf-reviews__copy', __('Independent feedback from ABF customers, kept current through Trustpilot.', 'abf-accounting'));

// Panel: Trustpilot's review carousel when switched on (paid plan), else a link to the reviews.
$abf_accounting_carousel = abf_accounting_trustbox('tp_carousel_template', 'light', 140, 'abf-reviews__trustbox');
if ($abf_accounting_carousel) {
    $abf_accounting_panel = abf_accounting_block_group('abf-reviews__carousel abf-reviews__carousel--widget', abf_accounting_block_html($abf_accounting_carousel));
} else {
    $abf_accounting_panel = abf_accounting_block_group(
        'abf-reviews__carousel abf-reviews__carousel--link',
        abf_accounting_block_group(
            'abf-reviews__bar',
            $abf_accounting_logo
            . abf_accounting_block_p('abf-reviews__bar-rating', $abf_accounting_rating . ' <span class="abf-reviews__bar-score">' . $abf_accounting_score . '</span>')
        )
        . abf_accounting_block_h(3, 'abf-reviews__panel-title', __('Read what our customers say.', 'abf-accounting'))
        . abf_accounting_block_p('abf-reviews__panel-text', __('Every review is written by an ABF customer on Trustpilot, an independent review site. Read them in full, good and bad, on our Trustpilot page.', 'abf-accounting'))
        . abf_accounting_block_p('abf-reviews__panel-link', abf_accounting_trustpilot_link('abf-tp-link'))
    );
}

$abf_accounting_inner = abf_accounting_block_group(
    'abf-reviews__intro',
    abf_accounting_block_p('abf-eyebrow abf-eyebrow--sm', __('Trustpilot', 'abf-accounting'))
    . abf_accounting_block_h(2, 'abf-reviews__title', __('Trusted by UK businesses.', 'abf-accounting'))
    . abf_accounting_block_p('abf-reviews__lead', __('Real customer experiences, displayed through Trustpilot.', 'abf-accounting'))
)
    . abf_accounting_block_group('abf-reviews__summary', $abf_accounting_summary)
    . $abf_accounting_panel;

echo abf_accounting_block_section('abf-reviews', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
