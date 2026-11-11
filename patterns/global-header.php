<?php
/**
 * Title: Global Header
 * Slug: abf-accounting/global-header
 * Categories: abf-sections
 * Block Types: core/template-part/header
 * Inserter: no
 * Description: White header with the ABF logo, navigation (Who we help and Services dropdowns), phone, Callback and Buy now.
 *
 * @package ABF_Accounting
 */

$abf_accounting_contact = abf_accounting_contact();
$abf_accounting_link    = static function (string $label, string $url, string $class = ''): string {
    $attrs = array(
        'label'          => $label,
        'url'            => $url,
        'kind'           => 'custom',
        'isTopLevelLink' => true,
    );
    if ($class) {
        $attrs['className'] = $class;
    }

    return '<!-- wp:navigation-link ' . wp_json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ' /-->';
};
$abf_accounting_submenu = static function (string $label, string $url, array $items): string {
    $inner = '';
    foreach ($items as $item) {
        $inner .= '<!-- wp:navigation-link ' . wp_json_encode(
            array(
                'label' => $item['name'],
                'url'   => $item['url'],
                'kind'  => 'custom',
            ),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) . ' /-->';
    }

    return '<!-- wp:navigation-submenu ' . wp_json_encode(
        array(
            'label' => $label,
            'url'   => $url,
            'kind'  => 'custom',
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) . ' -->' . $inner . '<!-- /wp:navigation-submenu -->';
};
?>
<!-- wp:group {"align":"full","className":"abf-header","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull abf-header"><!-- wp:group {"className":"abf-container abf-header__inner","layout":{"type":"default"}} -->
<div class="wp-block-group abf-container abf-header__inner"><!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"abf-header__logo"} -->
<figure class="wp-block-image size-full abf-header__logo"><a href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo abf_accounting_img('abf-logo.svg'); ?>" alt="<?php esc_attr_e('ABF Accounting home', 'abf-accounting'); ?>"/></a></figure>
<!-- /wp:image -->

<!-- wp:navigation {"overlayBackgroundColor":"white","overlayTextColor":"ink","className":"abf-header__nav","layout":{"type":"flex","justifyContent":"left","flexWrap":"nowrap"}} -->
<?php
echo $abf_accounting_link(__('Home', 'abf-accounting'), home_url('/')); // phpcs:ignore WordPress.Security.EscapeOutput
echo $abf_accounting_submenu(__('Who we help', 'abf-accounting'), home_url('/who-we-help/limited-companies/'), abf_accounting_audiences()); // phpcs:ignore WordPress.Security.EscapeOutput
echo $abf_accounting_submenu(__('Services', 'abf-accounting'), home_url('/services/annual-accounts-tax-return/'), abf_accounting_services()); // phpcs:ignore WordPress.Security.EscapeOutput
echo $abf_accounting_link(__('Pricing', 'abf-accounting'), home_url('/pricing/')); // phpcs:ignore WordPress.Security.EscapeOutput
echo $abf_accounting_link(__('First-Year Accounts & Tax Return FREE', 'abf-accounting'), home_url('/first-year-accounts-free/')); // phpcs:ignore WordPress.Security.EscapeOutput
echo $abf_accounting_submenu( // phpcs:ignore WordPress.Security.EscapeOutput
    __('Resources', 'abf-accounting'),
    home_url('/resources/'),
    array(
        array('name' => __('Articles & guides', 'abf-accounting'), 'url' => home_url('/resources/')),
        array('name' => __('About', 'abf-accounting'), 'url' => home_url('/about/')),
    )
);
echo $abf_accounting_link(__('Contact', 'abf-accounting'), home_url('/contact/')); // phpcs:ignore WordPress.Security.EscapeOutput
?>
<!-- /wp:navigation -->

<!-- wp:paragraph {"className":"abf-header__phone"} -->
<p class="abf-header__phone"><a href="<?php echo esc_attr($abf_accounting_contact['phone_uri']); ?>"><?php echo esc_html($abf_accounting_contact['phone']); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"abf-header__actions"} -->
<div class="wp-block-buttons abf-header__actions"><!-- wp:button {"className":"is-style-outline abf-button--callback"} -->
<div class="wp-block-button is-style-outline abf-button--callback"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Callback', 'abf-accounting'); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"abf-button--buy"} -->
<div class="wp-block-button abf-button--buy"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url(home_url('/pricing/')); ?>"><?php esc_html_e('Buy now', 'abf-accounting'); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:html -->
<?php echo abf_accounting_header_icons(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<!-- /wp:html --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
