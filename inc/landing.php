<?php
/**
 * Service and "Who we help" landing pages.
 *
 * Both are custom post types (Figma handoff: "2 custom post types"), served at /services/<slug>/
 * and /who-we-help/<slug>/. All 13 pages share one Figma template: hero + price card, trust
 * strip, explainer, one page-specific feature section, included cards, mid CTA, timeline,
 * audience cards, reviews, FAQ and final CTA. The copy lives in inc/data/*.json (extracted from
 * Figma); each page is registered as its own pattern, abf-accounting/landing-<slug>, and the
 * post content is just that pattern reference.
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register the two post types.
 */
function abf_accounting_register_post_types(): void
{
    $common = array(
        'public'       => true,
        'show_in_rest' => true,
        'has_archive'  => false,
        'hierarchical' => false,
        'supports'     => array('title', 'editor', 'excerpt', 'revisions', 'page-attributes'),
    );

    register_post_type(
        'abf_service',
        $common + array(
            'labels'    => array(
                'name'          => __('Services', 'abf-accounting'),
                'singular_name' => __('Service', 'abf-accounting'),
                'add_new_item'  => __('Add new service', 'abf-accounting'),
                'edit_item'     => __('Edit service', 'abf-accounting'),
            ),
            'menu_icon' => 'dashicons-portfolio',
            'rewrite'   => array('slug' => 'services', 'with_front' => false),
        )
    );

    register_post_type(
        'abf_audience',
        $common + array(
            'labels'    => array(
                'name'          => __('Who We Help', 'abf-accounting'),
                'singular_name' => __('Audience page', 'abf-accounting'),
                'add_new_item'  => __('Add new audience page', 'abf-accounting'),
                'edit_item'     => __('Edit audience page', 'abf-accounting'),
            ),
            'menu_icon' => 'dashicons-groups',
            'rewrite'   => array('slug' => 'who-we-help', 'with_front' => false),
        )
    );
}
add_action('init', 'abf_accounting_register_post_types');

/**
 * Flush rewrite rules once after the theme is switched on, so the new URLs work.
 */
function abf_accounting_flush_on_switch(): void
{
    abf_accounting_register_post_types();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'abf_accounting_flush_on_switch');

/**
 * Page data from inc/data (landing pages, page-specific sections and FAQ answers).
 *
 * @param string $file File name without .json.
 */
function abf_accounting_data(string $file): array
{
    static $cache = array();
    if (! isset($cache[$file])) {
        $path           = get_theme_file_path('inc/data/' . $file . '.json');
        $cache[$file] = is_readable($path) ? (array) json_decode((string) file_get_contents($path), true) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
    }

    return $cache[$file];
}

/**
 * Escape copy taken from the data files; only <br> and the green highlight span are kept.
 *
 * @param string $text Copy.
 */
function abf_accounting_copy(string $text): string
{
    return wp_kses($text, array('br' => array(), 'span' => array('class' => array())));
}

/**
 * Where a card link label points. Labels without a page yet return '' (no link).
 *
 * @param string $label Link label from Figma.
 */
function abf_accounting_link_for(string $label): string
{
    $audiences = abf_accounting_audiences();
    $services  = abf_accounting_services();
    $map       = array(
        'limited company'   => $audiences['limited-companies']['url'],
        'contractor'        => $audiences['contractors']['url'],
        'sole trader'       => $audiences['sole-traders']['url'],
        'freelancer'        => $audiences['self-employed-freelancers']['url'],
        'landlord'          => $audiences['landlords']['url'],
        'first-year'        => home_url('/first-year-accounts-free/'),
        'pricing'           => home_url('/pricing/'),
    );
    foreach ($services as $service) {
        $map[strtolower(html_entity_decode($service['name']))] = $service['url'];
    }
    $label = strtolower($label);
    foreach ($map as $needle => $url) {
        if (str_contains($label, $needle)) {
            return $url;
        }
    }

    return '';
}

