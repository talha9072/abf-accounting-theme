<?php
/**
 * Resources (blog): main-query rules, Query Loop tweaks and the dynamic pieces the blog
 * templates show (archive hero, category chips, sidebar, breadcrumb, related posts).
 *
 * Templates: home.html (Resources = posts page), archive.html (categories), search.html,
 * single.html (wide, with sidebar) and single-narrow.html (custom template for posts).
 *
 * The featured card on Resources and on a category shows the newest post; the grid below
 * starts from the second post, so the main query is offset by one on those views.
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

const ABF_ACCOUNTING_POSTS_PER_PAGE = 6;

/**
 * Grid views (Resources, categories, search) show six posts. Resources and category pages skip
 * the newest post because it is already the featured card. Search only lists articles.
 */
function abf_accounting_blog_main_query(WP_Query $query): void
{
    if (is_admin() || ! $query->is_main_query()) {
        return;
    }

    if ($query->is_search()) {
        $query->set('post_type', 'post');
        $query->set('posts_per_page', ABF_ACCOUNTING_POSTS_PER_PAGE);
        return;
    }

    if ($query->is_home() || $query->is_category()) {
        $paged = max(1, (int) $query->get('paged'));
        $query->set('posts_per_page', ABF_ACCOUNTING_POSTS_PER_PAGE);
        $query->set('ignore_sticky_posts', true);
        $query->set('offset', 1 + ($paged - 1) * ABF_ACCOUNTING_POSTS_PER_PAGE);
    }
}
add_action('pre_get_posts', 'abf_accounting_blog_main_query');

/**
 * Keep pagination right while the first post is skipped.
 */
function abf_accounting_blog_found_posts(int $found, WP_Query $query): int
{
    if (! is_admin() && $query->is_main_query() && ($query->is_home() || $query->is_category()) && ! $query->is_search()) {
        return max(0, $found - 1);
    }

    return $found;
}
add_filter('found_posts', 'abf_accounting_blog_found_posts', 10, 2);

/**
 * Query Loop blocks marked in the templates:
 *  - "abfFeatured": the newest post (in the current category on a category page);
 *  - "abfRelated": three posts from the current post's categories, excluding it.
 */
function abf_accounting_blog_query_vars(array $vars, WP_Block $block): array
{
    $query = $block->context['query'] ?? array();

    if (! empty($query['abfFeatured'])) {
        $vars['posts_per_page']      = 1;
        $vars['offset']              = 0;
        $vars['ignore_sticky_posts'] = true;
        unset($vars['paged']);
        if (is_category()) {
            $vars['cat'] = get_queried_object_id();
        }
    }

    if (! empty($query['abfRelated'])) {
        $post_id                     = get_queried_object_id();
        $vars['posts_per_page']      = 3;
        $vars['post__not_in']        = array($post_id);
        $vars['ignore_sticky_posts'] = true;
        // Same category when it has three other posts; otherwise the latest articles.
        $cats = wp_get_post_categories($post_id);
        if ($cats) {
            $others = get_posts(
                array(
                    'category__in'   => $cats,
                    'post__not_in'   => array($post_id),
                    'posts_per_page' => 3,
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                )
            );
            if (count($others) >= 3) {
                $vars['category__in'] = $cats;
            }
        }
    }

    return $vars;
}
add_filter('query_loop_block_query_vars', 'abf_accounting_blog_query_vars', 10, 2);

/**
 * Estimated reading time in minutes (200 words a minute, at least one).
 */
function abf_accounting_reading_time($post = null): int
{
    $words = str_word_count(wp_strip_all_tags((string) get_post_field('post_content', $post)));

    return max(1, (int) ceil($words / 200));
}

/**
 * "Published date · 5 min read": post-date blocks with the abf-readtime class get the reading
 * time added. Posts without a featured image get the theme's office photo instead of a gap.
 */
