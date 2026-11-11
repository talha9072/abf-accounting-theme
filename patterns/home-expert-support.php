<?php
/**
 * Title: Home — Expert support
 * Slug: abf-accounting/home-expert-support
 * Categories: abf-sections
 * Description: "Why ABF Accounting" numbered proof list beside a photo with two floating cards.
 *
 * @package ABF_Accounting
 */

$abf_accounting_proofs = array(
    array('01', __('One dedicated accountant', 'abf-accounting'), __('A consistent point of contact who understands your business and what is due next.', 'abf-accounting')),
    array('02', __('Clear pricing', 'abf-accounting'), __('Fixed prices for each service, with the scope explained before work starts.', 'abf-accounting')),
    array('03', __('Deadline-focused support', 'abf-accounting'), __('Support around HMRC and Companies House obligations, without last-minute panic.', 'abf-accounting')),
);

$abf_accounting_list = '';
foreach ($abf_accounting_proofs as [$abf_accounting_num, $abf_accounting_title, $abf_accounting_text]) {
    $abf_accounting_list .= abf_accounting_block_group(
        'abf-proof',
        abf_accounting_block_p('abf-proof__num', $abf_accounting_num)
        . abf_accounting_block_group(
            'abf-proof__copy',
            abf_accounting_block_h(3, 'abf-proof__title', $abf_accounting_title)
            . abf_accounting_block_p('abf-proof__text', $abf_accounting_text)
        )
    );
}

$abf_accounting_float = static fn (string $mod, string $title, string $sub): string => abf_accounting_block_group(
    'abf-float-card abf-float-card--' . $mod,
    abf_accounting_block_rule('abf-float-card__icon')
    . abf_accounting_block_group(
        'abf-float-card__copy',
        abf_accounting_block_p('abf-float-card__title', $title)
        . abf_accounting_block_p('abf-float-card__sub', $sub)
    )
);

$abf_accounting_inner = abf_accounting_block_group(
    'abf-expert__text',
    abf_accounting_heading(
        __('Why ABF Accounting', 'abf-accounting'),
        __('Expert support, without the old-school accounting experience.', 'abf-accounting'),
        __('A modern service built around clear communication, predictable pricing and fewer last-minute surprises.', 'abf-accounting')
    )
    . abf_accounting_block_group('abf-proof-list', $abf_accounting_list)
)
    . abf_accounting_block_group(
        'abf-expert__media',
        abf_accounting_block_image('abf-expert__photo', abf_accounting_img('home-expert-support-woman-on-phone-bright-office.webp'), __('Smiling woman on the phone in a bright office', 'abf-accounting'))
        . $abf_accounting_float('accountant', __('Your dedicated accountant', 'abf-accounting'), __('One consistent point of contact', 'abf-accounting'))
        . $abf_accounting_float('trustpilot', __('Excellent · 4.7 out of 5', 'abf-accounting'), __('27 reviews on Trustpilot', 'abf-accounting'))
    );

echo abf_accounting_block_section('abf-expert', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