/**
 * Button target: "Speak to our team" goes to Contact, buy-type labels to the package checkout.
 *
 * @param string $label Button label.
 * @param string $slug  Page slug (package).
 */
function abf_accounting_button_url(string $label, string $slug): string
{
    return str_contains(strtolower($label), 'speak') ? home_url('/contact/') : abf_accounting_buy_url($slug);
}

/**
 * Hero with the dark price card (First-Year, services, audiences).
 *
 * @param array  $d    Hero data: crumb, current, title, price, lead, buttons, assurances, card.
 * @param string $slug Page slug.
 * @param string $crumb_url Breadcrumb parent URL.
 */
function abf_accounting_offer_hero(array $d, string $slug, string $crumb_url): string
{
    $card  = $d['card'];
    $icon  = get_theme_file_uri('assets/icons/pages/' . $slug . '-card.svg');
    $price = ($card['from'] ? '<span>' . esc_html($card['from']) . '</span> ' : '') . '<strong>' . esc_html($card['amount']) . '</strong>' . ($card['cadence'] ? ' <span>' . esc_html($card['cadence']) . '</span>' : '');

    $message = abf_accounting_block_p('abf-breadcrumb', '<a href="' . esc_url($crumb_url) . '">' . esc_html($d['crumb']) . '</a><span aria-hidden="true">/</span><span aria-current="page">' . esc_html($d['current']) . '</span>')
        . abf_accounting_block_h(1, 'abf-page-title', abf_accounting_copy($d['title']))
        . abf_accounting_block_p('abf-offer-hero__price', abf_accounting_copy($d['price']))
        . abf_accounting_block_p('abf-page-lead', esc_html($d['lead']))
        . abf_accounting_block_buttons(
            'abf-offer-hero__actions',
            array(
                array(esc_html($d['buttons'][0]), abf_accounting_button_url($d['buttons'][0], $slug), 'abf-button--lg abf-button--arrow'),
                array(esc_html($d['buttons'][1]), home_url('/contact/'), 'abf-button--phone abf-button--outline-green'),
            )
        )
        . abf_accounting_block_list('abf-assurances', array_map('esc_html', $d['assurances']));

    $price_card = abf_accounting_block_group(
        'abf-price-card__head',
        abf_accounting_block_group(
            'abf-price-card__title-row',
            abf_accounting_block_image('abf-price-card__icon', $icon)
            . abf_accounting_block_p('abf-price-card__label', esc_html($card['label']))
        )
        . abf_accounting_block_p('abf-price-card__price' . (preg_match('/\d/', $card['amount']) ? '' : ' is-quote'), $price)
        . abf_accounting_block_p('abf-price-card__who', esc_html($card['who']))
    )
        . abf_accounting_block_list('abf-price-card__list', array_map('esc_html', $card['items']))
        . abf_accounting_block_group(
            'abf-price-card__foot',
            abf_accounting_block_buttons('abf-price-card__actions', array(array(esc_html($card['button']), abf_accounting_button_url($card['button'], $slug), 'abf-button--block abf-button--arrow')))
            . ($card['small'] ? abf_accounting_block_p('abf-price-card__small', esc_html($card['small'])) : '')
        );

    return abf_accounting_block_section(
        'abf-offer-hero',
        abf_accounting_block_group('abf-offer-hero__message', $message)
        . abf_accounting_block_group('abf-price-card', $price_card)
    );
}

/**
 * Explainer: checklist card beside the "What is it" text.
 *
 * @param array  $d    Explainer data.
 * @param string $icon Card icon URL.
 */
