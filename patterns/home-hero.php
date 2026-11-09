<?php
/**
 * Title: Home — Hero
 * Slug: abf-accounting/home-hero
 * Categories: abf-sections
 * Description: Centred headline (£299 offer), Buy now, the filing-journey visual and the trust rail.
 *
 * @package ABF_Accounting
 */

$abf_accounting_step = static fn (string $label, string $value, string $mod): string => abf_accounting_block_group(
    'abf-hero__step abf-hero__step--' . $mod,
    abf_accounting_block_rule('abf-hero__step-icon')
    . abf_accounting_block_p('abf-hero__step-label', $label)
    . abf_accounting_block_p('abf-hero__step-value', $value)
);

$abf_accounting_status = static fn (string $title, string $chip, string $mod): string => abf_accounting_block_group(
    'abf-hero__status abf-hero__status--' . $mod,
    abf_accounting_block_rule('abf-hero__status-icon')
    . abf_accounting_block_p('abf-hero__status-kicker', __('Filing status', 'abf-accounting'))
    . abf_accounting_block_p('abf-hero__status-title', $title)
    . abf_accounting_block_p('abf-hero__chip', $chip)
);

$abf_accounting_visual = abf_accounting_block_group(
    'abf-hero__visual',
    abf_accounting_block_rule('abf-hero__stripes abf-hero__stripes--left')
    . abf_accounting_block_rule('abf-hero__stripes abf-hero__stripes--right')
    . abf_accounting_block_image('abf-hero__photo', abf_accounting_img('home-hero-man-on-laptop-open-plan-office.webp'), __('Man working on a laptop in an open-plan office', 'abf-accounting'))
    . abf_accounting_block_group(
        'abf-hero__steps',
        $abf_accounting_step(__('Step 1', 'abf-accounting'), __('Share your financial information', 'abf-accounting'), '1')
        . $abf_accounting_step(__('Step 2', 'abf-accounting'), __('We prepare &amp; file', 'abf-accounting'), '2')
        . $abf_accounting_step(__('Step 3', 'abf-accounting'), __('We submit and you get proof', 'abf-accounting'), '3')
    )
    . abf_accounting_block_group(
        'abf-hero__statuses',
        $abf_accounting_status(__('Tax return submitted', 'abf-accounting'), __('HMRC accepted', 'abf-accounting'), 'tax')
        . $abf_accounting_status(__('Annual accounts submitted', 'abf-accounting'), __('Companies House accepted', 'abf-accounting'), 'accounts')
    )
);

$abf_accounting_rail = abf_accounting_block_group(
    'abf-hero__rail',
    abf_accounting_block_group(
        'abf-hero__rail-col abf-hero__rail-col--rating',
        abf_accounting_block_p('abf-hero__rail-rating', '<span class="abf-hero__rail-stars" aria-hidden="true"></span>' . __('4.7 Trustpilot', 'abf-accounting'))
        . abf_accounting_block_p('abf-hero__rail-sub', __('Rated by ABF customers', 'abf-accounting'))
    )
    . abf_accounting_block_group(
        'abf-hero__rail-col',
        abf_accounting_block_p('abf-hero__rail-title', __('Dedicated accountant', 'abf-accounting'))
        . abf_accounting_block_p('abf-hero__rail-sub', __('One consistent point of contact', 'abf-accounting'))
    )
    . abf_accounting_block_group(
        'abf-hero__rail-col',
        abf_accounting_block_p('abf-hero__rail-title', __('First-year accounts FREE', 'abf-accounting'))
        . abf_accounting_block_p('abf-hero__rail-sub', __('New companies · £120 set-up applies', 'abf-accounting'))
    )
);

$abf_accounting_inner = abf_accounting_block_group(
    'abf-hero__intro',
    abf_accounting_block_p('abf-hero__eyebrow', __('Online accounting for UK businesses', 'abf-accounting'))
    . abf_accounting_block_h(1, 'abf-hero__title', '<span class="abf-hero__title-line1">' . __('File online company accounts', 'abf-accounting') . '</span> <span class="abf-hero__title-line2">' . __('and tax return for', 'abf-accounting') . ' <mark class="abf-hero__price">£299</mark></span>')
    . abf_accounting_block_p('abf-hero__lead', __('Whether you are a limited company, sole trader, freelancer or e-commerce seller, we have a solution to fit your needs.', 'abf-accounting'))
    . abf_accounting_block_buttons('abf-hero__actions', array(array(__('Buy now', 'abf-accounting'), home_url('/pricing/'), 'abf-button--wide')))
)
    . $abf_accounting_visual
    . abf_accounting_block_p('abf-hero__reassurance', __('From freelancers to limited companies, ABF Accounting simplifies your finances so you can focus on growth.', 'abf-accounting'))
    . $abf_accounting_rail;

echo abf_accounting_block_section('abf-hero', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