function abf_accounting_blog_render_block(string $html, array $block, WP_Block $instance): string
{
    $class = $block['attrs']['className'] ?? '';

    if ('core/post-date' === $block['blockName'] && str_contains($class, 'abf-readtime') && $html) {
        /* translators: %d: minutes. */
        $read = sprintf(_n('%d min read', '%d min read', abf_accounting_reading_time($instance->context['postId'] ?? null), 'abf-accounting'), abf_accounting_reading_time($instance->context['postId'] ?? null));
        $html = preg_replace('#</time>#', '</time><span class="abf-readtime__sep" aria-hidden="true"> · </span><span class="abf-readtime__min">' . esc_html($read) . '</span>', $html, 1);
    }

    if ('core/post-featured-image' === $block['blockName'] && '' === trim($html) && str_contains($class, 'abf-fallback-image')) {
        $post_id = $instance->context['postId'] ?? 0;
        $files   = array('blog-office-desk-with-plants.webp', 'blog-modern-office-desk.webp');
        $img     = sprintf(
            '<img src="%s" alt="" loading="lazy" decoding="async">',
            esc_url(get_theme_file_uri('assets/img/' . $files[$post_id % 2]))
        );
        $link    = ! empty($block['attrs']['isLink']) ? '<a href="' . esc_url(get_permalink($post_id)) . '" tabindex="-1" aria-hidden="true">' . $img . '</a>' : $img;
        $html    = '<figure class="wp-block-post-featured-image ' . esc_attr($class) . '">' . $link . '</figure>';
    }

    return $html;
}
add_filter('render_block', 'abf_accounting_blog_render_block', 10, 3);

/**
 * Blog categories shown as chips and in the sidebar (empty ones and Uncategorized left out).
 *
 * @return WP_Term[]
 */
function abf_accounting_blog_categories(): array
{
    $terms = get_categories(
        array(
            'hide_empty' => true,
            'exclude'    => array((int) get_option('default_category')),
        )
    );
    if (! is_array($terms)) {
        return array();
    }

    // Optional "abf_order" term meta sets the chip order; the rest follow by name.
    $order = static fn (WP_Term $t): int => '' === get_term_meta($t->term_id, 'abf_order', true) ? 999 : (int) get_term_meta($t->term_id, 'abf_order', true);
    usort($terms, static fn (WP_Term $a, WP_Term $b): int => array($order($a), $a->name) <=> array($order($b), $b->name));

    return $terms;
}

function abf_accounting_blog_url(): string
{
    $page = (int) get_option('page_for_posts');

    return $page ? get_permalink($page) : home_url('/resources/');
}

/**
 * Search box (Figma "Search"): icon, field and green button. Searches articles.
 */
function abf_accounting_blog_search(string $placeholder, string $class = ''): string
{
    $id = wp_unique_id('abf-search-');

    return sprintf(
        '<form role="search" method="get" class="abf-search %1$s" action="%2$s"><label class="screen-reader-text" for="%3$s">%4$s</label><input type="search" id="%3$s" name="s" value="%5$s" placeholder="%6$s"><button type="submit" class="abf-search__button">%7$s</button></form>',
        esc_attr($class),
        esc_url(home_url('/')),
        esc_attr($id),
        esc_html__('Search articles', 'abf-accounting'),
        esc_attr(get_search_query()),
        esc_attr($placeholder),
        esc_html__('Search', 'abf-accounting')
    );
}

/**
 * Category chips: All + each category, the current one dark.
 */
function abf_accounting_blog_chips(): string
{
    $current = is_category() ? get_queried_object_id() : 0;
    $chips   = sprintf(
        '<li><a class="abf-chip%s" href="%s"%s>%s</a></li>',
        (is_home() && ! is_search()) ? ' is-active' : '',
        esc_url(abf_accounting_blog_url()),
        (is_home() && ! is_search()) ? ' aria-current="page"' : '',
        esc_html__('All', 'abf-accounting')
    );
    foreach (abf_accounting_blog_categories() as $term) {
        $active = $term->term_id === $current;
        $chips .= sprintf(
            '<li><a class="abf-chip%s" href="%s"%s>%s</a></li>',
            $active ? ' is-active' : '',
            esc_url(get_category_link($term)),
            $active ? ' aria-current="page"' : '',
            esc_html($term->name)
        );
    }

    return '<nav class="abf-chips" aria-label="' . esc_attr__('Article categories', 'abf-accounting') . '"><ul>' . $chips . '</ul></nav>';
}

