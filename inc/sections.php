<?php
/**
 * Shared section markup used by several patterns (headings, CTAs, FAQ, cards).
 *
 * @package ABF_Accounting
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Section heading: eyebrow, H2 title and optional intro.
 *
 * @param string $eyebrow Eyebrow text.
 * @param string $title   Title HTML.
 * @param string $intro   Intro HTML (optional).
 * @param string $class   Extra class for the wrapper.
 */
function abf_accounting_heading(string $eyebrow, string $title, string $intro = '', string $class = ''): string
{
    return abf_accounting_block_group(
        trim('abf-heading ' . $class),
        ($eyebrow ? abf_accounting_block_p('abf-eyebrow', $eyebrow) : '')
        . abf_accounting_block_h(2, 'abf-title', $title)
        . ($intro ? abf_accounting_block_p('abf-intro', $intro) : '')
    );
}

/**
 * FAQ section (Figma "Section / Home / FAQ / Editorial Accordion"): intro with the dark
 * "Still deciding?" card, and an accordion of native <details> blocks (first one open).
 *
 * @param array $items array(question, answer) pairs.
 * @param array $args  eyebrow, title, intro; optional: card (false hides "Still deciding?"),
 *                     contact_link (false hides "Still have a question?"), anchor (default "faq").
 */
function abf_accounting_faq(array $items, array $args): string
{
    $contact = abf_accounting_contact();
    $list    = '';
    foreach (array_values($items) as $i => [$question, $answer]) {
        $attrs = 0 === $i ? array('showContent' => true, 'className' => 'abf-faq__item') : array('className' => 'abf-faq__item');
        $list .= sprintf(
            "<!-- wp:details %s -->\n<details class=\"wp-block-details abf-faq__item\"%s><summary>%s</summary><!-- wp:paragraph {\"className\":\"abf-faq__answer\"} -->\n<p class=\"abf-faq__answer\">%s</p>\n<!-- /wp:paragraph --></details>\n<!-- /wp:details -->",
            wp_json_encode($attrs),
            0 === $i ? ' open' : '',
            $question,
            $answer
        );
    }

    $card = abf_accounting_block_group(
        'abf-decide',
        abf_accounting_block_p('abf-eyebrow abf-eyebrow--caps', __('Still deciding?', 'abf-accounting'))
        . abf_accounting_block_p('abf-decide__title', __('Talk it through with the ABF team before you choose.', 'abf-accounting'))
        . abf_accounting_block_p(
            'abf-decide__phone',
            '<a href="' . esc_attr($contact['phone_uri']) . '">' . esc_html(str_replace(' 141 ', ' 141', $contact['phone'])) . '</a> <br><span>' . esc_html__('Or request a callback below', 'abf-accounting') . '</span>'
        )
        . abf_accounting_block_buttons('abf-decide__actions', array(array(__('Request a callback', 'abf-accounting'), abf_accounting_callback_url(), 'abf-button--phone abf-button--block')))
    );

    $inner = abf_accounting_block_group(
        'abf-faq__intro',
        abf_accounting_heading($args['eyebrow'], $args['title'], $args['intro'], 'abf-heading--caps')
        . (false === ($args['card'] ?? true) ? '' : $card)
    )
        . abf_accounting_block_group(
            'abf-faq__list',
            $list
            . (false === ($args['contact_link'] ?? true) ? '' : abf_accounting_block_p('abf-faq__more', '<a href="' . esc_url(home_url('/contact/')) . '">' . esc_html__('Still have a question? Contact us', 'abf-accounting') . '</a>'))
        );

    return abf_accounting_block_section('abf-faq' . (! empty($args['class']) ? ' ' . $args['class'] : ''), $inner, array('anchor' => $args['anchor'] ?? 'faq'));
}

/**
 * Final CTA above the footer (Figma "Section / Home / Final CTA / Navy Stage").
 */
