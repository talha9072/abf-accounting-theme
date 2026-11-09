<?php
/**
 * Block markup builders for patterns.
 *
 * Patterns are stored as ordinary core blocks so every section stays editable. These helpers
 * only write the serialised block comments and wrapper HTML in the exact form the editor
 * produces, so the blocks validate when a page is opened in the editor.
 *
 * Text passed in is trusted theme copy (it may hold <br> or <em>); escape anything else first.
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Block comment attributes, JSON encoded the way the editor serialises them.
 */
function abf_accounting_block_attrs(array $attrs): string
{
    return $attrs ? ' ' . wp_json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
}

function abf_accounting_block_p(string $class, string $html): string
{
    return sprintf(
        "<!-- wp:paragraph%s -->\n<p class=\"%s\">%s</p>\n<!-- /wp:paragraph -->",
        abf_accounting_block_attrs(array('className' => $class)),
        esc_attr($class),
        $html
    );
}

function abf_accounting_block_h(int $level, string $class, string $html): string
{
    $attrs = 2 === $level ? array('className' => $class) : array('level' => $level, 'className' => $class);

    return sprintf(
        "<!-- wp:heading%s -->\n<h%d class=\"wp-block-heading %s\">%s</h%d>\n<!-- /wp:heading -->",
        abf_accounting_block_attrs($attrs),
        $level,
        esc_attr($class),
        $html,
        $level
    );
}

/**
 * Group block wrapper. Pass the inner block markup already joined.
 *
 * @param string $class  Class names.
 * @param string $inner  Inner blocks.
 * @param string $tag    HTML tag.
 * @param array  $extra  Extra block attributes (anchor, align).
 */
function abf_accounting_block_group(string $class, string $inner, string $tag = 'div', array $extra = array()): string
{
    $attrs = array();
    if ('div' !== $tag) {
        $attrs['tagName'] = $tag;
    }
    if (! empty($extra['anchor'])) {
        $attrs['anchor'] = $extra['anchor'];
    }
    if (! empty($extra['align'])) {
        $attrs['align'] = $extra['align'];
    }
    $attrs['className'] = $class;
    $attrs['layout']    = array('type' => 'default');

    $classes = 'wp-block-group' . (! empty($extra['align']) ? ' align' . $extra['align'] : '') . ' ' . $class;
    $id      = ! empty($extra['anchor']) ? ' id="' . esc_attr($extra['anchor']) . '"' : '';

    return sprintf(
        "<!-- wp:group%s -->\n<%s%s class=\"%s\">%s</%s>\n<!-- /wp:group -->",
        abf_accounting_block_attrs($attrs),
        $tag,
        $id,
        esc_attr($classes),
        $inner,
        $tag
    );
}

/**
 * Full-width section with the shared container inside.
 */
function abf_accounting_block_section(string $class, string $inner, array $extra = array()): string
{
    return abf_accounting_block_group(
        'abf-section ' . $class,
        abf_accounting_block_group('abf-container', $inner),
        'section',
        array('align' => 'full') + $extra
    );
}

/**
 * Empty decorative group (accent rules, dividers).
 */
function abf_accounting_block_rule(string $class = 'abf-accent'): string
{
    return abf_accounting_block_group($class, '');
}

function abf_accounting_block_image(string $class, string $src, string $alt = '', int $width = 0, int $height = 0): string
{
    $size = ''; // Sizes come from CSS; width/height attributes would not validate in the editor.

    return sprintf(
        "<!-- wp:image%s -->\n<figure class=\"wp-block-image size-full %s\"><img src=\"%s\" alt=\"%s\"%s/></figure>\n<!-- /wp:image -->",
        abf_accounting_block_attrs(array('sizeSlug' => 'full', 'linkDestination' => 'none', 'className' => $class)),
        esc_attr($class),
        esc_url($src),
        esc_attr($alt),
        $size
    );
}

/**
 * Buttons block. Each button: array(label, url, extra class).
 */
