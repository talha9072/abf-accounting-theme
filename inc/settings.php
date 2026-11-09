<?php
/**
 * Appearance → ABF settings: the keys and links the client adds after launch.
 *
 *  - reCAPTCHA v3 site key and secret key (callback form)
 *  - tawk.to property ID and widget ID (the "Chat with us" button)
 *  - Social profile URLs (footer icons)
 *
 * Every feature stays switched off, with no broken output, until its value is saved.
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

const ABF_ACCOUNTING_SETTINGS = 'abf_accounting_settings';

/**
 * Field definitions: key => array(label, type, section, help).
 */
function abf_accounting_setting_fields(): array
{
    return array(
        'recaptcha_site'   => array(__('Site key', 'abf-accounting'), 'text', 'recaptcha', __('reCAPTCHA v3 keys from google.com/recaptcha/admin. Add this site’s domain to the key.', 'abf-accounting')),
        'recaptcha_secret' => array(__('Secret key', 'abf-accounting'), 'secret', 'recaptcha', ''),
        'tawk_property'    => array(__('Property ID', 'abf-accounting'), 'text', 'chat', __('From tawk.to → Administration → Chat Widget: the direct chat link ends in /PROPERTY_ID/WIDGET_ID.', 'abf-accounting')),
        'tawk_widget'      => array(__('Widget ID', 'abf-accounting'), 'text', 'chat', __('Usually "default" or a short code.', 'abf-accounting')),
        'social_instagram' => array(__('Instagram', 'abf-accounting'), 'url', 'social', ''),
        'social_linkedin'  => array(__('LinkedIn', 'abf-accounting'), 'url', 'social', ''),
        'social_facebook'  => array(__('Facebook', 'abf-accounting'), 'url', 'social', ''),
        'social_youtube'   => array(__('YouTube', 'abf-accounting'), 'url', 'social', ''),
        'social_x'         => array(__('X (Twitter)', 'abf-accounting'), 'url', 'social', ''),
    );
}

/**
 * One saved setting ('' when not set).
 */
function abf_accounting_setting(string $key): string
{
    $settings = get_option(ABF_ACCOUNTING_SETTINGS, array());

    return is_array($settings) && isset($settings[$key]) ? (string) $settings[$key] : '';
}

function abf_accounting_sanitize_settings($input): array
{
    $input = is_array($input) ? $input : array();
    $old   = get_option(ABF_ACCOUNTING_SETTINGS, array());
    $clean = array();

    foreach (abf_accounting_setting_fields() as $key => [, $type]) {
        $value = isset($input[$key]) ? trim((string) wp_unslash($input[$key])) : '';
        if ('url' === $type) {
            $clean[$key] = esc_url_raw($value, array('https', 'http'));
        } elseif ('secret' === $type) {
            // Left blank = keep the saved secret; "-" clears it.
            if ('-' === $value) {
                $clean[$key] = '';
            } else {
                $clean[$key] = '' === $value ? (string) ($old[$key] ?? '') : preg_replace('/[^A-Za-z0-9_\-]/', '', $value);
            }
        } else {
            $clean[$key] = preg_replace('/[^A-Za-z0-9_\-]/', '', $value);
        }
    }

    return $clean;
}

function abf_accounting_register_settings(): void
{
    register_setting(
        'abf_accounting',
        ABF_ACCOUNTING_SETTINGS,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'abf_accounting_sanitize_settings',
            'default'           => array(),
            'show_in_rest'      => false,
        )
    );

    $sections = array(
        'recaptcha' => __('reCAPTCHA (callback form)', 'abf-accounting'),
        'chat'      => __('Live chat (tawk.to)', 'abf-accounting'),
        'social'    => __('Social profiles (footer icons)', 'abf-accounting'),
    );
    foreach ($sections as $id => $title) {
        add_settings_section('abf_accounting_' . $id, $title, '__return_false', 'abf-accounting-settings');
    }

    foreach (abf_accounting_setting_fields() as $key => [$label, $type, $section, $help]) {
        add_settings_field(
            $key,
            $label,
            static function () use ($key, $type, $help): void {
                $value = abf_accounting_setting($key);
                $name  = ABF_ACCOUNTING_SETTINGS . '[' . $key . ']';
                if ('secret' === $type) {
                    printf(
                        '<input type="password" class="regular-text" id="%1$s" name="%2$s" value="" autocomplete="new-password" placeholder="%3$s">',
                        esc_attr($key),
                        esc_attr($name),
                        $value ? esc_attr__('Saved — leave blank to keep it, or type - to remove it', 'abf-accounting') : ''
                    );
                } else {
                    printf(
                        '<input type="%1$s" class="regular-text" id="%2$s" name="%3$s" value="%4$s"%5$s>',
                        'url' === $type ? 'url' : 'text',
                        esc_attr($key),
                        esc_attr($name),
                        esc_attr($value),
                        'url' === $type ? ' placeholder="https://"' : ''
                    );
                }
                if ($help) {
                    echo '<p class="description">' . esc_html($help) . '</p>';
                }
            },
            'abf-accounting-settings',
            'abf_accounting_' . $section,
            array('label_for' => $key)
        );
    }
}
add_action('admin_init', 'abf_accounting_register_settings');

