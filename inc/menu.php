<?php
/**
 * Mobile and tablet menu (Figma "Header / Mobile / Menu open" 1391:10917 and
 * "Header / Tablet / Menu open" 1391:11000).
 *
 * The header uses the core Navigation block's overlay (open/close, focus trap, Escape).
 * This adds what the design has around the links: a bar with the logo (tablet: Buy now and
 * My account too) and a footer with the phone number, Callback, Buy now and My account.
 * Sub-menus open one at a time — assets/js/menu.js; styles in assets/css/frontend.css.
 *
 * @package ABF_Accounting
 */

/**
 * Bar at the top of the open menu. The core close button sits over its right-hand end.
 */
function abf_accounting_menu_bar(): string
{
    $account = abf_accounting_shop_url('myaccount');

    return '<div class="abf-menu-bar">'
        . '<a class="abf-menu-bar__logo" href="' . esc_url(home_url('/')) . '"><img src="' . abf_accounting_img('abf-logo.svg') . '" alt="' . esc_attr__('ABF Accounting home', 'abf-accounting') . '" width="136" height="40"></a>'
        . '<div class="abf-menu-bar__actions">'
        . '<a class="abf-menu-bar__buy" href="' . esc_url(abf_accounting_buy_url('company-accounts')) . '">' . esc_html__('Buy now', 'abf-accounting') . '</a>'
        . '<a class="abf-header__icon abf-menu-bar__account" href="' . esc_url($account) . '" aria-label="' . esc_attr__('My account', 'abf-accounting') . '"><svg viewBox="0 0 20 20" width="20" height="20" fill="none" aria-hidden="true" focusable="false"><path d="M13.167 7.083a3.167 3.167 0 1 1-6.334 0 3.167 3.167 0 0 1 6.334 0Z" stroke="currentColor" stroke-width="1.5"/><path d="M3.75 16.667C4.833 13.667 7.25 12.167 10 12.167s5.167 1.5 6.25 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></a>'
        . '</div>'
        . '</div>';
}

/**
 * Footer under the links: phone, Callback, Buy now (Company Accounts) and My account.
 */
function abf_accounting_menu_footer(): string
{
    $contact = abf_accounting_contact();

    return '<div class="abf-menu-footer">'
        . '<a class="abf-menu-footer__phone" href="' . esc_attr($contact['phone_uri']) . '">' . esc_html($contact['phone']) . '</a>'
        . '<div class="abf-menu-footer__buttons">'
        . '<a class="abf-menu-footer__btn abf-menu-footer__btn--callback" href="' . esc_url(abf_accounting_callback_url()) . '">' . esc_html__('Callback', 'abf-accounting') . '</a>'
        . '<a class="abf-menu-footer__btn abf-menu-footer__btn--buy" href="' . esc_url(abf_accounting_buy_url('company-accounts')) . '">' . esc_html__('Buy now', 'abf-accounting') . '</a>'
        . '</div>'
        . '<a class="abf-menu-footer__account" href="' . esc_url(abf_accounting_shop_url('myaccount')) . '">' . esc_html__('My account', 'abf-accounting') . '</a>'
        . '</div>';
}

/**
 * Put the bar before the links and the footer after them, inside the overlay dialog, so the
 * core focus trap and Escape key cover them. Only the header menu is changed.
 */
function abf_accounting_menu_render(string $html, array $block): string
{
    if (false === strpos((string) ($block['attrs']['className'] ?? ''), 'abf-header__nav')) {
        return $html;
    }
    $open = strpos($html, 'wp-block-navigation__responsive-container-content');
    $end  = strrpos($html, '</ul>');
    if (false === $open || false === $end || $end < $open) {
        return $html;
    }
    $start = strpos($html, '>', $open);

    return substr($html, 0, $start + 1)
        . abf_accounting_menu_bar()
        . substr($html, $start + 1, $end + 5 - $start - 1)
        . abf_accounting_menu_footer()
        . substr($html, $end + 5);
}
add_filter('render_block_core/navigation', 'abf_accounting_menu_render', 10, 2);

function abf_accounting_menu_assets(): void
{
    wp_enqueue_script(
        'abf-accounting-menu',
        get_theme_file_uri('assets/js/menu.js'),
        array(),
        wp_get_theme()->get('Version'),
        array(
            'in_footer' => true,
            'strategy'  => 'defer',
        )
    );
}
add_action('wp_enqueue_scripts', 'abf_accounting_menu_assets');
