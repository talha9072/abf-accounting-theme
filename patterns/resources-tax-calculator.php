<?php
/**
 * Title: Resources — Tax calculator
 * Slug: abf-accounting/resources-tax-calculator
 * Categories: abf-sections
 * Description: Free take-home pay calculator: salary, tax year, region, pension and student loan, with the results panel.
 *
 * Rates live in assets/js/tax-calculator.js (one entry per tax year).
 *
 * @package ABF_Accounting
 */

$abf_accounting_points = '';
foreach (array(
    __('Income Tax and National Insurance by band', 'abf-accounting'),
    __('England, Wales &amp; NI or Scotland', 'abf-accounting'),
    __('Updated for each tax year', 'abf-accounting'),
) as $abf_accounting_point) {
    $abf_accounting_points .= '<li>' . $abf_accounting_point . '</li>';
}

$abf_accounting_select = static function (string $id, string $label, array $options): string {
    $html = '';
    foreach ($options as $value => $text) {
        $html .= '<option value="' . esc_attr($value) . '">' . esc_html($text) . '</option>';
    }

    return '<div class="abf-calc__field"><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label><div class="abf-calc__input abf-calc__input--select"><select id="' . esc_attr($id) . '" name="' . esc_attr(str_replace('abf-calc-', '', $id)) . '">' . $html . '</select></div></div>';
};

$abf_accounting_rows = '';
foreach (array(
    'gross'   => __('Gross salary', 'abf-accounting'),
    'tax'     => __('Income Tax', 'abf-accounting'),
    'ni'      => __('National Insurance', 'abf-accounting'),
    'pension' => __('Pension', 'abf-accounting'),
    'loan'    => __('Student loan', 'abf-accounting'),
    'total'   => __('Total deductions', 'abf-accounting'),
) as $abf_accounting_key => $abf_accounting_label) {
    $abf_accounting_rows .= '<div class="abf-calc__row' . ('total' === $abf_accounting_key ? ' abf-calc__row--total' : '') . '"><dt>' . esc_html($abf_accounting_label) . '</dt><dd data-out="' . esc_attr($abf_accounting_key) . '">£ —</dd></div>';
}

$abf_accounting_calc = '<div class="abf-calc" data-abf-taxcalc>'
    . '<form class="abf-calc__inputs" novalidate>'
    . '<h3 class="abf-calc__title">' . esc_html__('Your details', 'abf-accounting') . '</h3>'
    . '<div class="abf-calc__field"><label for="abf-calc-salary">' . esc_html__('Annual salary', 'abf-accounting') . '</label><div class="abf-calc__input"><span class="abf-calc__affix" aria-hidden="true">£</span><input id="abf-calc-salary" name="salary" type="text" inputmode="decimal" autocomplete="off" placeholder="' . esc_attr__('e.g. 35,000', 'abf-accounting') . '"></div><p class="abf-calc__error" hidden>' . esc_html__('Enter your annual salary before tax.', 'abf-accounting') . '</p></div>'
    . '<div class="abf-calc__pair">'
    . $abf_accounting_select('abf-calc-year', __('Tax year', 'abf-accounting'), array('2026' => '2026/27', '2025' => '2025/26'))
    . $abf_accounting_select('abf-calc-region', __('Where you live', 'abf-accounting'), array('ruk' => __('England, Wales & NI', 'abf-accounting'), 'scot' => __('Scotland', 'abf-accounting')))
    . '</div><div class="abf-calc__pair">'
    . '<div class="abf-calc__field"><label for="abf-calc-pension">' . esc_html__('Pension contribution', 'abf-accounting') . '</label><div class="abf-calc__input"><input id="abf-calc-pension" name="pension" type="text" inputmode="decimal" autocomplete="off" placeholder="0"><span class="abf-calc__affix" aria-hidden="true">%</span></div></div>'
    . $abf_accounting_select('abf-calc-loan', __('Student loan', 'abf-accounting'), array('none' => __('None', 'abf-accounting'), 'plan1' => __('Plan 1', 'abf-accounting'), 'plan2' => __('Plan 2', 'abf-accounting'), 'plan4' => __('Plan 4 (Scotland)', 'abf-accounting'), 'plan5' => __('Plan 5', 'abf-accounting'), 'pg' => __('Postgraduate loan', 'abf-accounting')))
    . '</div>'
    . '<button type="submit" class="abf-calc__submit">' . esc_html__('Calculate take-home pay', 'abf-accounting') . '</button>'
    . '</form>'
    . '<div class="abf-calc__results" aria-live="polite">'
    . '<p class="abf-calc__eyebrow">' . esc_html__('Your take-home pay', 'abf-accounting') . '</p>'
    . '<p class="abf-calc__net" data-out="net">£ —</p>'
    . '<p class="abf-calc__periods"><span data-out="period-label">' . esc_html__('per year', 'abf-accounting') . '</span>  ·  <span data-out="month">£ —</span> ' . esc_html__('per month', 'abf-accounting') . '  ·  <span data-out="week">£ —</span> ' . esc_html__('per week', 'abf-accounting') . '</p>'
    . '<dl class="abf-calc__list">' . $abf_accounting_rows . '<div class="abf-calc__row abf-calc__row--rate"><dt>' . esc_html__('Effective tax rate', 'abf-accounting') . '</dt><dd data-out="rate">— %</dd></div></dl>'
    . '<p class="abf-calc__note">' . esc_html__('Estimate only, based on HMRC rates for the selected tax year and a standard tax code. Not financial advice.', 'abf-accounting') . '</p>'
    . '<a class="abf-calc__cta" href="' . esc_url(home_url('/contact/')) . '">' . esc_html__('Speak to our team', 'abf-accounting') . '</a>'
    . '</div></div>';

$abf_accounting_inner = abf_accounting_block_group(
    'abf-taxcalc__message',
    abf_accounting_block_p('abf-pill abf-pill--mint', __('Free tool', 'abf-accounting'))
    . abf_accounting_block_h(2, 'abf-taxcalc__title', __('Tax calculator', 'abf-accounting'))
    . abf_accounting_block_p('abf-taxcalc__text', __('Enter your salary to see your take-home pay after Income Tax, National Insurance, pension and student loan.', 'abf-accounting'))
    . abf_accounting_block_html('<ul class="abf-taxcalc__points">' . $abf_accounting_points . '</ul>')
)
    . abf_accounting_block_html($abf_accounting_calc);

echo abf_accounting_block_section('abf-taxcalc', $abf_accounting_inner, array('anchor' => 'tax-calculator')); // phpcs:ignore WordPress.Security.EscapeOutput