function abf_accounting_final_cta(): string
{
    $contact = abf_accounting_contact();
    $steps   = array(
        array(__('Tell us about your business', 'abf-accounting'), __('A short quote form covering your business type and the support you need.', 'abf-accounting')),
        array(__('Get a clear recommendation', 'abf-accounting'), __('We confirm the scope and price before any work starts.', 'abf-accounting')),
        array(__('Meet your accountant', 'abf-accounting'), __('Your dedicated accountant gets everything set up and on track.', 'abf-accounting')),
    );
    $list    = '';
    foreach ($steps as $i => [$title, $text]) {
        $list .= abf_accounting_block_group(
            'abf-next__step' . (0 === $i ? ' is-current' : ''),
            abf_accounting_block_p('abf-next__num', (string) ($i + 1))
            . abf_accounting_block_group(
                'abf-next__copy',
                abf_accounting_block_h(3, 'abf-next__title', $title)
                . abf_accounting_block_p('abf-next__text', $text)
            )
        );
    }

    $stage = abf_accounting_block_group(
        'abf-cta__message',
        abf_accounting_block_p('abf-cta__chip', '<strong>' . esc_html__('Fixed prices', 'abf-accounting') . '</strong> <span>' . esc_html__('· Support by phone and email', 'abf-accounting') . '</span>')
        . abf_accounting_block_h(2, 'abf-cta__title', __('Make accounting one less <br>thing to worry about.', 'abf-accounting'))
        . abf_accounting_block_p('abf-cta__text', __('Tell us about your business and we will recommend the right service. Prefer to talk it through first? Book a call with the team.', 'abf-accounting'))
        . abf_accounting_block_buttons(
            'abf-cta__actions',
            array(
                array(__('Buy now', 'abf-accounting'), home_url('/pricing/'), 'abf-button--arrow abf-button--glow'),
                array(__('Request a callback', 'abf-accounting'), abf_accounting_callback_url(), 'abf-button--phone abf-button--ghost'),
            )
        )
        . abf_accounting_block_p('abf-cta__phone', esc_html__('Or call the team on', 'abf-accounting') . ' <a href="' . esc_attr($contact['phone_uri']) . '">' . esc_html(str_replace(' 141 ', ' 141', $contact['phone'])) . '</a>')
    )
        . abf_accounting_block_group(
            'abf-next',
            abf_accounting_block_p('abf-eyebrow abf-eyebrow--caps', __('What happens next', 'abf-accounting'))
            . $list
        );

    return abf_accounting_block_section('abf-cta', abf_accounting_block_group('abf-cta__stage', $stage));
}

/**
 * Mid-page CTA band (Figma "Section / Shared / Mid-page CTA / Contact"): office photo under a
 * teal-to-green overlay, message left, Contact us + phone right.
 *
 * @param array $args eyebrow, title, text.
 */
function abf_accounting_mid_cta(array $args = array()): string
{
    $args    = wp_parse_args(
        $args,
        array(
            'eyebrow' => __('Not sure which option fits?', 'abf-accounting'),
            'title'   => __('Talk it through with the ABF team.', 'abf-accounting'),
            'text'    => __('Not sure which service you need? Speak to ABF before you choose, and we will point you to the right support.', 'abf-accounting'),
        )
    );
    $contact = abf_accounting_contact();

    $inner = abf_accounting_block_group(
        'abf-mid-cta__message',
        abf_accounting_block_p('abf-mid-cta__eyebrow', $args['eyebrow'])
        . abf_accounting_block_h(2, 'abf-mid-cta__title', $args['title'])
        . abf_accounting_block_p('abf-mid-cta__text', $args['text'])
    )
        . abf_accounting_block_group(
            'abf-mid-cta__actions',
            abf_accounting_block_buttons('abf-mid-cta__buttons', array(array(__('Contact us', 'abf-accounting'), home_url('/contact/'), 'abf-button--white')))
            . abf_accounting_block_p('abf-mid-cta__phone', '<a href="' . esc_attr($contact['phone_uri']) . '">' . esc_html__('Or call', 'abf-accounting') . ' ' . esc_html(str_replace(' 141 ', ' 141', $contact['phone'])) . '</a>')
        );

    return abf_accounting_block_cover('abf-mid-cta', get_theme_file_uri('assets/img/home-hero-office-wide.webp'), abf_accounting_block_group('abf-container', $inner), 388);
}

/**
 * Dark trust strip under service heroes (Figma "Section / Service / Trust Strip").
 */
function abf_accounting_trust_strip(): string
{
    $items = array(
        array('strip-1', __('Support by phone and email', 'abf-accounting')),
        array('strip-2', __('A dedicated accountant for every client', 'abf-accounting')),
        array('strip-3', __('Clear pricing with no hidden fees', 'abf-accounting')),
    );
    $inner = '';
    foreach ($items as [$icon, $text]) {
        $inner .= abf_accounting_block_p('abf-strip__item abf-strip__item--' . $icon, $text);
    }

    return abf_accounting_block_section('abf-strip', $inner);
}

