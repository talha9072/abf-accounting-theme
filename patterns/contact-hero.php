<?php
/**
 * Title: Contact — Details and callback form
 * Slug: abf-accounting/contact-hero
 * Categories: abf-sections
 * Description: "Speak to an accountant, not a call centre." with phone, email and office, beside the Request a callback form.
 *
 * @package ABF_Accounting
 */

$abf_accounting_c = abf_accounting_contact();

$abf_accounting_detail = static fn (int $n, string $label, string $value): string => abf_accounting_block_group(
    'abf-contact-detail',
    abf_accounting_block_image('abf-contact-detail__icon', get_theme_file_uri('assets/icons/contact-detail-' . $n . '.svg'))
    . abf_accounting_block_group(
        'abf-contact-detail__copy',
        abf_accounting_block_p('abf-contact-detail__label', $label)
        . abf_accounting_block_p('abf-contact-detail__value', $value)
    )
);

$abf_accounting_details = abf_accounting_block_p('abf-eyebrow abf-eyebrow--caps', __('Talk to ABF', 'abf-accounting'))
    . abf_accounting_block_h(1, 'abf-page-title', __('Speak to an accountant, <br>not a call centre.', 'abf-accounting'))
    . abf_accounting_block_p('abf-page-lead', __('Tell us what you need help with and when you would like us to call. We will point you to the right support for your business.', 'abf-accounting'))
    . abf_accounting_block_group(
        'abf-contact-details',
        $abf_accounting_detail(1, __('Phone', 'abf-accounting'), '<a href="' . esc_attr($abf_accounting_c['phone_uri']) . '">' . esc_html(str_replace(' 141 ', ' 141', $abf_accounting_c['phone'])) . '</a>')
        . $abf_accounting_detail(2, __('Email', 'abf-accounting'), '<a href="mailto:' . esc_attr($abf_accounting_c['email']) . '">' . esc_html($abf_accounting_c['email']) . '</a>')
        . $abf_accounting_detail(3, __('Registered office', 'abf-accounting'), esc_html(implode(', ', $abf_accounting_c['address'])))
    );

$abf_accounting_form = abf_accounting_block_h(2, 'abf-callback-form__title', __('Request a callback', 'abf-accounting'))
    . abf_accounting_block_p('abf-callback-form__sub', __('Complete the form and choose a preferred time for ABF to contact you.', 'abf-accounting'))
    . "<!-- wp:shortcode -->\n[abf_callback_form]\n<!-- /wp:shortcode -->";

echo abf_accounting_block_section( // phpcs:ignore WordPress.Security.EscapeOutput
    'abf-contact',
    abf_accounting_block_group('abf-contact__details', $abf_accounting_details)
    . abf_accounting_block_group('abf-callback-form', $abf_accounting_form),
    array('anchor' => 'callback')
);
