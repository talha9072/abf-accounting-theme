<?php
/**
 * Title: Global Footer
 * Slug: abf-accounting/global-footer
 * Categories: abf-sections
 * Block Types: core/template-part/footer
 * Inserter: no
 * Description: Footer with brand column and callback card, Company / Services / Who we help links, contact details and legal links.
 *
 * @package ABF_Accounting
 */

$abf_accounting_c = abf_accounting_contact();

$abf_accounting_links = static function (string $title, array $items, string $class): string {
    $list = array();
    foreach ($items as [$label, $url]) {
        $list[] = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }

    return abf_accounting_block_group(
        'abf-footer__col ' . $class,
        abf_accounting_block_p('abf-footer__heading', esc_html($title))
        . abf_accounting_block_list('abf-footer__links', $list)
    );
};

$abf_accounting_pairs = static fn (array $items): array => array_map(static fn ($i) => array($i['name'], $i['url']), array_values($items));

$abf_accounting_social = '';
foreach (array('instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'x' => 'X') as $abf_accounting_key => $abf_accounting_name) {
    // Not linked yet: the client has not sent the profile URLs (Figma handoff, section 4).
    $abf_accounting_social .= '<span class="abf-footer__social-icon abf-footer__social-icon--' . esc_attr($abf_accounting_key) . '" role="img" aria-label="' . esc_attr($abf_accounting_name) . '"></span>';
}

$abf_accounting_brand = abf_accounting_block_image('abf-footer__logo', abf_accounting_img('abf-logo.svg'), __('ABF Accounting', 'abf-accounting'))
    . abf_accounting_block_p('abf-footer__tagline', __('From freelancers to limited companies, ABF Accounting simplifies your finances so you can focus on growth.', 'abf-accounting'))
    . abf_accounting_block_group(
        'abf-callback-card',
        abf_accounting_block_group(
            'abf-callback-card__content',
            abf_accounting_block_p('abf-callback-card__eyebrow', __('Prefer to talk?', 'abf-accounting'))
            . abf_accounting_block_p('abf-callback-card__title', __('Request a callback', 'abf-accounting'))
            . abf_accounting_block_p('abf-callback-card__text', __('Leave your number and we’ll call you back.', 'abf-accounting'))
        )
        . abf_accounting_block_buttons('abf-callback-card__actions', array(array(__('Callback', 'abf-accounting'), home_url('/contact/'), 'abf-button--callback-card')))
    );

$abf_accounting_nav = $abf_accounting_links(
    __('Company', 'abf-accounting'),
    array(
        array(__('Pricing', 'abf-accounting'), home_url('/pricing/')),
        array(__('First-year accounts FREE', 'abf-accounting'), home_url('/first-year-accounts-free/')),
        array(__('Resources', 'abf-accounting'), home_url('/resources/')),
        array(__('Contact', 'abf-accounting'), home_url('/contact/')),
    ),
    'abf-footer__col--company'
)
    . $abf_accounting_links(__('Services', 'abf-accounting'), $abf_accounting_pairs(abf_accounting_services()), 'abf-footer__col--services')
    . $abf_accounting_links(__('Who we help', 'abf-accounting'), $abf_accounting_pairs(abf_accounting_audiences()), 'abf-footer__col--audiences');

$abf_accounting_contact_col = abf_accounting_block_p('abf-footer__hello', __('Get in touch', 'abf-accounting'))
    . abf_accounting_block_group(
        'abf-footer__contact',
        abf_accounting_block_p('abf-footer__label', __('Visit us', 'abf-accounting'))
        . abf_accounting_block_p('abf-footer__address', esc_html($abf_accounting_c['address'][0]) . '<br>' . esc_html($abf_accounting_c['address'][1]))
        . abf_accounting_block_p('abf-footer__label', __('Phone', 'abf-accounting'))
        . abf_accounting_block_p('abf-footer__value', '<a href="' . esc_attr($abf_accounting_c['phone_uri']) . '">' . esc_html($abf_accounting_c['phone_intl']) . '</a>')
        . abf_accounting_block_p('abf-footer__label', __('Email', 'abf-accounting'))
        . abf_accounting_block_p('abf-footer__value abf-footer__value--email', '<a href="mailto:' . esc_attr($abf_accounting_c['email']) . '">' . esc_html($abf_accounting_c['email']) . '</a>')
        . abf_accounting_block_p('abf-footer__label', __('Follow ABF', 'abf-accounting'))
        . abf_accounting_block_p('abf-footer__social', $abf_accounting_social)
    );

$abf_accounting_legal = array();
foreach (
    array(
        array(__('Privacy Policy', 'abf-accounting'), '/privacy-policy/'),
        array(__('Terms of Service', 'abf-accounting'), '/terms-and-conditions-of-engagement/'),
        array(__('Cookie Policy', 'abf-accounting'), '/cookie-policy/'),
        array(__('Modern Slavery Statement', 'abf-accounting'), '/modern-slavery-statement/'),
        array(__('Terms & Conditions for One-Off Services', 'abf-accounting'), '/terms-and-conditions-for-one-off-services/'),
    ) as [$abf_accounting_label, $abf_accounting_path]
) {
    $abf_accounting_legal[] = '<a href="' . esc_url(home_url($abf_accounting_path)) . '">' . esc_html($abf_accounting_label) . '</a>';
}

$abf_accounting_year = gmdate('Y');

echo abf_accounting_block_group( // phpcs:ignore WordPress.Security.EscapeOutput
    'abf-footer',
    abf_accounting_block_group(
        'abf-container abf-footer__inner',
        abf_accounting_block_group(
            'abf-footer__top',
            abf_accounting_block_group('abf-footer__brand', $abf_accounting_brand)
            . abf_accounting_block_group('abf-footer__nav', $abf_accounting_nav)
            . abf_accounting_block_group('abf-footer__contact-col', $abf_accounting_contact_col)
        )
        . abf_accounting_block_group(
            'abf-footer__legal',
            abf_accounting_block_p(
                'abf-footer__company',
                sprintf(
                    /* translators: 1: company name, 2: year, 3: company number */
                    esc_html__('© %1$s %2$s · Registered in England and Wales | No. %3$s', 'abf-accounting'),
                    esc_html($abf_accounting_c['company']),
                    $abf_accounting_year,
                    esc_html($abf_accounting_c['company_no'])
                ) . '<br>' . esc_html__('Registered office:', 'abf-accounting') . ' ' . esc_html(implode(', ', $abf_accounting_c['address']))
            )
            . abf_accounting_block_p('abf-footer__legal-links', implode('<span class="abf-footer__sep" aria-hidden="true">|</span>', $abf_accounting_legal))
        )
        . abf_accounting_block_p(
            'abf-footer__copyright',
            sprintf(
                /* translators: 1: year, 2: company name */
                esc_html__('© Copyright %1$s %2$s — All Rights Reserved', 'abf-accounting'),
                $abf_accounting_year,
                esc_html($abf_accounting_c['company'])
            )
        )
    ),
    'div',
    array('align' => 'full')
);