/**
 * Wraps raw HTML in a core/html block so it stays a single editable block in patterns.
 */
function abf_accounting_block_html(string $html): string
{
    return "<!-- wp:html -->\n" . $html . "\n<!-- /wp:html -->";
}

/**
 * Hero for category, archive and search views (Figma "Section / Blog / Hero" on the
 * Category, Search and No Results frames).
 */
function abf_accounting_blog_archive_hero(): string
{
    global $wp_query;

    if (is_search()) {
        $total = (int) $wp_query->found_posts;
        $term  = get_search_query();
        /* translators: %s: search terms. */
        $title = $total ? sprintf(__('Results for “%s”', 'abf-accounting'), esc_html($term)) : sprintf(__('No results for “%s”', 'abf-accounting'), esc_html($term));
        /* translators: %d: number of articles. */
        $text   = $total ? sprintf(_n('%d article matches your search.', '%d articles match your search.', $total, 'abf-accounting'), $total) : __('Check the spelling, try a shorter word, or browse a category below.', 'abf-accounting');
        $kicker = __('Search results', 'abf-accounting');
        $hint   = __('Search ABF insights', 'abf-accounting');
    } else {
        $kicker = is_category() ? __('Blog category', 'abf-accounting') : __('Resources', 'abf-accounting');
        $title  = esc_html(single_term_title('', false) ?: wp_strip_all_tags(get_the_archive_title()));
        $desc   = is_category() ? wp_strip_all_tags(term_description()) : '';
        /* translators: %s: category name. */
        $text   = $desc ?: sprintf(__('Guides and updates on %s from the ABF Accounting team.', 'abf-accounting'), esc_html(single_term_title('', false) ?: __('accounting', 'abf-accounting')));
        $hint   = is_category() ? __('Search this category', 'abf-accounting') : __('Search ABF insights', 'abf-accounting');
    }

    $inner = abf_accounting_block_p('abf-eyebrow abf-blog-hero__eyebrow', esc_html($kicker))
        . abf_accounting_block_h(1, 'abf-blog-hero__title', $title)
        . abf_accounting_block_p('abf-blog-hero__text', esc_html($text))
        . abf_accounting_block_html(abf_accounting_blog_search($hint, 'abf-search--hero') . abf_accounting_blog_chips());

    return abf_accounting_block_section('abf-blog-hero abf-blog-hero--archive', $inner);
}

/**
 * Grid heading: "All articles" / "More in Tax" / "6 results" / "Try one of these instead".
 */
function abf_accounting_blog_grid_heading(): string
{
    global $wp_query;

    if (is_search()) {
        $total = (int) $wp_query->found_posts;
        /* translators: %d: number of results. */
        $text = $total ? sprintf(_n('%d result', '%d results', $total, 'abf-accounting'), $total) : __('Try one of these instead', 'abf-accounting');
    } elseif (is_category()) {
        /* translators: %s: category name. */
        $text = sprintf(__('More in %s', 'abf-accounting'), single_term_title('', false));
    } else {
        $text = __('All articles', 'abf-accounting');
    }

    return abf_accounting_block_h(2, 'abf-blog-subtitle', esc_html($text));
}

/**
 * Breadcrumb and category chip above a post title.
 */
