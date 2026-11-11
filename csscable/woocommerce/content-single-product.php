<?php
/**
 * Карточка товара CSS Cable.
 *
 * @package CSSCable
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

if ( ! $product instanceof WC_Product ) {
	$product = wc_get_product( get_the_ID() );
}

if ( ! $product instanceof WC_Product ) {
	return;
}

do_action( 'woocommerce_before_single_product' );

$product_id = $product->get_id();
$unit       = function_exists( 'csscable_b2b_product_unit' )
	? csscable_b2b_product_unit( $product )
	: 'м';

$warehouses = function_exists( 'csscable_b2b_product_warehouses' )
	? csscable_b2b_product_warehouses( $product )
	: array();

$stock_quantity = $product->get_stock_quantity();
$current_price  = (float) $product->get_price();

$regular_price = (float) $product->get_regular_price( 'edit' );

$base_price = $regular_price > 0
	? $regular_price
	: $current_price;


$display_price = (float) wc_get_price_to_display(
	$product,
	array( 'price' => $current_price )
);

$display_base_price = (float) wc_get_price_to_display(
	$product,
	array( 'price' => $base_price )
);

$discount_percent = 0;

if ( $display_base_price > 0 && $display_price < $display_base_price ) {
	$discount_percent = (int) round(
		( ( $display_base_price - $display_price ) / $display_base_price ) * 100
	);
}

$min_quantity = max(
	0.001,
	(float) $product->get_meta( '_cc_min_quantity', true )
);

if ( ! $product->get_meta( '_cc_min_quantity', true ) ) {
	$settings     = (array) get_option( 'csscable_b2b_settings', array() );
	$min_quantity = isset( $settings['default_min_quantity'] )
		? max( 0.001, (float) $settings['default_min_quantity'] )
		: 1;
}

$quantity_step = max(
	0.001,
	(float) $product->get_meta( '_cc_quantity_step', true )
);

if ( ! $product->get_meta( '_cc_quantity_step', true ) ) {
	$settings      = isset( $settings )
		? $settings
		: (array) get_option( 'csscable_b2b_settings', array() );
	$quantity_step = isset( $settings['default_quantity_step'] )
		? max( 0.001, (float) $settings['default_quantity_step'] )
		: 1;
}

$settings = isset( $settings )
	? $settings
	: (array) get_option( 'csscable_b2b_settings', array() );

$cashback_enabled = ! isset( $settings['enable_cashback'] )
	|| 'yes' === $settings['enable_cashback'];

$cashback_percent = $cashback_enabled
	? max( 0, (float) ( $settings['cashback_percent'] ?? 2 ) )
	: 0;

$manufacturer = (string) $product->get_meta( '_cc_manufacturer', true );
$gost         = (string) $product->get_meta( '_cc_gost', true );
$warranty     = (string) $product->get_meta( '_cc_warranty', true );
$shipping     = (string) $product->get_meta( '_cc_shipping_time', true );
$documents    = (string) $product->get_meta( '_cc_documents', true );

if ( '' === $manufacturer ) {
	foreach ( array( 'pa_proizvoditel', 'proizvoditel', 'pa_manufacturer', 'manufacturer' ) as $attribute_name ) {
		$value = $product->get_attribute( $attribute_name );

		if ( $value ) {
			$manufacturer = $value;
			break;
		}
	}
}

if ( '' === $gost ) {
	foreach ( array( 'pa_gost', 'gost', 'pa_standart', 'standart' ) as $attribute_name ) {
		$value = $product->get_attribute( $attribute_name );

		if ( $value ) {
			$gost = $value;
			break;
		}
	}
}

$image_ids = array_filter(
	array_unique(
		array_merge(
			array( $product->get_image_id() ),
			$product->get_gallery_image_ids()
		)
	)
);

$main_image_id = $image_ids ? reset( $image_ids ) : 0;
$main_image    = $main_image_id
	? wp_get_attachment_image_url( $main_image_id, 'woocommerce_single' )
	: '';

$short_description = $product->get_short_description();
$attributes        = $product->get_attributes();
$default_warehouse = $warehouses ? $warehouses[0]['name'] : '';
?>

<div
	id="product-<?php the_ID(); ?>"
	<?php wc_product_class( 'cc-sku-product', $product ); ?>
	data-price="<?php echo esc_attr( $display_price ); ?>"
	data-base-price="<?php echo esc_attr( $display_base_price ); ?>"
	data-cashback="<?php echo esc_attr( $cashback_percent ); ?>"
	data-unit="<?php echo esc_attr( $unit ); ?>"
>
	<nav class="cc-sku-breadcrumbs" aria-label="Хлебные крошки">
		<?php
		woocommerce_breadcrumb(
			array(
				'delimiter'   => '<span>/</span>',
				'wrap_before' => '<div class="cc-sku-breadcrumbs__items">',
				'wrap_after'  => '</div>',
			)
		);
		?>

		<a class="cc-sku-back" href="<?php echo esc_url( csscable_shop_url() ); ?>">
			← К каталогу
		</a>
	</nav>

	<div class="cc-sku-layout">
		<section class="cc-sku-gallery" aria-label="Изображения товара">
			<div class="cc-sku-gallery__main">
				<?php if ( $main_image ) : ?>
					<img
						id="cc-sku-main-image"
						src="<?php echo esc_url( $main_image ); ?>"
						alt="<?php echo esc_attr( $product->get_name() ); ?>"
					>
				<?php else : ?>
					<div class="cc-sku-placeholder" aria-hidden="true">
						<svg viewBox="0 0 100 100">
							<circle cx="50" cy="50" r="31"></circle>
							<circle cx="50" cy="50" r="8"></circle>
							<path d="M50 19v23M50 58v23M19 50h23M58 50h23M28 28l16 16M56 56l16 16M72 28 56 44M44 56 28 72"></path>
						</svg>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $image_ids ) : ?>
				<div class="cc-sku-gallery__thumbs">
					<?php foreach ( $image_ids as $index => $image_id ) : ?>
						<?php
						$thumbnail = wp_get_attachment_image_url(
							$image_id,
							'woocommerce_thumbnail'
						);

						$large = wp_get_attachment_image_url(
							$image_id,
							'woocommerce_single'
						);
						?>

						<button
							type="button"
							class="cc-sku-thumb <?php echo 0 === $index ? 'is-active' : ''; ?>"
							data-product-image="<?php echo esc_url( $large ); ?>"
							aria-label="<?php echo esc_attr( sprintf( 'Изображение %d', $index + 1 ) ); ?>"
						>
							<?php
							echo wp_get_attachment_image(
								$image_id,
								'woocommerce_thumbnail'
							);
							?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="cc-sku-gallery__thumbs">
					<div class="cc-sku-thumb is-active">
						<span class="cc-sku-thumb__placeholder">◎</span>
					</div>
				</div>
			<?php endif; ?>
		</section>

		<main class="cc-sku-main">
			<header class="cc-sku-header">
				<div class="cc-sku-badges">
					<?php if ( $product->is_in_stock() ) : ?>
						<span class="cc-sku-status">
							<span></span>
							В наличии
							<?php if ( null !== $stock_quantity ) : ?>
								· <?php echo esc_html( number_format_i18n( $stock_quantity ) ); ?>
								<?php echo esc_html( $unit ); ?>
							<?php endif; ?>
						</span>
					<?php else : ?>
						<span class="cc-sku-status cc-sku-status--empty">
							Нет в наличии
						</span>
					<?php endif; ?>

					<?php if ( $gost ) : ?>
						<span class="cc-sku-meta-badge">
							<?php echo esc_html( $gost ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $manufacturer ) : ?>
						<span class="cc-sku-meta-badge">
							Изготовитель:
							<strong><?php echo esc_html( $manufacturer ); ?></strong>
						</span>
					<?php endif; ?>
				</div>

				<h1 class="cc-sku-title"><?php the_title(); ?></h1>

				<?php if ( $short_description ) : ?>
					<div class="cc-sku-description">
						<?php echo wp_kses_post( wpautop( $short_description ) ); ?>
					</div>
				<?php endif; ?>
			</header>

			<section class="cc-sku-pricing">
				<div class="cc-sku-pricing__top">
					<div>
						<span class="cc-sku-label">Ваша цена</span>

						<div class="cc-sku-current-price">
							<?php echo wp_kses_post( wc_price( $display_price ) ); ?>
							<small>/ <?php echo esc_html( $unit ); ?></small>
						</div>

						<?php if ( $discount_percent > 0 ) : ?>
							<div class="cc-sku-base-price">
								<span>−<?php echo esc_html( $discount_percent ); ?>%</span>
								Базовая:
								<del><?php echo wp_kses_post( wc_price( $display_base_price ) ); ?></del>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( $cashback_percent > 0 ) : ?>
						<div class="cc-sku-cashback">
							<span>Кэшбэк</span>
							<strong>+<?php echo esc_html( wc_format_decimal( $cashback_percent ) ); ?>%</strong>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<section class="cc-sku-tabs">
				<div class="cc-sku-tabs__nav" role="tablist">
					<button class="is-active" type="button" data-sku-tab="specifications">
						Характеристики
					</button>

					<button type="button" data-sku-tab="warehouses">
						Остатки по складам
					</button>

					<button type="button" data-sku-tab="documents">
						Документы
					</button>

					<button type="button" data-sku-tab="orders">
						История заказов
					</button>
				</div>

				<div class="cc-sku-tab-panel is-active" data-sku-panel="specifications">
					<div class="cc-sku-specifications">
						<?php if ( $product->get_sku() ) : ?>
							<div>
								<span>Артикул</span>
								<strong><?php echo esc_html( $product->get_sku() ); ?></strong>
							</div>
						<?php endif; ?>

						<?php if ( $manufacturer ) : ?>
							<div>
								<span>Изготовитель</span>
								<strong><?php echo esc_html( $manufacturer ); ?></strong>
							</div>
						<?php endif; ?>

						<?php if ( $gost ) : ?>
							<div>
								<span>Стандарт</span>
								<strong><?php echo esc_html( $gost ); ?></strong>
							</div>
						<?php endif; ?>

						<?php if ( $warranty ) : ?>
							<div>
								<span>Гарантия</span>
								<strong><?php echo esc_html( $warranty ); ?></strong>
							</div>
						<?php endif; ?>

						<?php foreach ( $attributes as $attribute ) : ?>
							<?php
							if ( ! $attribute->get_visible() ) {
								continue;
							}

							$label = wc_attribute_label( $attribute->get_name() );

							if ( $attribute->is_taxonomy() ) {
								$values = wc_get_product_terms(
									$product_id,
									$attribute->get_name(),
									array( 'fields' => 'names' )
								);
							} else {
								$values = $attribute->get_options();
							}

							if ( ! $values ) {
								continue;
							}
							?>

							<div>
								<span><?php echo esc_html( $label ); ?></span>
								<strong><?php echo esc_html( implode( ', ', $values ) ); ?></strong>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( $product->get_description() ) : ?>
						<div class="cc-sku-full-description">
							<?php echo wp_kses_post( apply_filters( 'the_content', $product->get_description() ) ); ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="cc-sku-tab-panel" data-sku-panel="warehouses" hidden>
					<?php if ( $warehouses ) : ?>
						<div class="cc-sku-warehouse-list">
							<?php foreach ( $warehouses as $warehouse ) : ?>
								<div class="cc-sku-warehouse-row">
									<span>
										<i></i>
										<?php echo esc_html( $warehouse['name'] ); ?>
									</span>

									<strong>
										<?php echo esc_html( number_format_i18n( $warehouse['quantity'] ) ); ?>
										<?php echo esc_html( $unit ); ?>
									</strong>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="cc-sku-empty">Остатки по складам пока не указаны.</p>
					<?php endif; ?>
				</div>

				<div class="cc-sku-tab-panel" data-sku-panel="documents" hidden>
					<?php if ( trim( $documents ) ) : ?>
						<div class="cc-sku-documents">
							<?php foreach ( preg_split( '/\r\n|\r|\n/', $documents ) as $document ) : ?>
								<?php
								$parts = array_map( 'trim', explode( '|', $document, 2 ) );
								$url   = count( $parts ) > 1 ? $parts[1] : $parts[0];
								$title = count( $parts ) > 1 ? $parts[0] : basename( $url );

								if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
									continue;
								}
								?>

								<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
									<span>Документ</span>
									<strong><?php echo esc_html( $title ); ?></strong>
									<em>Скачать →</em>
								</a>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="cc-sku-empty">Для этого товара документы пока не добавлены.</p>
					<?php endif; ?>
				</div>

				<div class="cc-sku-tab-panel" data-sku-panel="orders" hidden>
					<?php if ( is_user_logged_in() ) : ?>
						<p class="cc-sku-empty">
							История приобретения товара отображается в разделе
							<a href="<?php echo esc_url( csscable_account_url() . 'orders/' ); ?>">
								«Мои заказы»
							</a>.
						</p>
					<?php else : ?>
						<p class="cc-sku-empty">
							<a href="<?php echo esc_url( csscable_account_url() ); ?>">
								Войдите в личный кабинет
							</a>,
							чтобы посмотреть историю заказов.
						</p>
					<?php endif; ?>
				</div>
			</section>
		</main>

		<aside class="cc-sku-order">
			<div class="cc-sku-order__card">
				<h2>Оформить заказ</h2>

				<?php if ( $product->is_purchasable() && $product->is_in_stock() ) : ?>
					<?php if ( $product->is_type( 'simple' ) ) : ?>
						<form
							class="cart cc-sku-cart-form"
							action="<?php echo esc_url( $product->get_permalink() ); ?>"
							method="post"
							enctype="multipart/form-data"
						>
							<div class="cc-sku-field">
								<label for="cc-sku-quantity">Количество</label>

								<div class="cc-sku-quantity">
									<button type="button" data-quantity-minus aria-label="Уменьшить">
										−
									</button>

									<?php
									woocommerce_quantity_input(
										array(
											'input_id'    => 'cc-sku-quantity',
											'input_name'  => 'quantity',
											'input_value' => $min_quantity,
											'min_value'   => $min_quantity,
											'max_value'   => $product->get_max_purchase_quantity(),
											'step'        => $quantity_step,
										),
										$product,
										true
									);
									?>

									<button type="button" data-quantity-plus aria-label="Увеличить">
										+
									</button>
								</div>

								<small>
									Кратно
									<?php echo esc_html( wc_format_localized_decimal( $quantity_step ) ); ?>
									<?php echo esc_html( $unit ); ?>
								</small>
							</div>

							<?php if ( $warehouses ) : ?>
								<div class="cc-sku-field">
									<label for="cc-sku-warehouse">Склад отгрузки</label>

									<select id="cc-sku-warehouse" name="cc_warehouse">
										<?php foreach ( $warehouses as $warehouse ) : ?>
											<option value="<?php echo esc_attr( $warehouse['name'] ); ?>">
												<?php echo esc_html( $warehouse['name'] ); ?>
												—
												<?php echo esc_html( number_format_i18n( $warehouse['quantity'] ) ); ?>
												<?php echo esc_html( $unit ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>
							<?php endif; ?>

							<div class="cc-sku-calculation">
								<div>
									<span>
										<b data-sku-quantity-output>
											<?php echo esc_html( wc_format_localized_decimal( $min_quantity ) ); ?>
										</b>
										<?php echo esc_html( $unit ); ?>
										×
										<?php echo wp_kses_post( wc_price( $display_price ) ); ?>
									</span>

									<strong data-sku-total>
										<?php echo wp_kses_post( wc_price( $display_price * $min_quantity ) ); ?>
									</strong>
								</div>

								<?php if ( $display_base_price > $display_price ) : ?>
									<div class="cc-sku-saving-row">
										<span>Экономия</span>
										<strong data-sku-saving>
											<?php
											echo wp_kses_post(
												wc_price(
													( $display_base_price - $display_price ) * $min_quantity
												)
											);
											?>
										</strong>
									</div>
								<?php endif; ?>

								<?php if ( $cashback_percent > 0 ) : ?>
									<div class="cc-sku-bonus-row">
										<span>Будет начислено бонусами</span>
										<strong data-sku-bonus>
											<?php
											echo wp_kses_post(
												wc_price(
													$display_price
													* $min_quantity
													* ( $cashback_percent / 100 )
												)
											);
											?>
										</strong>
									</div>
								<?php endif; ?>
							</div>

							<input
								type="hidden"
								name="add-to-cart"
								value="<?php echo esc_attr( $product_id ); ?>"
							>

							<button
								type="submit"
								class="single_add_to_cart_button button alt cc-sku-add"
							>
								В корзину
							</button>

							<button
								type="submit"
								name="cc_buy_now"
								value="1"
								class="button cc-sku-buy-now"
							>
								Заказ в один клик
							</button>
						</form>
					<?php else : ?>
						<div class="cc-sku-standard-cart">
							<?php woocommerce_template_single_add_to_cart(); ?>
						</div>
					<?php endif; ?>
				<?php else : ?>
					<p class="cc-sku-unavailable">
						Товар временно недоступен для заказа.
					</p>
				<?php endif; ?>

				<ul class="cc-sku-order-notes">
					<li>
						<span>◷</span>
						<?php echo esc_html( $shipping ?: 'Отгрузка по согласованию' ); ?>
					</li>

					<?php if ( $warranty ) : ?>
						<li>
							<span>◇</span>
							Гарантия: <?php echo esc_html( $warranty ); ?>
						</li>
					<?php endif; ?>

					<?php if ( $documents ) : ?>
						<li>
							<span>□</span>
							Сертификаты доступны во вкладке «Документы»
						</li>
					<?php endif; ?>
				</ul>
			</div>
		</aside>
	</div>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
