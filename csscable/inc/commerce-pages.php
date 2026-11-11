<?php
/**
 * Оформление страниц корзины, checkout и успешного заказа.
 *
 * @package CSSCable
 */

defined( 'ABSPATH' ) || exit;

/**
 * Подключает стили коммерческих страниц.
 */
function csscable_commerce_pages_assets(): void {
	if (
		! function_exists( 'is_cart' ) ||
		! function_exists( 'is_checkout' )
	) {
		return;
	}

	if ( ! is_cart() && ! is_checkout() ) {
		return;
	}

	wp_enqueue_style(
		'csscable-commerce-pages',
		CSSCABLE_URI . '/assets/css/commerce-pages.css',
		array( 'csscable-app' ),
		CSSCABLE_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'csscable_commerce_pages_assets', 30 );

/**
 * Индикатор этапов оформления.
 *
 * @param int $current Текущий этап: 1, 2 или 3.
 */
function csscable_checkout_steps( int $current = 1 ): void {
	$steps = array(
		1 => 'Корзина',
		2 => 'Оформление',
		3 => 'Готово',
	);
	?>
	<nav class="cc-order-flow" aria-label="<?php esc_attr_e( 'Этапы оформления заказа', 'csscable' ); ?>">
		<ol class="cc-order-flow__list">
			<?php foreach ( $steps as $number => $label ) : ?>
				<?php
				$classes = array( 'cc-order-flow__step' );

				if ( $number < $current ) {
					$classes[] = 'is-complete';
				} elseif ( $number === $current ) {
					$classes[] = 'is-current';
				} else {
					$classes[] = 'is-upcoming';
				}
				?>

				<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
					<span class="cc-order-flow__marker" aria-hidden="true">
						<?php if ( $number < $current ) : ?>
							<svg viewBox="0 0 20 20" aria-hidden="true">
								<path d="M4.5 10.3 8.2 14l7.3-8"/>
							</svg>
						<?php else : ?>
							<?php echo esc_html( str_pad( (string) $number, 2, '0', STR_PAD_LEFT ) ); ?>
						<?php endif; ?>
					</span>

					<span class="cc-order-flow__label">
						<?php echo esc_html( $label ); ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Шапка корзины.
 */
function csscable_cart_page_header(): void {
	?>
	<header class="cc-commerce-heading">
		<div class="cc-commerce-heading__content">
			<p class="cc-commerce-heading__eyebrow">
				<?php esc_html_e( 'Оформление заказа', 'csscable' ); ?>
			</p>

			<h1><?php esc_html_e( 'Корзина', 'csscable' ); ?></h1>

			<p class="cc-commerce-heading__description">
				<?php esc_html_e( 'Проверьте товары, количество и итоговую стоимость.', 'csscable' ); ?>
			</p>
		</div>

		<?php csscable_checkout_steps( 1 ); ?>
	</header>
	<?php
}
add_action( 'woocommerce_before_cart', 'csscable_cart_page_header', 5 );

/**
 * Шапка оформления заказа.
 */
function csscable_checkout_page_header(): void {
	if (
		function_exists( 'is_order_received_page' ) &&
		is_order_received_page()
	) {
		return;
	}
	?>
	<header class="cc-commerce-heading">
		<div class="cc-commerce-heading__content">
			<p class="cc-commerce-heading__eyebrow">
				<?php esc_html_e( 'Оформление заказа', 'csscable' ); ?>
			</p>

			<h1><?php esc_html_e( 'Данные заказа', 'csscable' ); ?></h1>

			<p class="cc-commerce-heading__description">
				<?php esc_html_e( 'Укажите реквизиты компании, адрес и способ оплаты.', 'csscable' ); ?>
			</p>
		</div>

		<?php csscable_checkout_steps( 2 ); ?>
	</header>
	<?php
}
add_action(
	'woocommerce_before_checkout_form',
	'csscable_checkout_page_header',
	5
);

/**
 * Шапка успешного заказа.
 *
 * @param int $order_id ID заказа.
 */
function csscable_order_received_header( $order_id ): void {
	$order_id = absint( $order_id );

	if ( ! $order_id ) {
		return;
	}

	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return;
	}
	?>
	<section class="cc-order-complete">
		<div class="cc-order-complete__icon" aria-hidden="true">
			<svg viewBox="0 0 24 24">
				<path d="M5 12.5 9.3 17 19 7"/>
			</svg>
		</div>

		<div class="cc-order-complete__content">
			<p class="cc-order-complete__eyebrow">
				<?php esc_html_e( 'Заказ оформлен', 'csscable' ); ?>
			</p>

			<h1><?php esc_html_e( 'Спасибо за заказ', 'csscable' ); ?></h1>

			<p>
				<?php
				printf(
					/* translators: %s: order number. */
					esc_html__( 'Заказ №%s принят и передан в обработку.', 'csscable' ),
					esc_html( $order->get_order_number() )
				);
				?>
			</p>
		</div>

		<?php csscable_checkout_steps( 3 ); ?>
	</section>
	<?php
}
add_action(
	'woocommerce_before_thankyou',
	'csscable_order_received_header',
	5
);