function abf_accounting_post_breadcrumb(): string
{
    $post_id = get_queried_object_id();
    $cats    = get_the_category($post_id);
    $cat     = $cats ? $cats[0] : null;

    $crumbs = '<a href="' . esc_url(abf_accounting_blog_url()) . '">' . esc_html__('Resources', 'abf-accounting') . '</a>';
    if ($cat) {
        $crumbs .= '<span aria-hidden="true">/</span><a href="' . esc_url(get_category_link($cat)) . '" aria-current="page">' . esc_html($cat->name) . '</a>';
    }

    $html = '<nav class="abf-breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'abf-accounting') . '">' . $crumbs . '</nav>';
    if ($cat) {
        $html .= '<a class="abf-cat-chip" href="' . esc_url(get_category_link($cat)) . '">' . esc_html($cat->name) . '</a>';
    }

    return abf_accounting_block_html($html);
}

/**
 * Post sidebar: search, categories, recent posts.
 */
function abf_accounting_post_sidebar(): string
{
    $cats = '';
    foreach (abf_accounting_blog_categories() as $term) {
        $cats .= '<li><a href="' . esc_url(get_category_link($term)) . '">' . esc_html($term->name) . '</a></li>';
    }

    $recent = '';
    $posts  = get_posts(
        array(
            'numberposts'         => 3,
            'post__not_in'        => array(get_queried_object_id()),
            'ignore_sticky_posts' => true,
        )
    );
    foreach ($posts as $i => $post) {
        $thumb = get_the_post_thumbnail($post, 'thumbnail', array('alt' => '', 'loading' => 'lazy'));
        if (! $thumb) {
            $thumb = '<img src="' . esc_url(get_theme_file_uri('assets/img/' . ($post->ID % 2 ? 'blog-modern-office-desk.webp' : 'blog-office-desk-with-plants.webp'))) . '" alt="" loading="lazy">';
        }
        $recent .= sprintf(
            '<li><a href="%s"><span class="abf-recent__thumb">%s</span><span class="abf-recent__copy"><span class="abf-recent__title">%s</span><span class="abf-recent__date">%s</span></span></a></li>',
            esc_url(get_permalink($post)),
            $thumb,
            esc_html(get_the_title($post)),
            esc_html(get_the_date('j F Y', $post))
        );
    }

    $html = '<div class="abf-side-card"><h2 class="abf-side-card__title">' . esc_html__('Search articles', 'abf-accounting') . '</h2>' . abf_accounting_blog_search(__('Search ABF insights', 'abf-accounting'), 'abf-search--side') . '</div>';
    if ($cats) {
        $html .= '<div class="abf-side-card"><h2 class="abf-side-card__title">' . esc_html__('Categories', 'abf-accounting') . '</h2><ul class="abf-side-cats">' . $cats . '</ul></div>';
    }
    if ($recent) {
        $html .= '<div class="abf-side-card"><h2 class="abf-side-card__title">' . esc_html__('Recent posts', 'abf-accounting') . '</h2><ul class="abf-recent">' . $recent . '</ul></div>';
    }

    return abf_accounting_block_html('<aside class="abf-post-sidebar" aria-label="' . esc_attr__('More from Resources', 'abf-accounting') . '">' . $html . '</aside>');
}

/**
 * Legal pages: hero + numbered sections, copy from inc/data/legal-pages.json.
 */
function abf_accounting_legal_page(string $slug): string
{
    $data = abf_accounting_data('legal-pages')[$slug] ?? null;
    if (! $data) {
        return '';
    }

    $hero = abf_accounting_block_p('abf-eyebrow abf-legal-hero__eyebrow', esc_html__('Legal &amp; privacy', 'abf-accounting'))
        . abf_accounting_block_h(1, 'abf-legal-hero__title', esc_html($data['title']));
    if (! empty($data['updated'])) {
        $hero .= abf_accounting_block_p('abf-legal-hero__updated', esc_html($data['updated']));
    }

    $body = '';
    foreach ($data['sections'] as [$heading, $paragraphs]) {
        $body .= abf_accounting_block_h(2, 'abf-legal__heading', esc_html($heading));
        foreach ($paragraphs as $paragraph) {
            $body .= abf_accounting_block_p('abf-legal__text', make_clickable(esc_html($paragraph)));
        }
    }

    return abf_accounting_block_section('abf-legal-hero', $hero)
        . abf_accounting_block_section('abf-legal', abf_accounting_block_group('abf-legal__article', $body, 'article'));
}