function abf_accounting_explainer(array $d, string $icon): string
{
    $widths = array(77, 56, 42, 63);
    $rows   = '';
    foreach (array_values($d['rows']) as $i => $label) {
        $rows .= abf_accounting_block_p('abf-checklist__row', esc_html($label) . '<span class="abf-checklist__track" aria-hidden="true"><span style="width:' . (int) round($widths[$i % 4] * 5.04) . 'px"></span></span>');
    }

    $visual = abf_accounting_block_group(
        'abf-checklist',
        abf_accounting_block_group(
            'abf-checklist__head',
            abf_accounting_block_image('abf-checklist__icon', $icon)
            . abf_accounting_block_group(
                'abf-checklist__title',
                abf_accounting_block_p('abf-checklist__name', esc_html($d['cardTitle']))
                . abf_accounting_block_p('abf-checklist__sub', esc_html($d['cardSub']))
            )
            . abf_accounting_block_p('abf-checklist__chip', esc_html($d['chip']))
        )
        . $rows
    );

    $body = $d['body'];

    return abf_accounting_block_section(
        'abf-needs',
        abf_accounting_block_group('abf-needs__visual', $visual)
        . abf_accounting_block_group(
            'abf-needs__text',
            abf_accounting_heading(esc_html($d['eyebrow']), abf_accounting_copy($d['title']), esc_html($body[0] ?? ''), 'abf-heading--caps')
            . (isset($body[1]) ? abf_accounting_block_p('abf-needs__more', esc_html($body[1])) : '')
        )
    );
}

/**
 * The page-specific feature section (see inc/data/feature-sections.json for the types).
 *
 * @param array $f Section config.
 */
