<?php
/**
 * Title: Pricing — Packages
 * Slug: abf-accounting/pricing-packages
 * Categories: abf-sections
 * Description: "Transparent accountant fees" intro and the six fixed-price packages with their details.
 *
 * @package ABF_Accounting
 */

$abf_accounting_q  = array(__('Who is this for?', 'abf-accounting'), __('What’s included?', 'abf-accounting'), __('How long does it take?', 'abf-accounting'));
$abf_accounting_25 = __('2–5 business days', 'abf-accounting');
$abf_accounting_13 = __('1–3 business days', 'abf-accounting');
$abf_accounting_go = __('Get started', 'abf-accounting');

$abf_accounting_packages = array(
    array('company-accounts', 'light-corporation-tax', __('Company Accounts &amp; Corporation Tax Return', 'abf-accounting'), __('Year-end accounts and CT600 filing for UK limited companies.', 'abf-accounting'), '£299', $abf_accounting_25, $abf_accounting_go, true, array(
        __('Micro and small limited companies requiring annual accounts and Corporation Tax filing.', 'abf-accounting'),
        __('Preparation of year-end accounts and CT600 Corporation Tax return, with filing at Companies House and HMRC.', 'abf-accounting'),
        __('Typically 2–5 business days after receiving all required information, subject to complexity and review.', 'abf-accounting'),
    )),
    array('llp', 'light-payroll-cis', __('LLP Accounts &amp; Tax Return', 'abf-accounting'), __('Annual accounts and partnership tax filing for Limited Liability Partnerships.', 'abf-accounting'), '£299', $abf_accounting_25, $abf_accounting_go, false, array(
        __('Small LLPs requiring annual accounts and partnership tax compliance.', 'abf-accounting'),
        __('Preparation of LLP accounts and the relevant SA800 partnership tax return.', 'abf-accounting'),
        __('Timescale confirmed after reviewing your records and requirements.', 'abf-accounting'),
    )),
    array('cic', 'light-bookkeeping-records', __('Community Interest Company (CIC) Accounts', 'abf-accounting'), __('Year-end accounts and Corporation Tax compliance for CICs.', 'abf-accounting'), '£299', $abf_accounting_25, $abf_accounting_go, false, array(
        __('CICs needing annual accounts and tax compliance support.', 'abf-accounting'),
        __('Preparation of annual accounts and CT600 filing, where required, plus support with CIC reporting obligations.', 'abf-accounting'),
        __('Timescale confirmed once your records and reporting requirements have been reviewed.', 'abf-accounting'),
    )),
    array('self-assessment', 'light-self-assessment', __('Self Assessment Tax Return', 'abf-accounting'), __('Personal tax return preparation and filing with HMRC.', 'abf-accounting'), '£150*', $abf_accounting_25, $abf_accounting_go, false, array(
        __('Self-employed individuals, landlords, company directors and others required to file a personal tax return.', 'abf-accounting'),
        __('Preparation and submission of your Self Assessment tax return (SA100).', 'abf-accounting'),
        __('Typically 2–5 business days after receiving all required information, subject to complexity and review.', 'abf-accounting'),
    )),
    array('dormant', 'light-annual-accounts', __('Dormant Company Accounts', 'abf-accounting'), __('Dormant accounts preparation and filing with Companies House.', 'abf-accounting'), '£80', $abf_accounting_13, $abf_accounting_go, false, array(
        __('Companies that qualify to file dormant accounts.', 'abf-accounting'),
        __('Preparation and electronic submission of dormant company accounts to Companies House.', 'abf-accounting'),
        __('Timescale confirmed after receiving the necessary company details and information.', 'abf-accounting'),
    )),
    array('confirmation-statement', 'light-confirmation-statement', __('Confirmation Statement Filing', 'abf-accounting'), __('Keep your company information up to date with Companies House.', 'abf-accounting'), '£75', $abf_accounting_13, __('Get a quote', 'abf-accounting'), false, array(
        __('UK limited companies that need to submit their annual Confirmation Statement (CS01), whether or not their company details have changed.', 'abf-accounting'),
        __('Review of your company information, guidance on any necessary updates, and preparation and submission of your Confirmation Statement.', 'abf-accounting'),
        __('Typically 1–3 business days after receiving all required information, subject to complexity and review.', 'abf-accounting'),
    )),
);

$abf_accounting_cards = '';
foreach ($abf_accounting_packages as [$abf_accounting_key, $abf_accounting_icon, $abf_accounting_title, $abf_accounting_desc, $abf_accounting_price, $abf_accounting_time, $abf_accounting_label, $abf_accounting_primary, $abf_accounting_details]) {
    $abf_accounting_list = '';
    foreach ($abf_accounting_q as $abf_accounting_i => $abf_accounting_question) {
        $abf_accounting_list .= abf_accounting_block_group(
            'abf-package__detail',
            abf_accounting_block_p('abf-package__q', $abf_accounting_question)
            . abf_accounting_block_p('abf-package__a', $abf_accounting_details[$abf_accounting_i])
        );
    }
    $abf_accounting_url    = 'confirmation-statement' === $abf_accounting_key ? home_url('/contact/') : abf_accounting_buy_url($abf_accounting_key);
    $abf_accounting_cards .= abf_accounting_block_group(
        'abf-package' . ($abf_accounting_primary ? ' is-featured' : ''),
        abf_accounting_block_group(
            'abf-package__top',
            abf_accounting_block_image('abf-package__icon', get_theme_file_uri('assets/icons/' . $abf_accounting_icon . '.svg'))
            . abf_accounting_block_h(2, 'abf-package__title', $abf_accounting_title)
            . abf_accounting_block_p('abf-package__desc', $abf_accounting_desc)
            . abf_accounting_block_p('abf-package__price', esc_html($abf_accounting_price))
            . abf_accounting_block_p('abf-package__time', $abf_accounting_time)
            . abf_accounting_block_buttons('abf-package__actions', array(array($abf_accounting_label, $abf_accounting_url, $abf_accounting_primary ? 'abf-button--block' : 'abf-button--block abf-button--quiet')))
        )
        . abf_accounting_block_group('abf-package__details', $abf_accounting_list)
    );
}

$abf_accounting_inner = abf_accounting_block_group(
    'abf-pricing__intro',
    abf_accounting_block_p('abf-pill', __('Clear, fixed prices', 'abf-accounting'))
    . abf_accounting_block_h(1, 'abf-page-title', __('Transparent accountant fees <br>for <span class="abf-text-green">small businesses</span>.', 'abf-accounting'))
    . abf_accounting_block_p('abf-page-lead', __('Clear, fixed prices for the filings your business needs, each handled by a dedicated accountant.', 'abf-accounting'))
)
    . abf_accounting_block_group('abf-packages', $abf_accounting_cards)
    . abf_accounting_block_p('abf-pricing__note', __('*Additional fees may apply for complex returns, foreign income, capital gains and other specialist requirements.', 'abf-accounting'));

echo abf_accounting_block_section('abf-pricing', $abf_accounting_inner); // phpcs:ignore WordPress.Security.EscapeOutput