function abf_accounting_register_legal_patterns(): void
{
    foreach (abf_accounting_data('legal-pages') as $slug => $data) {
        register_block_pattern(
            'abf-accounting/legal-' . $slug,
            array(
                'title'      => $data['title'],
                'categories' => array('abf-sections'),
                'inserter'   => false,
                'content'    => abf_accounting_legal_page($slug),
            )
        );
    }
}
add_action('init', 'abf_accounting_register_legal_patterns', 20);

/**
 * Tax calculator script, loaded only where the calculator is on the page.
 */
function abf_accounting_tax_calculator_script(string $html, array $block): string
{
    if ('core/html' === $block['blockName'] && str_contains($html, 'data-abf-taxcalc')) {
        wp_enqueue_script(
            'abf-accounting-tax-calculator',
            get_theme_file_uri('assets/js/tax-calculator.js'),
            array(),
            wp_get_theme()->get('Version'),
            array(
                'in_footer' => true,
                'strategy'  => 'defer',
            )
        );
    }

    return $html;
}
add_filter('render_block', 'abf_accounting_tax_calculator_script', 10, 2);

/**
 * Article card (Figma "Article card"): photo, category, title, excerpt, date · read time.
 */
function abf_accounting_blog_card(): string
{
    return '<!-- wp:group {"tagName":"article","className":"abf-card","layout":{"type":"default"}} -->' . "\n"
        . '<article class="wp-block-group abf-card"><!-- wp:post-featured-image {"isLink":true,"className":"abf-card__image abf-fallback-image"} /-->' . "\n\n"
        . '<!-- wp:group {"className":"abf-card__copy","layout":{"type":"default"}} -->' . "\n"
        . '<div class="wp-block-group abf-card__copy"><!-- wp:post-terms {"term":"category","className":"abf-card__cat"} /-->' . "\n\n"
        . '<!-- wp:post-title {"level":3,"isLink":true,"className":"abf-card__title"} /-->' . "\n\n"
        . '<!-- wp:post-excerpt {"excerptLength":24,"className":"abf-card__excerpt"} /-->' . "\n\n"
        . '<!-- wp:post-date {"format":"j F Y","className":"abf-card__meta abf-readtime"} /--></div>' . "\n"
        . '<!-- /wp:group --></article>' . "\n"
        . '<!-- /wp:group -->';
}

/**
 * Query Loop block. $query holds the query attributes; $inner the blocks inside it.
 */
function abf_accounting_block_query(array $query, string $inner, string $class): string
{
    static $id = 10;
    $attrs = array(
        'queryId'   => ++$id,
        'query'     => $query + array(
            'perPage'  => ABF_ACCOUNTING_POSTS_PER_PAGE,
            'pages'    => 0,
            'offset'   => 0,
            'postType' => 'post',
            'order'    => 'desc',
            'orderBy'  => 'date',
            'author'   => '',
            'search'   => '',
            'exclude'  => array(),
            'sticky'   => '',
            'inherit'  => false,
        ),
        'className' => $class,
        'layout'    => array('type' => 'default'),
    );

    return '<!-- wp:query' . abf_accounting_block_attrs($attrs) . ' -->' . "\n"
        . '<div class="wp-block-query ' . esc_attr($class) . '">' . $inner . '</div>' . "\n"
        . '<!-- /wp:query -->';
}

function abf_accounting_block_post_template(string $class, string $inner): string
{
    return '<!-- wp:post-template' . abf_accounting_block_attrs(array('className' => $class, 'layout' => array('type' => 'default'))) . ' -->' . "\n"
        . $inner . "\n"
        . '<!-- /wp:post-template -->';
}

/**
 * Featured article (Figma "Featured post"): newest post, large photo left, copy right.
 * On Resources the category chips and search sit above it.
 */