function abf_accounting_settings_menu(): void
{
    add_theme_page(
        __('ABF settings', 'abf-accounting'),
        __('ABF settings', 'abf-accounting'),
        'manage_options',
        'abf-accounting-settings',
        static function (): void {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('ABF settings', 'abf-accounting'); ?></h1>
                <p><?php esc_html_e('Each feature switches on as soon as its details are saved. Leave a field empty to keep that feature off.', 'abf-accounting'); ?></p>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('abf_accounting');
                    do_settings_sections('abf-accounting-settings');
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
    );
}
add_action('admin_menu', 'abf_accounting_settings_menu');

/* ---------- reCAPTCHA v3 on the callback form ---------- */

function abf_accounting_recaptcha_enabled(): bool
{
    return '' !== abf_accounting_setting('recaptcha_site') && '' !== abf_accounting_setting('recaptcha_secret');
}

/**
 * Loads Google's script and the small submit handler on pages with the form.
 */
function abf_accounting_recaptcha_assets(): void
{
    if (! abf_accounting_recaptcha_enabled()) {
        return;
    }
    wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . rawurlencode(abf_accounting_setting('recaptcha_site')), array(), null, array('in_footer' => true)); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
    wp_enqueue_script('abf-accounting-recaptcha', get_theme_file_uri('assets/js/recaptcha.js'), array('google-recaptcha'), wp_get_theme()->get('Version'), array('in_footer' => true));
}

/**
 * Checks the token Google gave the browser. Returns false for low scores, a wrong action,
 * or when Google cannot be reached (the form then asks the visitor to call instead).
 */
function abf_accounting_recaptcha_passes(string $token): bool
{
    if (! abf_accounting_recaptcha_enabled()) {
        return true;
    }
    if ('' === $token) {
        return false;
    }

    $response = wp_remote_post(
        'https://www.google.com/recaptcha/api/siteverify',
        array(
            'timeout' => 8,
            'body'    => array(
                'secret'   => abf_accounting_setting('recaptcha_secret'),
                'response' => $token,
                'remoteip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
            ),
        )
    );
    if (is_wp_error($response)) {
        return false;
    }
    $result = json_decode((string) wp_remote_retrieve_body($response), true);

    /**
     * Minimum reCAPTCHA v3 score (0–1) for the callback form.
     *
     * @param float $score Default 0.5.
     */
    $minimum = (float) apply_filters('abf_accounting_recaptcha_min_score', 0.5);

    return ! empty($result['success'])
        && 'callback' === ($result['action'] ?? '')
        && (float) ($result['score'] ?? 0) >= $minimum;
}

/* ---------- Live chat (Figma "Live chat / tawk.to widget") ---------- */

/**
 * The tawk.to script with its own bubble hidden, plus the "Chat with us" button that opens it.
 */
function abf_accounting_live_chat(): void
{
    $property = abf_accounting_setting('tawk_property');
    $widget   = abf_accounting_setting('tawk_widget') ?: 'default';
    if ('' === $property || is_admin()) {
        return;
    }
    $src = 'https://embed.tawk.to/' . rawurlencode($property) . '/' . rawurlencode($widget);
    ?>
    <button type="button" class="abf-chat" id="abf-chat" hidden aria-label="<?php esc_attr_e('Chat with us', 'abf-accounting'); ?>">
        <span class="abf-chat__label"><?php esc_html_e('Chat with us', 'abf-accounting'); ?></span>
        <span class="abf-chat__bubble" aria-hidden="true"></span>
    </button>
    <script>
    (function () {
        var button = document.getElementById('abf-chat');
        window.Tawk_API = window.Tawk_API || {};
        window.Tawk_LoadStart = new Date();
        Tawk_API.onLoad = function () { Tawk_API.hideWidget(); button.hidden = false; };
        Tawk_API.onChatMinimized = function () { Tawk_API.hideWidget(); button.hidden = false; };
        button.addEventListener('click', function () { button.hidden = true; Tawk_API.showWidget(); Tawk_API.maximize(); });
        var s = document.createElement('script');
        s.async = true; s.src = <?php echo wp_json_encode($src); ?>; s.charset = 'UTF-8'; s.setAttribute('crossorigin', '*');
        document.body.appendChild(s);
    })();
    </script>
    <?php
}
add_action('wp_footer', 'abf_accounting_live_chat', 50);

/* ---------- Social share (Open Graph) ---------- */

/**
 * Share title, description and image (Figma "og-image-abf-accounting", 1200 × 630). Skipped
 * when an SEO plugin that writes its own tags is active.
 */
function abf_accounting_open_graph(): void
{
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION')) {
        return;
    }

    $title       = wp_get_document_title();
    $description = get_bloginfo('description');
    $image       = get_theme_file_uri('assets/img/og-image-abf-accounting.jpg');
    $url         = home_url(add_query_arg(array()));
    $type        = 'website';

    if (is_singular()) {
        $post        = get_queried_object();
        $excerpt     = has_excerpt($post) ? get_the_excerpt($post) : '';
        $description = $excerpt ?: $description;
        $url         = get_permalink($post);
        if (is_singular('post')) {
            $type = 'article';
            if (has_post_thumbnail($post)) {
                $image = (string) get_the_post_thumbnail_url($post, 'large');
            }
        }
    }

    $tags = array(
        'og:site_name'   => get_bloginfo('name'),
        'og:type'        => $type,
        'og:title'       => $title,
        'og:description' => wp_strip_all_tags($description),
        'og:url'         => $url,
        'og:image'       => $image,
        'og:locale'      => 'en_GB',
    );
    foreach ($tags as $property => $content) {
        printf('<meta property="%s" content="%s">' . "\n", esc_attr($property), esc_attr($content));
    }
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    printf('<meta name="description" content="%s">' . "\n", esc_attr(wp_strip_all_tags($description)));
}
add_action('wp_head', 'abf_accounting_open_graph', 5);
