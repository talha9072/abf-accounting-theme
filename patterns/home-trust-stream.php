<?php
/**
 * Title: Home — Trustpilot proof (review streams)
 * Slug: abf-accounting/home-trust-stream
 * Categories: abf-sections
 * Description: "Trusted by UK businesses" with the rating badge, and two slowly scrolling columns of review cards.
 *
 * @package ABF_Accounting
 */

$abf_accounting_card = static fn (string $name, string $date, string $text): string => abf_accounting_block_group(
    'abf-stream-card',
    abf_accounting_block_group(
        'abf-stream-card__head',
        abf_accounting_block_p('abf-stream-card__initial', esc_html(mb_substr($name, 0, 1)))
        . abf_accounting_block_group(
            'abf-stream-card__who',
            abf_accounting_block_p('abf-stream-card__name', esc_html($name))
            . abf_accounting_block_p('abf-stream-card__date', esc_html($date))
        )
    )
    . abf_accounting_block_p('abf-stars abf-stars--md', '<span class="screen-reader-text">' . esc_html__('Rated 5 out of 5', 'abf-accounting') . '</span>')
    . abf_accounting_block_p('abf-stream-card__text', $text)
    . abf_accounting_block_p('abf-stream-card__more', '<a href="https://uk.trustpilot.com/review/abfaccounting.com" target="_blank" rel="noreferrer noopener">' . esc_html__('Read more', 'abf-accounting') . '</a>')
);

$abf_accounting_left = array(
    array('Stella Harrison', '16 Jul 2026', __('My experience with ABF Accounting has been excellent. They are efficient, responsive, and always easy to reach.', 'abf-accounting')),
    array('Adefunke Ifaturoti', '9 Jul 2026', __('Swift response with a touch of professionalism.', 'abf-accounting')),
    array('Emeka Asuzu', '30 Apr 2026', __('Had a wonderful experience with them. Patience with customers.', 'abf-accounting')),
);
$abf_accounting_right = array(
    array('Fola Ebiseni', '12 Jul 2026', __('Great service and very professional. They responded quickly, explained everything clearly, and answered all my questions.', 'abf-accounting')),
    array('Anthony', '18 May 2026', __('Well detailed instructions and fast service', 'abf-accounting')),
    array('Valentine Aigbokhan', '17 Apr 2026', __('Fast and efficient', 'abf-accounting')),
);

$abf_accounting_column = static function (array $items, string $mod) use ($abf_accounting_card): string {
    $cards = '';
    // Each column is drawn twice so the upward loop is seamless.
    foreach (array_merge($items, $items) as [$name, $date, $text]) {
        $cards .= $abf_accounting_card($name, $date, $text);
    }

    return abf_accounting_block_group('abf-stream__col abf-stream__col--' . $mod, abf_accounting_block_group('abf-stream__track', $cards));
};

$abf_accounting_inner = abf_accounting_block_group(
    'abf-stream__intro',
    abf_accounting_block_h(2, 'abf-stream__title', __('Trusted by UK <br>businesses.', 'abf-accounting'))
    . abf_accounting_block_p('abf-stream__badge', '<strong>' . esc_html__('Excellent', 'abf-accounting') . '</strong> ' . esc_html__('4.7 out of 5', 'abf-accounting') . ' <span class="abf-stream__badge-star" aria-hidden="true">★</span> <span class="abf-stream__badge-brand">Trustpilot</span>')
    . abf_accounting_block_p('abf-stream__count', __('27 reviews', 'abf-accounting'))
)
    . abf_accounting_block_group(
        'abf-stream__cols',
        $abf_accounting_column($abf_accounting_left, 'left') . $abf_accounting_column($abf_accounting_right, 'right')
    );

echo abf_accounting_block_section('abf-stream', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