/**
 * Vertical step timeline (Figma "Section / Service / How It Works"): centred heading, a
 * spine with numbered nodes and cards alternating left and right.
 *
 * @param string $eyebrow Eyebrow.
 * @param string $title   Title.
 * @param array  $steps   array(title, text) pairs; the last node is drawn as the finish.
 */
function abf_accounting_timeline(string $eyebrow, string $title, array $steps): string
{
    $list = '';
    foreach (array_values($steps) as $i => [$step_title, $text]) {
        $list .= abf_accounting_block_group(
            'abf-timeline__step' . ($i % 2 ? ' is-right' : ' is-left'),
            abf_accounting_block_p('abf-timeline__node', sprintf('%02d', $i + 1))
            . abf_accounting_block_group(
                'abf-timeline__card',
                abf_accounting_block_h(3, 'abf-timeline__title', $step_title)
                . abf_accounting_block_p('abf-timeline__text', $text)
            )
        );
    }

    return abf_accounting_block_section(
        'abf-steps',
        abf_accounting_heading($eyebrow, $title, '', 'abf-heading--caps abf-heading--center')
        . abf_accounting_block_group('abf-timeline', $list)
    );
}

/**
 * "Who it is for" cards (Figma "Section / Service / Who It Is For").
 *
 * @param string $eyebrow Eyebrow.
 * @param string $title   Title.
 * @param array  $cards   array(title, text, link label, url or ''); no url = no link yet.
 */
function abf_accounting_audience_cards(string $eyebrow, string $title, array $cards): string
{
    $list = '';
    foreach ($cards as [$card_title, $text, $label, $url]) {
        $list .= abf_accounting_block_group(
            'abf-audience-card',
            abf_accounting_block_h(3, 'abf-audience-card__title', $card_title)
            . abf_accounting_block_p('abf-audience-card__text', $text)
            . ($url ? abf_accounting_block_p('abf-audience-card__link', '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>') : '')
        );
    }

    return abf_accounting_block_section(
        'abf-audience',
        abf_accounting_heading($eyebrow, $title, '', 'abf-heading--caps')
        . abf_accounting_block_group('abf-audience__cards', $list)
    );
}

/**
 * Icon cards on a 3-column grid (First-Year "Why start early", service "benefits").
 *
 * @param array $cards array(icon file without .svg, title, text).
 */
function abf_accounting_icon_cards(array $cards): string
{
    $list = '';
    foreach ($cards as [$icon, $title, $text]) {
        $list .= abf_accounting_block_group(
            'abf-icon-card',
            abf_accounting_block_group('abf-icon-card__tile', abf_accounting_block_image('abf-icon-card__icon', get_theme_file_uri('assets/icons/' . $icon . '.svg')))
            . abf_accounting_block_h(3, 'abf-icon-card__title', $title)
            . abf_accounting_block_p('abf-icon-card__text', $text)
        );
    }

    // Four cards sit two per row (Figma Confirmation Statement and Dormant pages).
    return abf_accounting_block_group('abf-icon-cards' . (4 === count($cards) ? ' abf-icon-cards--two' : ''), $list);
}

/**
 * Full-screen status page (Figma "404" and "Thank You"): office backdrop under a teal-to-green
 * overlay, glass circles, kicker, title, text, Back to homepage + one more button.
 *
 * @param string $kicker  Kicker.
 * @param string $title   Title (H1).
 * @param string $text    Body.
 * @param array  $second  array(label, url) of the outline button.
 * @param bool   $is_404  Draw the large "404" mark.
 */
function abf_accounting_status_page(string $kicker, string $title, string $text, array $second, bool $is_404 = false): string
{
    $inner = abf_accounting_block_group(
        'abf-container abf-status__content',
        abf_accounting_block_p('abf-status__kicker', $kicker)
        . abf_accounting_block_h(1, 'abf-status__title', $title)
        . abf_accounting_block_p('abf-status__text', $text)
        . abf_accounting_block_buttons(
            'abf-status__actions',
            array(
                array(__('Back to homepage', 'abf-accounting'), home_url('/'), 'abf-button--lg'),
                array($second[0], $second[1], 'abf-button--lg abf-button--ghost'),
            )
        )
    );

    return abf_accounting_block_cover('abf-status' . ($is_404 ? ' abf-status--404' : ''), get_theme_file_uri('assets/img/status-office-backdrop.webp'), $inner, 988);
}

