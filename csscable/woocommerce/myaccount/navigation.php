<?php
/**
 * Навигация личного кабинета.
 *
 * @package CSSCable
 */

defined( 'ABSPATH' ) || exit;

$items = wc_get_account_menu_items();

/**
 * Иконки пунктов меню.
 *
 * @param string $endpoint Endpoint WooCommerce.
 * @return string
 */
function csscable_account_menu_icon( string $endpoint ): string {
	$icons = array(
		'dashboard' => '
			<svg viewBox="0 0 24 24" aria-hidden="true">
				<rect x="3" y="3" width="7" height="7" rx="1"></rect>
				<rect x="14" y="3" width="7" height="7" rx="1"></rect>
				<rect x="3" y="14" width="7" height="7" rx="1"></rect>
				<rect x="14" y="14" width="7" height="7" rx="1"></rect>
			</svg>
		',
		'orders' => '
			<svg viewBox="0 0 24 24" aria-hidden="true">
				<path d="M6 3h12v18H6z"></path>
				<path d="M9 8h6"></path>
				<path d="M9 12h6"></path>
				<path d="M9 16h4"></path>
			</svg>
		',
		'edit-address' => '
			<svg viewBox="0 0 24 24" aria-hidden="true">
				<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"></path>
				<circle cx="12" cy="10" r="2.5"></circle>
			</svg>
		',
		'payment-methods' => '
			<svg viewBox="0 0 24 24" aria-hidden="true">
				<rect x="3" y="5" width="18" height="14" rx="2"></rect>
				<path d="M3 10h18"></path>
				<path d="M7 15h3"></path>
			</svg>
		',
		'edit-account' => '
			<svg viewBox="0 0 24 24" aria-hidden="true">
				<circle cx="12" cy="8" r="4"></circle>
				<path d="M4 21a8 8 0 0 1 16 0"></path>
			</svg>
		',
		'customer-logout' => '
			<svg viewBox="0 0 24 24" aria-hidden="true">
				<path d="M10 4H5v16h5"></path>
				<path d="M14 8l4 4-4 4"></path>
				<path d="M18 12H9"></path>
			</svg>
		',
	);

	return $icons[ $endpoint ] ?? $icons['dashboard'];
}
?>

<nav
	class="woocommerce-MyAccount-navigation cc-account-nav"
	aria-label="Разделы личного кабинета"
>
	<header class="cc-account-nav__header">
		<span>Личный кабинет</span>
		<strong>Моя компания</strong>
	</header>

	<ul class="cc-account-nav__list">
		<?php foreach ( $items as $endpoint => $label ) : ?>
			<?php
			$is_active = wc_is_current_account_menu_item( $endpoint );
			$is_logout = 'customer-logout' === $endpoint;
			?>

			<li
				class="
					cc-account-nav__item
					cc-account-nav__item--<?php echo esc_attr( $endpoint ); ?>
					<?php echo $is_active ? 'is-active' : ''; ?>
					<?php echo $is_logout ? 'is-logout' : ''; ?>
				"
			>
				<a
					href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
					<?php echo $is_active ? 'aria-current="page"' : ''; ?>
				>
					<span class="cc-account-nav__icon">
						<?php
						echo wp_kses(
							csscable_account_menu_icon( $endpoint ),
							array(
								'svg' => array(
									'viewBox'    => true,
									'aria-hidden' => true,
								),
								'rect' => array(
									'x'      => true,
									'y'      => true,
									'width'  => true,
									'height' => true,
									'rx'     => true,
								),
								'path' => array(
									'd' => true,
								),
								'circle' => array(
									'cx' => true,
									'cy' => true,
									'r'  => true,
								),
							)
						);
						?>
					</span>

					<span><?php echo esc_html( $label ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
