<?php
/**
 * ABF Accounting theme functions.
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once get_theme_file_path('inc/blocks.php');
require_once get_theme_file_path('inc/settings.php');
require_once get_theme_file_path('inc/trustpilot.php');
require_once get_theme_file_path('inc/sections.php');
require_once get_theme_file_path('inc/contact-form.php');
require_once get_theme_file_path('inc/landing.php');
require_once get_theme_file_path('inc/blog.php');
require_once get_theme_file_path('inc/shop.php');
require_once get_theme_file_path('inc/buy-now.php');
require_once get_theme_file_path('inc/callback.php');
require_once get_theme_file_path('inc/menu.php');

function abf_accounting_setup(): void
{
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_editor_style(array('assets/css/frontend.css', 'assets/css/home.css', 'assets/css/pages.css', 'assets/css/blog.css', 'assets/css/editor.css'));

    register_block_pattern_category(
        'abf-sections',
        array(
            'label' => __('ABF Sections', 'abf-accounting'),
        )
    );
}
add_action('after_setup_theme', 'abf_accounting_setup');

function abf_accounting_enqueue_assets(): void
{
    $version = wp_get_theme()->get('Version');

    wp_enqueue_style('abf-accounting-frontend', get_theme_file_uri('assets/css/frontend.css'), array(), $version);
    wp_enqueue_style('abf-accounting-home', get_theme_file_uri('assets/css/home.css'), array('abf-accounting-frontend'), $version);
    wp_enqueue_style('abf-accounting-pages', get_theme_file_uri('assets/css/pages.css'), array('abf-accounting-frontend'), $version);
    wp_enqueue_style('abf-accounting-blog', get_theme_file_uri('assets/css/blog.css'), array('abf-accounting-frontend'), $version);

    wp_enqueue_script(
        'abf-accounting-site',
        get_theme_file_uri('assets/js/site.js'),
        array(),
        $version,
        array(
            'in_footer' => true,
            'strategy'  => 'defer',
        )
    );
}
add_action('wp_enqueue_scripts', 'abf_accounting_enqueue_assets');

/**
 * Home hero on laptops (ClickUp, 22 Nov): the whole hero has to fit on the first screen.
 * The visible window height goes into --abf-vh before anything is drawn (no jump), and home.css
 * shrinks the hero just enough. Only the front page needs it.
 */
function abf_accounting_viewport_height(): void
{
    if (! is_front_page()) {
        return;
    }
    echo "<script>(function(){var d=document.documentElement,s=function(){d.style.setProperty('--abf-vh',String(window.innerHeight));};s();window.addEventListener('resize',s);})();</script>\n";
}
add_action('wp_head', 'abf_accounting_viewport_height', 2);

/**
 * Cache-bust theme images and icons with the theme version.
 *
 * @param string $url  Resolved file URL.
 * @param string $file Path relative to the theme root.
 */
function abf_accounting_version_theme_images(string $url, string $file): string
{
    if (! preg_match('#^/?assets/(img|icons)/.+\.[a-z0-9]+$#i', $file)) {
        return $url;
    }

    return add_query_arg('ver', wp_get_theme()->get('Version'), $url);
}
add_filter('theme_file_uri', 'abf_accounting_version_theme_images', 10, 2);

/**
 * Preload the self-hosted Manrope file so above-the-fold text renders without a font swap.
 */
function abf_accounting_preload_fonts(): void
{
    printf(
        '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
        esc_url(get_theme_file_uri('assets/fonts/manrope-latin-variable.woff2'))
    );
}
add_action('wp_head', 'abf_accounting_preload_fonts', 1);

/**
 * Site icon from the Figma favicon ("ABF" mark on a white tile), until a Site Icon is set.
 */
function abf_accounting_site_icon(): void
{
    if (has_site_icon()) {
        return;
    }

    $version = wp_get_theme()->get('Version');
    $icon    = static fn (int $size): string => esc_url(add_query_arg('ver', $version, get_theme_file_uri("assets/favicon/icon-{$size}.png")));

    printf('<link rel="icon" href="%s" sizes="32x32">' . "\n", $icon(32));
    printf('<link rel="icon" href="%s" sizes="192x192">' . "\n", $icon(192));
    printf('<link rel="apple-touch-icon" href="%s">' . "\n", $icon(180));
    printf('<meta name="msapplication-TileImage" content="%s">' . "\n", $icon(270));
}
add_action('wp_head', 'abf_accounting_site_icon');
add_action('admin_head', 'abf_accounting_site_icon');
add_action('login_head', 'abf_accounting_site_icon');

