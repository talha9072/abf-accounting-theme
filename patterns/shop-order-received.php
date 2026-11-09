<?php
/**
 * Title: Shop — Order received
 * Slug: abf-accounting/shop-order-received
 * Categories: abf-sections
 * Inserter: no
 * Description: Full-screen order confirmation with reference, package and amount paid (dynamic).
 *
 * @package ABF_Accounting
 */

echo function_exists('abf_accounting_order_received') ? abf_accounting_order_received() : ''; // phpcs:ignore WordPress.Security.EscapeOutput