/**
 * The /faq/ page topics (Figma "FAQ" and its developer note "FAQ page + new pages — all questions
 * and answers"). Used by patterns/faq-page.php and the FAQPage structured data.
 */
function abf_accounting_faq_topics(): array
{
    return array(
        array(
            'anchor'  => 'getting-started',
            'eyebrow' => __('Getting started', 'abf-accounting'),
            'title'   => __('Getting started with ABF.', 'abf-accounting'),
            'intro'   => __('How switching, onboarding and your accountant work.', 'abf-accounting'),
            'items'   => array(
                array(__('Can I switch to ABF from my current accountant?', 'abf-accounting'), __('Yes. We explain what we need from you and your previous accountant, confirm the scope and price, and handle the handover with you so nothing gets missed.', 'abf-accounting')),
                array(__('Will I have a dedicated accountant?', 'abf-accounting'), __('Yes. Every client has one dedicated accountant, with support by phone and email.', 'abf-accounting')),
                array(__('What do I need to send you to get started?', 'abf-accounting'), __('Your business details, your records for the period (such as bank statements, invoices and receipts) and any letters from HMRC or Companies House. Your accountant will send you a simple checklist.', 'abf-accounting')),
                array(__('Do you work with businesses outside Luton?', 'abf-accounting'), __('Yes. We work with businesses across the UK. Everything can be done online, by phone and by email.', 'abf-accounting')),
            ),
        ),
        array(
            'anchor'  => 'pricing-payments',
            'eyebrow' => __('Pricing & payments', 'abf-accounting'),
            'title'   => __('Prices and paying online.', 'abf-accounting'),
            'intro'   => __('Fixed prices, shown up front.', 'abf-accounting'),
            'items'   => array(
                array(__('How much do your services cost?', 'abf-accounting'), __('Company accounts and tax return £299, LLP and CIC accounts £299, Self Assessment £150, dormant company accounts £80 and Confirmation Statement £75. VAT returns start from £50 and payroll from £30 a month. Bookkeeping and tax planning are quoted.', 'abf-accounting')),
                array(__('Are there any extra charges on top of these prices?', 'abf-accounting'), __('No. The prices shown are what you pay. Additional fees may only apply for complex returns, such as foreign income or capital gains.', 'abf-accounting')),
                array(__('How do I pay?', 'abf-accounting'), __('Online, by bank payment through GoCardless. You approve the payment in your banking app. No card needed.', 'abf-accounting')),
                array(__('What happens after I click Buy now?', 'abf-accounting'), __('You find your company, confirm your details with a code sent by email, check your order and pay by bank. Your accountant then contacts you to get started.', 'abf-accounting')),
            ),
        ),
        array(
            'anchor'  => 'limited-companies',
            'eyebrow' => __('Limited companies', 'abf-accounting'),
            'title'   => __('Limited company questions.', 'abf-accounting'),
            'intro'   => __('Accounts, Corporation Tax and Companies House filings.', 'abf-accounting'),
            'items'   => array(
                array(__('What is included in the £299 service?', 'abf-accounting'), __('Preparation of your year-end accounts and CT600 Corporation Tax return, with filing at Companies House and HMRC, typically within 2–5 business days of receiving all your information.', 'abf-accounting')),
                array(__('What is a dormant company?', 'abf-accounting'), __('A company is usually dormant when it has not traded and has had no significant transactions in its financial year. Dormant companies still need to file annual accounts with Companies House.', 'abf-accounting')),
                array(__('How much does Confirmation Statement filing cost?', 'abf-accounting'), __('Confirmation Statement filing is £75, typically completed within 1–3 business days of receiving all the information we need.', 'abf-accounting')),
                array(__('Who qualifies for the first-year offer?', 'abf-accounting'), __('UK limited companies that subscribe to ABF Accounting’s annual accounts and Corporation Tax return service within 30 days of their incorporation date.', 'abf-accounting')),
            ),
        ),
        array(
            'anchor'  => 'self-assessment',
            'eyebrow' => __('Self Assessment', 'abf-accounting'),
            'title'   => __('Self Assessment questions.', 'abf-accounting'),
            'intro'   => __('Tax returns for the self-employed, landlords and directors.', 'abf-accounting'),
            'items'   => array(
                array(__('Do I need to file a Self Assessment tax return?', 'abf-accounting'), __('You usually need to file if you have income that is not taxed through PAYE, such as self-employment, rental or partnership income. If you are not sure, we will check your situation with you before you sign up.', 'abf-accounting')),
                array(__('How much does a Self Assessment return cost?', 'abf-accounting'), __('A Self Assessment tax return is £150. Additional fees may apply for complex returns, foreign income or capital gains.', 'abf-accounting')),
                array(__('When is the Self Assessment deadline?', 'abf-accounting'), __('The online deadline is 31 January after the end of the tax year on 5 April. Any tax you owe is usually due on the same day.', 'abf-accounting')),
                array(__('Can you help with rental income?', 'abf-accounting'), __('Yes. We organise your rental figures, prepare your rental accounts and complete the property pages of your Self Assessment return.', 'abf-accounting')),
            ),
        ),
        array(
            'anchor'  => 'vat',
            'eyebrow' => __('VAT', 'abf-accounting'),
            'title'   => __('VAT questions.', 'abf-accounting'),
            'intro'   => __('Registration, returns and Making Tax Digital.', 'abf-accounting'),
            'items'   => array(
                array(__('How much does a VAT return cost?', 'abf-accounting'), __('VAT returns start from £50 per return. Your accountant will confirm the price based on your VAT scheme and transaction volume.', 'abf-accounting')),
                array(__('Do I need to register for VAT?', 'abf-accounting'), __('You must register if your VAT taxable turnover goes over the VAT threshold. You can also register voluntarily. Your accountant can check this for you.', 'abf-accounting')),
                array(__('What is Making Tax Digital?', 'abf-accounting'), __('Making Tax Digital means VAT records are kept digitally and returns are sent to HMRC using compatible software. We file your VAT returns this way.', 'abf-accounting')),
            ),
        ),
        array(
            'anchor'  => 'payroll',
            'eyebrow' => __('Payroll', 'abf-accounting'),
            'title'   => __('Payroll questions.', 'abf-accounting'),
            'intro'   => __('Paying your team and workplace pensions.', 'abf-accounting'),
            'items'   => array(
                array(__('How much does payroll cost?', 'abf-accounting'), __('Payroll starts from £30/month. The final price depends on the number of employees and how often you run payroll.', 'abf-accounting')),
                array(__('Do you handle workplace pensions?', 'abf-accounting'), __('Yes. We can handle auto-enrolment and pension contributions alongside your payroll.', 'abf-accounting')),
                array(__('Can you run payroll for just one director?', 'abf-accounting'), __('Yes. We can run a simple payroll for a single director.', 'abf-accounting')),
            ),
        ),
        array(
            'anchor'  => 'charities-cics-partnerships',
            'eyebrow' => __('Charities, CICs & partnerships', 'abf-accounting'),
            'title'   => __('Charities, CICs and partnerships.', 'abf-accounting'),
            'intro'   => __('Accounts and returns for not-for-profits and partnerships.', 'abf-accounting'),
            'items'   => array(
                array(__('How much do charity and CIC accounts cost?', 'abf-accounting'), __('From £299, priced up front. Larger or more complex organisations may need a quote.', 'abf-accounting')),
                array(__('Do you prepare the CIC34 community interest report?', 'abf-accounting'), __('Yes. For CICs, we prepare the CIC34 report alongside your year-end accounts.', 'abf-accounting')),
                array(__('How much does a partnership tax return cost?', 'abf-accounting'), __('From £299, priced up front. Each partner’s Self Assessment can be added.', 'abf-accounting')),
                array(__('What is a Partnership Tax Return (SA800)?', 'abf-accounting'), __('It reports the partnership’s income and each partner’s share of the profit to HMRC. Each partner then includes their share on their own Self Assessment return.', 'abf-accounting')),
            ),
        ),
    );
}

/**
 * FAQPage structured data on /faq/ (search engines can show the answers).
 */
function abf_accounting_faq_schema(): void
{
    if (! is_page('faq')) {
        return;
    }
    $questions = array();
    foreach (abf_accounting_faq_topics() as $topic) {
        foreach ($topic['items'] as [$q, $a]) {
            $questions[] = array(
                '@type'          => 'Question',
                'name'           => $q,
                'acceptedAnswer' => array('@type' => 'Answer', 'text' => $a),
            );
        }
    }
    echo '<script type="application/ld+json">' . wp_json_encode(array('@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $questions), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}
add_action('wp_head', 'abf_accounting_faq_schema');