function abf_accounting_blog_featured(): string
{
    $card = '<!-- wp:group {"tagName":"article","className":"abf-featured","layout":{"type":"default"}} -->' . "\n"
        . '<article class="wp-block-group abf-featured"><!-- wp:post-featured-image {"isLink":true,"className":"abf-featured__image abf-fallback-image"} /-->' . "\n\n"
        . '<!-- wp:group {"className":"abf-featured__copy","layout":{"type":"default"}} -->' . "\n"
        . '<div class="wp-block-group abf-featured__copy"><!-- wp:post-terms {"term":"category","className":"abf-featured__cat"} /-->' . "\n\n"
        . '<!-- wp:post-title {"isLink":true,"className":"abf-featured__title"} /-->' . "\n\n"
        . '<!-- wp:post-excerpt {"excerptLength":30,"className":"abf-featured__excerpt"} /-->' . "\n\n"
        . '<!-- wp:group {"className":"abf-featured__meta","layout":{"type":"default"}} -->' . "\n"
        . '<div class="wp-block-group abf-featured__meta"><!-- wp:post-author-name {"className":"abf-meta-author"} /-->' . "\n\n"
        . '<!-- wp:post-date {"format":"j F Y","className":"abf-meta-date"} /--></div>' . "\n"
        . '<!-- /wp:group -->' . "\n\n"
        . '<!-- wp:read-more {"content":"' . esc_attr__('Read article', 'abf-accounting') . '","className":"abf-read-more"} /--></div>' . "\n"
        . '<!-- /wp:group --></article>' . "\n"
        . '<!-- /wp:group -->';

    $heading = is_category() ? __('Featured in this category', 'abf-accounting') : __('Latest insight', 'abf-accounting');
    $top     = is_category() ? '' : abf_accounting_block_html('<div class="abf-blog-filters">' . abf_accounting_blog_chips() . abf_accounting_blog_search(__('Search ABF insights', 'abf-accounting'), 'abf-search--filters') . '</div>');

    $inner = $top
        . abf_accounting_block_h(2, 'abf-blog-subtitle', esc_html($heading))
        . abf_accounting_block_query(array('perPage' => 1, 'abfFeatured' => true), abf_accounting_block_post_template('abf-featured-list', $card), 'abf-featured-query');

    return abf_accounting_block_section('abf-blog-featured', $inner);
}

/**
 * Article grid with pagination. Uses the main query (Resources, category, search).
 */
function abf_accounting_blog_grid(): string
{
    $empty = '<!-- wp:query-no-results -->' . "\n"
        . abf_accounting_block_group(
            'abf-empty',
            abf_accounting_block_h(3, 'abf-empty__title', esc_html__('Can’t find what you need?', 'abf-accounting'))
            . abf_accounting_block_p('abf-empty__text', esc_html__('Speak to the ABF team and we’ll point you in the right direction.', 'abf-accounting'))
            . abf_accounting_block_buttons('abf-empty__actions', array(array(__('Contact us', 'abf-accounting'), home_url('/contact/'), '')))
        ) . "\n"
        . '<!-- /wp:query-no-results -->';

    $pagination = '<!-- wp:query-pagination {"paginationArrow":"none","className":"abf-pagination","layout":{"type":"flex","justifyContent":"center"}} -->' . "\n"
        . '<!-- wp:query-pagination-previous {"label":"' . esc_attr__('Previous', 'abf-accounting') . '"} /-->' . "\n\n"
        . '<!-- wp:query-pagination-numbers /-->' . "\n\n"
        . '<!-- wp:query-pagination-next {"label":"' . esc_attr__('Next', 'abf-accounting') . '"} /-->' . "\n"
        . '<!-- /wp:query-pagination -->';

    $inner = abf_accounting_blog_grid_heading()
        . abf_accounting_block_query(
            array('inherit' => true),
            abf_accounting_block_post_template('abf-cards', abf_accounting_blog_card()) . "\n\n" . $pagination . "\n\n" . (is_search() ? $empty : ''),
            'abf-grid-query'
        );

    return abf_accounting_block_section('abf-blog-grid', $inner);
}