function abf_accounting_feature_section(array $f): string
{
    $icon = static fn (string $name): string => get_theme_file_uri('assets/icons/pages/' . $name . '.svg');
    $dark = ! empty($f['dark']);
    $head = abf_accounting_heading(esc_html($f['eyebrow'] ?? ''), abf_accounting_copy($f['title']), esc_html($f['intro'] ?? ''), 'abf-heading--caps abf-heading--center' . ($dark ? ' abf-heading--light' : ''));
    $body = '';

    switch ($f['type']) {
        case 'tree':
            $leaves = '';
            foreach ($f['items'] as $item) {
                $inner = ! empty($item['icon'])
                    ? abf_accounting_block_group(
                        'abf-tree__card-head',
                        abf_accounting_block_image('abf-tree__icon', $icon($item['icon']))
                        . abf_accounting_block_group(
                            'abf-tree__card-title',
                            abf_accounting_block_p('abf-tree__kicker', esc_html($item['kicker'] ?? ''))
                            . abf_accounting_block_h(3, 'abf-tree__title', esc_html($item['title']))
                        )
                    )
                    : abf_accounting_block_h(3, 'abf-tree__title', esc_html($item['title']));
                $inner  .= ! empty($item['ticks']) ? abf_accounting_block_list('abf-tick-list', array_map('esc_html', $item['ticks'])) : abf_accounting_block_p('abf-tree__text', esc_html($item['text'] ?? ''));
                $leaves .= abf_accounting_block_group('abf-tree__card', $inner);
            }
            $body = abf_accounting_block_group(
                'abf-tree' . (count($f['items']) > 2 ? ' abf-tree--wide' : ''),
                abf_accounting_block_group(
                    'abf-tree__root',
                    abf_accounting_block_image('abf-tree__root-icon', $icon($f['root']['icon']))
                    . abf_accounting_block_group(
                        'abf-tree__root-copy',
                        abf_accounting_block_p('abf-tree__root-title', esc_html($f['root']['title']))
                        . abf_accounting_block_p('abf-tree__root-sub', esc_html($f['root']['sub']))
                    )
                )
                . abf_accounting_block_group('abf-tree__leaves', $leaves)
            );
            break;

        case 'cards':
            $cards = '';
            foreach (array_values($f['items']) as $i => $item) {
                $cards .= abf_accounting_block_group(
                    'abf-feature-card',
                    abf_accounting_block_group(
                        'abf-feature-card__head',
                        abf_accounting_block_image('abf-feature-card__icon', $icon($item['icon']))
                        . (! empty($f['numbered']) ? abf_accounting_block_p('abf-feature-card__num', sprintf('%02d', $i + 1)) : '')
                    )
                    . abf_accounting_block_h(3, 'abf-feature-card__title', esc_html($item['title']))
                    . abf_accounting_block_p('abf-feature-card__text', esc_html($item['text']))
                );
            }
            $body = abf_accounting_block_group('abf-feature-cards', $cards);
            break;

        case 'columns':
        case 'lanes':
            $cols = '';
            foreach ($f['items'] as $item) {
                $list = 'columns' === $f['type']
                    ? abf_accounting_block_list('abf-tick-list', array_map('esc_html', $item['ticks']))
                    : abf_accounting_block_list('abf-tick-list abf-tick-list--points', array_map(static fn ($p) => '<strong>' . esc_html($p[0]) . '</strong>' . esc_html($p[1]), $item['points']));
                $cols .= abf_accounting_block_group(
                    'abf-lane',
                    abf_accounting_block_group(
                        'abf-lane__head',
                        abf_accounting_block_image('abf-lane__icon', $icon($item['icon']))
                        . abf_accounting_block_group(
                            'abf-lane__title-wrap',
                            (! empty($item['kicker']) ? abf_accounting_block_p('abf-lane__kicker', esc_html($item['kicker'])) : '')
                            . abf_accounting_block_h(3, 'abf-lane__title', esc_html($item['title']))
                        )
                    )
                    . (! empty($item['text']) ? abf_accounting_block_p('abf-lane__text', esc_html($item['text'])) : '')
                    . $list
                );
            }
            $body = abf_accounting_block_group('abf-lanes abf-lanes--' . count($f['items']), $cols);
            break;

        case 'route':
            $steps = '';
            foreach (array_values($f['items']) as $i => [$title, $text]) {
                $steps .= abf_accounting_block_group(
                    'abf-route__step',
                    abf_accounting_block_p('abf-route__num', sprintf('%02d', $i + 1))
                    . abf_accounting_block_h(3, 'abf-route__title', esc_html($title))
                    . abf_accounting_block_p('abf-route__text', esc_html($text))
                );
            }
            $body = abf_accounting_block_group('abf-route', $steps)
                . (! empty($f['footnote']) ? abf_accounting_block_p('abf-route__note', esc_html($f['footnote'])) : '');
            break;

        case 'checklist':
            $items = array_map(static fn ($p) => '<strong>' . esc_html($p[0]) . '</strong>' . esc_html($p[1]), $f['items']);
            $cta   = $f['cta'];
            $body  = abf_accounting_block_list('abf-check-grid', $items)
                . abf_accounting_block_group(
                    'abf-check-cta',
                    abf_accounting_block_group(
                        'abf-check-cta__copy',
                        abf_accounting_block_p('abf-check-cta__title', esc_html($cta['title']))
                        . abf_accounting_block_p('abf-check-cta__text', esc_html($cta['text']))
                    )
                    . abf_accounting_block_buttons(
                        'abf-check-cta__actions',
                        array(
                            array(esc_html($cta['buttons'][0]), abf_accounting_buy_url('self-assessment')),
                            array(esc_html($cta['buttons'][1]), home_url('/contact/'), 'abf-button--ghost'),
                        )
                    )
                );
            break;
    }

    return abf_accounting_block_section('abf-feature-section abf-feature-section--' . $f['type'] . ($dark ? ' abf-section--dark is-dark' : ''), $head . $body);
}

/**
 * The full landing page for a service or audience slug.
 *
 * @param string $slug Page slug.
 */