function abf_accounting_block_buttons(string $class, array $buttons): string
{
    $inner = '';
    foreach ($buttons as $button) {
        [$label, $url] = $button;
        $btn_class     = $button[2] ?? '';
        $inner        .= sprintf(
            "<!-- wp:button%s -->\n<div class=\"%s\"><a class=\"wp-block-button__link wp-element-button\" href=\"%s\">%s</a></div>\n<!-- /wp:button -->",
            abf_accounting_block_attrs($btn_class ? array('className' => $btn_class) : array()),
            esc_attr(trim('wp-block-button ' . $btn_class)),
            esc_url($url),
            $label
        );
    }

    return sprintf(
        "<!-- wp:buttons%s -->\n<div class=\"wp-block-buttons %s\">%s</div>\n<!-- /wp:buttons -->",
        abf_accounting_block_attrs(array('className' => $class)),
        esc_attr($class),
        $inner
    );
}

/**
 * List block from an array of item HTML.
 */
function abf_accounting_block_list(string $class, array $items, bool $ordered = false): string
{
    $tag   = $ordered ? 'ol' : 'ul';
    $inner = '';
    foreach ($items as $item) {
        $inner .= "<!-- wp:list-item -->\n<li>{$item}</li>\n<!-- /wp:list-item -->";
    }
    $attrs = $ordered ? array('ordered' => true, 'className' => $class) : array('className' => $class);

    return sprintf(
        "<!-- wp:list%s -->\n<%s class=\"wp-block-list %s\">%s</%s>\n<!-- /wp:list -->",
        abf_accounting_block_attrs($attrs),
        $tag,
        esc_attr($class),
        $inner,
        $tag
    );
}

/**
 * Cover block: a full-width section over a background image.
 *
 * @param string $class      Class names.
 * @param string $image      Image URL.
 * @param string $inner      Inner blocks.
 * @param int    $min_height Minimum height in px.
 * @param string $tag        HTML tag.
 */
function abf_accounting_block_cover(string $class, string $image, string $inner, int $min_height, string $tag = 'section'): string
{
    $attrs = array(
        'url'           => $image,
        'dimRatio'      => 0,
        'minHeight'     => $min_height,
        'minHeightUnit' => 'px',
        'isDark'        => true,
    );
    if ('div' !== $tag) {
        $attrs['tagName'] = $tag;
    }
    $attrs['align']     = 'full';
    $attrs['className'] = $class;
    $attrs['layout']    = array('type' => 'default');

    return sprintf(
        "<!-- wp:cover%s -->\n<%s class=\"wp-block-cover alignfull %s\" style=\"min-height:%dpx\"><img class=\"wp-block-cover__image-background\" alt=\"\" src=\"%s\" data-object-fit=\"cover\"/><span aria-hidden=\"true\" class=\"wp-block-cover__background has-background-dim-0 has-background-dim\"></span><div class=\"wp-block-cover__inner-container\">%s</div></%s>\n<!-- /wp:cover -->",
        abf_accounting_block_attrs($attrs),
        $tag,
        esc_attr($class),
        $min_height,
        esc_url($image),
        $inner,
        $tag
    );
}

/**
 * Section heading: accent rule, eyebrow, title and optional intro.
 *
 * @param string $eyebrow Eyebrow text.
 * @param string $title   Title HTML.
 * @param string $intro   Intro HTML (optional).
 * @param bool   $accent  Show the accent rule above.
 * @param string $class   Extra class for the wrapper.
 */
function abf_accounting_block_heading(string $eyebrow, string $title, string $intro = '', bool $accent = true, string $class = ''): string
{
    $inner = ($accent ? abf_accounting_block_rule() : '')
        . abf_accounting_block_p('abf-eyebrow', $eyebrow)
        . abf_accounting_block_h(2, 'abf-title', $title)
        . ($intro ? abf_accounting_block_p('abf-intro', $intro) : '');

    return abf_accounting_block_group(trim('abf-heading ' . $class), $inner);
}
