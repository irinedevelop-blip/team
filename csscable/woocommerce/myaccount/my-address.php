<?php
/**
 * Адреса клиента.
 *
 * @package CSSCable
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

$address_types = apply_filters(
	'woocommerce_my_account_get_addresses',
	array(
		'billing'  => __( 'Платёжный адрес', 'woocommerce' ),
		'shipping' => __( 'Адрес доставки', 'woocommerce' ),
	),
	$customer_id
);
?>

<div class="cc-address-page">
	<header class="cc-account-page-header cc-address-page__header">
		<div>
			<span class="cc-account-page-header__eyebrow">
				Моя компания
			</span>

			<h1>Адреса</h1>

			<p>
				Эти данные будут автоматически использоваться
				при оформлении заказов.
			</p>
		</div>
	</header>

	<div class="cc-address-grid">
		<?php foreach ( $address_types as $address_type => $address_title ) : ?>
			<?php
			$address = wc_get_account_formatted_address( $address_type );

			$is_billing = 'billing' === $address_type;

			$edit_url = wc_get_endpoint_url(
				'edit-address',
				$address_type,
				wc_get_page_permalink( 'myaccount' )
			);
			?>

			<article class="cc-address-card">
				<header class="cc-address-card__header">
					<div class="cc-address-card__title">
						<span class="cc-address-card__icon" aria-hidden="true">
							<?php if ( $is_billing ) : ?>
								<svg
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									stroke-width="1.8"
									stroke-linecap="round"
									stroke-linejoin="round"
								>
									<rect
										x="3"
										y="5"
										width="18"
										height="14"
										rx="2"
									></rect>
									<path d="M3 10h18"></path>
									<path d="M7 15h3"></path>
								</svg>
							<?php else : ?>
								<svg
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									stroke-width="1.8"
									stroke-linecap="round"
									stroke-linejoin="round"
								>
									<path
										d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"
									></path>
									<circle cx="12" cy="10" r="2.5"></circle>
								</svg>
							<?php endif; ?>
						</span>

						<div>
							<span class="cc-address-card__eyebrow">
								<?php echo $is_billing ? 'Документы и оплата' : 'Получение заказа'; ?>
							</span>

							<h2><?php echo esc_html( $address_title ); ?></h2>
						</div>
					</div>

					<a
						class="cc-address-card__edit"
						href="<?php echo esc_url( $edit_url ); ?>"
					>
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="1.8"
							stroke-linecap="round"
							stroke-linejoin="round"
							aria-hidden="true"
						>
							<path d="M12 20h9"></path>
							<path
								d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z"
							></path>
						</svg>

						Изменить
					</a>
				</header>

				<div class="cc-address-card__body">
					<?php if ( $address ) : ?>
						<address>
							<?php echo wp_kses_post( $address ); ?>
						</address>
					<?php else : ?>
						<div class="cc-address-card__empty">
							<strong>Адрес пока не заполнен</strong>

							<p>
								Добавьте данные, чтобы использовать их
								при оформлении заказа.
							</p>
						</div>
					<?php endif; ?>
				</div>

				<footer class="cc-address-card__footer">
					<a href="<?php echo esc_url( $edit_url ); ?>">
						<?php echo $address ? 'Редактировать адрес' : 'Добавить адрес'; ?>
						<span aria-hidden="true">→</span>
					</a>
				</footer>
			</article>
		<?php endforeach; ?>
	</div>

	<div class="cc-address-page__notice">
		<span class="cc-address-page__notice-icon" aria-hidden="true">
			i
		</span>

		<div>
			<strong>Как используются адреса?</strong>

			<p>
				Платёжный адрес указывается в документах, а адрес доставки
				используется при оформлении и отгрузке заказа.
			</p>
		</div>
	</div>
</div>
