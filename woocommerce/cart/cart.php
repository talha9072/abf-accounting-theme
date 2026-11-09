<?php
/**
 * Cart (Figma "Shop / Cart"): packages card on the left, Cart totals on the right.
 * Packages are sold one at a time, so there is no quantity column and no Update cart button.
 *
 * Based on WooCommerce templates/cart/cart.php.
 *
 * @package ABF_Accounting
 * @version 11.2.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart'); ?>

<div class="abf-shop-layout abf-cart">
	<form class="woocommerce-cart-form abf-shop-card abf-cart__items" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
		<?php do_action('woocommerce_before_cart_table'); ?>

		<table class="shop_table cart woocommerce-cart-form__contents abf-cart__table">
			<thead>
				<tr>
					<th scope="col" class="product-name"><?php esc_html_e('Package', 'abf-accounting'); ?></th>
					<th scope="col" class="product-price"><?php esc_html_e('Price', 'woocommerce'); ?></th>
					<th scope="col" class="product-subtotal"><?php esc_html_e('Subtotal', 'woocommerce'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php do_action('woocommerce_before_cart_contents'); ?>

				<?php
				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					$_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					$product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
					$visible    = apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

					if ($_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && $visible) {
						$cart_item_name = WC()->cart->get_item_product_name($cart_item, $_product);
						$product_name   = apply_filters('woocommerce_cart_item_name', $cart_item_name, $cart_item, $cart_item_key); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
						?>
						<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment ?>">
							<td class="product-name" data-title="<?php esc_attr_e('Package', 'abf-accounting'); ?>">
								<span class="abf-cart__name"><?php echo wp_kses_post($product_name); ?></span>
								<?php
								do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
								echo wc_get_formatted_cart_item_data($cart_item, false, $cart_item_name); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

								echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WooCommerce.Commenting.CommentHooks.MissingHookComment
									'woocommerce_cart_item_remove_link',
									sprintf(
										'<a role="button" href="%s" class="remove abf-cart__remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">%s</a>',
										esc_url(wc_get_cart_remove_url($cart_item_key)),
										/* translators: %s is the product name */
										esc_attr(sprintf(__('Remove %s from cart', 'woocommerce'), wp_strip_all_tags($product_name))),
										esc_attr($product_id),
										esc_attr($_product->get_sku()),
										esc_html__('Remove', 'abf-accounting')
									),
									$cart_item_key
								);
								?>
							</td>
							<td class="product-price" data-title="<?php esc_attr_e('Price', 'woocommerce'); ?>">
								<?php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WooCommerce.Commenting.CommentHooks.MissingHookComment ?>
							</td>
							<td class="product-subtotal" data-title="<?php esc_attr_e('Subtotal', 'woocommerce'); ?>">
								<?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WooCommerce.Commenting.CommentHooks.MissingHookComment ?>
							</td>
						</tr>
						<?php
					}
				}
				?>

				<?php do_action('woocommerce_cart_contents'); ?>
				<?php do_action('woocommerce_after_cart_contents'); ?>
			</tbody>
		</table>

		<a class="abf-cart__add" href="<?php echo esc_url(home_url('/pricing/')); ?>"><?php esc_html_e('+ Add another package', 'abf-accounting'); ?></a>

		<?php do_action('woocommerce_cart_actions'); ?>
		<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>

		<?php do_action('woocommerce_after_cart_table'); ?>
	</form>

	<?php do_action('woocommerce_before_cart_collaterals'); ?>

	<div class="cart-collaterals">
		<?php do_action('woocommerce_cart_collaterals'); ?>
	</div>
</div>

<?php do_action('woocommerce_after_cart'); ?>
