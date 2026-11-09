<?php
/**
 * Title: Home — Real accountants, real support (video)
 * Slug: abf-accounting/home-real-support
 * Categories: abf-sections
 * Description: Dark section with centred heading, the intro video poster and two proof points.
 *
 * The client has no video yet (Figma handoff, image register): the poster shows the player
 * frame only. Swap in the YouTube embed when the video exists.
 *
 * @package ABF_Accounting
 */

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
        . abf_accounting_block_p('abf-video__time', '0:00 / 1:12')
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
