<?php
/**
 * Title: Home — Real accountants, real support (video)
 * Slug: abf-accounting/home-real-support
 * Categories: abf-sections
 * Description: Dark section with centred heading, the intro video poster and two proof points.
 *
 * The video is the YouTube link in Appearance → ABF settings → Home page video. The Figma poster
 * and player frame show first; pressing play swaps in the YouTube player (assets/js/site.js), so
 * nothing loads from YouTube until then. Without JavaScript the link opens the video on YouTube.
 *
 * @package ABF_Accounting
 */

$abf_accounting_video_id    = abf_accounting_youtube_id(abf_accounting_setting('home_video'));
// Title and the length label from the Figma player frame are only known for ABF's own video.
$abf_accounting_video_own   = 'CK_xD3Q4as8' === $abf_accounting_video_id;
$abf_accounting_video_title = $abf_accounting_video_own ? __('UK Limited Company Accounts & Tax Returns Made Easy | ABF Accounting Services', 'abf-accounting') : __('ABF Accounting video', 'abf-accounting');
$abf_accounting_video_time  = $abf_accounting_video_own ? '0:00 / 1:36' : '';

$abf_accounting_inner = abf_accounting_block_group(
    'abf-heading abf-heading--center abf-heading--light',
    abf_accounting_block_p('abf-eyebrow', __('Real people, real support', 'abf-accounting'))
    . abf_accounting_block_h(2, 'abf-title', __('Real accountants. Real support when you need it.', 'abf-accounting'))
    . abf_accounting_block_p('abf-intro', __('Once your account is set up, your dedicated accountant becomes the person who understands your business, your records and the deadlines ahead.', 'abf-accounting'))
)
    . abf_accounting_block_group(
        'abf-video',
        abf_accounting_block_image('abf-video__poster', abf_accounting_img('home-human-credibility-man-working-at-desk-with-laptop.webp'), __('Accountant working at a desk with a laptop', 'abf-accounting'))
        . abf_accounting_block_p('abf-video__label', __('ABF Accounting', 'abf-accounting'))
        . abf_accounting_block_p('abf-video__caption', __('One accounting relationship, built around your business.', 'abf-accounting'))
        . abf_accounting_block_rule('abf-video__play')
        . ($abf_accounting_video_time ? abf_accounting_block_p('abf-video__time', $abf_accounting_video_time) : '')
        . ($abf_accounting_video_id
            ? abf_accounting_block_p(
                'abf-video__open',
                sprintf(
                    '<a class="abf-video__link" href="%1$s" data-abf-video="%2$s" data-abf-video-title="%3$s" target="_blank" rel="noopener"><span class="screen-reader-text">%4$s</span></a>',
                    esc_url('https://www.youtube.com/watch?v=' . $abf_accounting_video_id),
                    esc_attr($abf_accounting_video_id),
                    esc_attr($abf_accounting_video_title),
                    /* translators: %s: video title. */
                    esc_html(sprintf(__('Play video: %s', 'abf-accounting'), $abf_accounting_video_title))
                )
            )
            : '')
    )
    . abf_accounting_block_group(
        'abf-real__proofs',
        abf_accounting_block_group(
            'abf-real__proof',
            abf_accounting_block_p('abf-real__num', '01')
            . abf_accounting_block_h(3, 'abf-real__title', __('A consistent point of contact', 'abf-accounting'))
            . abf_accounting_block_p('abf-real__text', __('Your accountant gets to know how your business works rather than starting from zero each time.', 'abf-accounting'))
        )
        . abf_accounting_block_group(
            'abf-real__proof',
            abf_accounting_block_p('abf-real__num', '02')
            . abf_accounting_block_h(3, 'abf-real__title', __('Support when questions come up', 'abf-accounting'))
            . abf_accounting_block_p('abf-real__text', __('Clear communication by phone and email, without being passed between departments.', 'abf-accounting'))
        )
    );

echo abf_accounting_block_section('abf-real abf-section--dark', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