/**
 * Related insights under an article (three posts from the same category).
 */
function abf_accounting_post_related(): string
{
    $inner = abf_accounting_block_p('abf-eyebrow', esc_html__('Keep reading', 'abf-accounting'))
        . abf_accounting_block_h(2, 'abf-post-related__title', esc_html__('Related insights', 'abf-accounting'))
        . abf_accounting_block_query(array('perPage' => 3, 'abfRelated' => true), abf_accounting_block_post_template('abf-cards', abf_accounting_blog_card()), 'abf-related-query');

    return abf_accounting_block_section('abf-post-related', $inner);
}

/**
 * Article hero: breadcrumb, category chip, title, standfirst (excerpt), author line, photo.
 */
function abf_accounting_post_hero(bool $narrow = false): string
{
    $inner = '<!-- wp:pattern {"slug":"abf-accounting/post-breadcrumb"} /-->' . "\n\n"
        . '<!-- wp:post-title {"level":1,"className":"abf-post-hero__title"} /-->' . "\n\n"
        . '<!-- wp:post-excerpt {"className":"abf-post-hero__lead"} /-->' . "\n\n"
        . '<!-- wp:group {"className":"abf-post-meta","layout":{"type":"default"}} -->' . "\n"
        . '<div class="wp-block-group abf-post-meta"><!-- wp:avatar {"size":40,"className":"abf-post-meta__avatar"} /-->' . "\n\n"
        . '<!-- wp:post-author-name {"className":"abf-meta-author"} /-->' . "\n\n"
        . '<!-- wp:post-date {"format":"j F Y","className":"abf-meta-date abf-readtime"} /--></div>' . "\n"
        . '<!-- /wp:group -->' . "\n\n"
        . '<!-- wp:post-featured-image {"className":"abf-post-hero__image abf-fallback-image"} /-->';

    return abf_accounting_block_section('abf-post-hero' . ($narrow ? ' abf-post-hero--narrow' : ''), $inner);
}

/**
 * Article body. Wide: content and sidebar side by side. Narrow: one 760px column.
 */
function abf_accounting_post_body(bool $narrow = false): string
{
    $content = '<!-- wp:post-content {"className":"abf-prose","layout":{"type":"default"}} /-->';
    $inner   = $narrow ? $content : $content . "\n\n" . '<!-- wp:pattern {"slug":"abf-accounting/post-sidebar"} /-->';

    return abf_accounting_block_section('abf-post-body' . ($narrow ? ' abf-post-body--narrow' : ''), $inner);
}

/**
 * Header: mark the link for the current page (Resources stays active on articles, categories
 * and search; the Services / Who we help menus on their landing pages).
 */
function abf_accounting_nav_current(string $html, array $block): string
{
    if (is_admin() || ! in_array($block['blockName'], array('core/navigation-link', 'core/navigation-submenu'), true)) {
        return $html;
    }

    $path    = static fn (string $url): string => untrailingslashit((string) wp_parse_url($url, PHP_URL_PATH));
    $link    = $path($block['attrs']['url'] ?? '');
    $current = $path(home_url(add_query_arg(array())));
    $blog    = is_home() || is_category() || is_search() || is_singular('post');

    $home  = $path(home_url('/'));
    $match = ($link === $current && ($link !== $home || is_front_page()))
        ||($blog && $link === $path(abf_accounting_blog_url()))
        || ('core/navigation-submenu' === $block['blockName'] && is_singular(array('abf_service', 'abf_audience')) && '' !== $link && str_starts_with($current, $link));

    if (! $match || str_contains($html, 'current-menu-item')) {
        return $html;
    }

    $html = preg_replace('/class="wp-block-navigation-(item|submenu)/', 'class="current-menu-item wp-block-navigation-$1', $html, 1);
    if ('core/navigation-link' === $block['blockName']) {
        $html = preg_replace('/<a /', '<a aria-current="page" ', $html, 1);
    }

    return $html;
}
add_filter('render_block', 'abf_accounting_nav_current', 10, 2);