function abf_accounting_landing_page(string $slug): string
{
    $pages = abf_accounting_data('landing-pages');
    if (empty($pages[$slug])) {
        return '';
    }
    $d        = $pages[$slug];
    $features = abf_accounting_data('feature-sections');
    $answers  = abf_accounting_data('faq-answers');
    $is_svc   = 'svc' === $d['kind'];
    $pattern  = static fn (string $name): string => '<!-- wp:pattern {"slug":"abf-accounting/' . $name . '"} /-->';
    $icon_url = static function (string $name): string {
        $file = 'assets/icons/pages/' . $name . '.svg';

        return is_readable(get_theme_file_path($file)) ? get_theme_file_uri($file) : get_theme_file_uri('assets/icons/why-1.svg');
    };

    $out  = abf_accounting_offer_hero($d['hero'], $slug, $is_svc ? home_url('/pricing/') : home_url('/'));
    $out .= $pattern('global-trust-strip');
    $out .= abf_accounting_explainer($d['explainer'], $icon_url($slug . '-explainer'));

    $feature = $features[$slug] ?? array();
    if ($feature) {
        $out .= 'pattern' === $feature['type'] ? $pattern($feature['pattern']) : abf_accounting_feature_section($feature);
    }

    $cards = array();
    foreach (array_values($d['included']['cards']) as $i => [$title, $text]) {
        $cards[] = array('pages/' . $slug . '-inc-' . ($i + 1), $title, $text);
    }
    foreach ($cards as &$card) {
        if (! is_readable(get_theme_file_path('assets/icons/' . $card[0] . '.svg'))) {
            $card[0] = 'why-1';
        }
    }
    unset($card);
    $out .= abf_accounting_block_section(
        'abf-why',
        abf_accounting_heading(esc_html($d['included']['eyebrow']), abf_accounting_copy($d['included']['title']), esc_html($d['included']['intro']), 'abf-heading--caps')
        . abf_accounting_icon_cards(array_map(static fn ($c) => array($c[0], esc_html($c[1]), esc_html($c[2])), $cards))
    );

    $out .= $pattern('global-mid-cta');
    $out .= abf_accounting_timeline(esc_html($d['steps']['eyebrow']), abf_accounting_copy($d['steps']['title']), array_map(static fn ($s) => array(esc_html($s[0]), esc_html($s[1])), $d['steps']['steps']));
    $out .= abf_accounting_audience_cards(
        esc_html($d['audience']['eyebrow']),
        abf_accounting_copy($d['audience']['title']),
        array_map(static fn ($c) => array(esc_html($c['title']), esc_html($c['text']), $c['link'], $c['link'] ? abf_accounting_link_for($c['link']) : ''), $d['audience']['cards'])
    );
    $out .= $pattern('home-trust-stream');

    $faq = array();
    foreach (array_values($d['faq']['items']) as $i => [$question, $answer]) {
        $answer = $answer ? $answer : ($answers[$slug][$i - 1] ?? '');
        if ($answer) {
            $faq[] = array(esc_html($question), esc_html($answer));
        }
    }
    $out .= abf_accounting_faq($faq, array('eyebrow' => esc_html($d['faq']['eyebrow']), 'title' => abf_accounting_copy($d['faq']['title']), 'intro' => esc_html($d['faq']['intro'])));
    $out .= $pattern('global-final-cta');

    return $out;
}

/**
 * Register one pattern per landing page: abf-accounting/landing-<slug>.
 */
function abf_accounting_register_landing_patterns(): void
{
    foreach (array_keys(abf_accounting_data('landing-pages')) as $slug) {
        register_block_pattern(
            'abf-accounting/landing-' . $slug,
            array(
                'title'      => sprintf(
                    /* translators: %s: page name */
                    __('Landing page — %s', 'abf-accounting'),
                    abf_accounting_data('landing-pages')[$slug]['hero']['current']
                ),
                'categories' => array('abf-sections'),
                'inserter'   => false,
                'content'    => abf_accounting_landing_page($slug),
            )
        );
    }
}
add_action('init', 'abf_accounting_register_landing_patterns', 20);