/**
 * Theme image URL, for patterns.
 *
 * @param string $file File name inside assets/img.
 */
function abf_accounting_img(string $file): string
{
    return esc_url(get_theme_file_uri('assets/img/' . $file));
}

/**
 * Inline SVG from assets/icons (decorative: hidden from screen readers).
 *
 * @param string $name File name without .svg.
 */
function abf_accounting_icon(string $name): string
{
    $file = get_theme_file_path('assets/icons/' . $name . '.svg');
    if (! is_readable($file)) {
        return '';
    }
    $svg = (string) file_get_contents($file); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

    return preg_replace('/<svg\b/', '<svg aria-hidden="true" focusable="false"', $svg, 1);
}

/**
 * Public contact details (footer, header, Contact page). Client will confirm the final ones.
 */
function abf_accounting_contact(): array
{
    return array(
        'phone'       => '020 3968 9444',
        'phone_intl'  => '+44 20 3968 9444',
        'phone_uri'   => 'tel:+442039689444',
        'email'       => 'support@abfaccounting.com',
        'address'     => array('18 Thricknells Close', 'Luton, LU3 3RP'),
        'company'     => 'ABF Accounting Services Ltd',
        'company_no'  => '15299101',
    );
}

/**
 * The 8 services, in the client's order (Figma page 01, row 2). Shared by the header,
 * footer and service cards so every link points to the same page.
 */
function abf_accounting_services(): array
{
    $list = array(
        'annual-accounts-tax-return'   => __('Annual Accounts & Tax Return', 'abf-accounting'),
        'confirmation-statement'       => __('Confirmation Statement', 'abf-accounting'),
        'self-assessment-tax-return'   => __('Self Assessment Tax Return', 'abf-accounting'),
        'dormant-company-accounts'     => __('Dormant Company Accounts', 'abf-accounting'),
        'bookkeeping-services'         => __('Bookkeeping Services', 'abf-accounting'),
        'vat-return'                   => __('VAT Return', 'abf-accounting'),
        'payroll-and-pension'          => __('Payroll and Pension', 'abf-accounting'),
        'tax-planning-advice'          => __('Tax Planning Advice', 'abf-accounting'),
    );
    $out  = array();
    foreach ($list as $slug => $name) {
        $out[$slug] = array(
            'name' => $name,
            'url'  => home_url('/services/' . $slug . '/'),
        );
    }

    return $out;
}

/**
 * The 7 "Who we help" audiences (Figma page 01, row 3, plus Charities & CICs and Partnerships,
 * Nov 2026). Footer order; the header dropdown uses abf_accounting_audience_menu_order().
 */
function abf_accounting_audiences(): array
{
    $list = array(
        'limited-companies'          => __('Limited companies', 'abf-accounting'),
        'sole-traders'               => __('Sole traders', 'abf-accounting'),
        'self-employed-freelancers'  => __('Self-employed & freelancers', 'abf-accounting'),
        'contractors'                => __('Contractors', 'abf-accounting'),
        'landlords'                  => __('Landlords', 'abf-accounting'),
        'charities-cics'             => __('Charities & CICs', 'abf-accounting'),
        'partnerships'               => __('Partnerships', 'abf-accounting'),
    );
    $out  = array();
    foreach ($list as $slug => $name) {
        $out[$slug] = array(
            'name' => $name,
            'url'  => home_url('/who-we-help/' . $slug . '/'),
        );
    }

    return $out;
}

/**
 * Where "Buy now / Get started / File now" goes for a package: "?buy=<package>" on the current
 * page, which opens the Buy now pop-up with that service selected (inc/buy-now.php). Services
 * not sold online (and any package while WooCommerce is off) go to Contact.
 *
 * @param string $package Package key (company-accounts, llp, cic, self-assessment, dormant,
 *                        confirmation-statement) or a service / audience page slug.
 */
function abf_accounting_buy_url(string $package = ''): string
{
    $key = abf_accounting_package_for($package);

    return function_exists('abf_buy_service') && abf_buy_service($key) ? '?buy=' . rawurlencode($key) : home_url('/contact/');
}
