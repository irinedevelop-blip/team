<?php
/**
 * Header.
 *
 * @package CSSCable
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="screen-reader-text" href="#primary">
	<?php esc_html_e( 'Перейти к содержимому', 'csscable' ); ?>
</a>

<div class="cc-mobile-overlay" data-sidebar-close></div>

<div class="cc-shell">
	<aside class="cc-sidebar" id="cc-sidebar">
		<div class="cc-sidebar__header">
			<a class="cc-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<span class="cc-brand__mark">ЦСС</span>
					<span class="cc-brand__name">КАБЕЛЬ</span>
				<?php endif; ?>
			</a>

			<button
				class="cc-sidebar__close"
				type="button"
				data-sidebar-close
				aria-label="<?php esc_attr_e( 'Закрыть меню', 'csscable' ); ?>"
			>
				×
			</button>
		</div>

		<nav class="cc-navigation" aria-label="<?php esc_attr_e( 'Навигация', 'csscable' ); ?>">
			<a
				class="cc-navigation__item <?php echo csscable_is_active( 'home' ) ? 'is-active' : ''; ?>"
				href="<?php echo esc_url( home_url( '/' ) ); ?>"
			>
				<?php csscable_icon( 'home' ); ?>
				<span><?php esc_html_e( 'Обзор', 'csscable' ); ?></span>
			</a>

			<a
				class="cc-navigation__item <?php echo csscable_is_active( 'catalog' ) ? 'is-active' : ''; ?>"
				href="<?php echo esc_url( csscable_shop_url() ); ?>"
			>
				<?php csscable_icon( 'catalog' ); ?>
				<span><?php esc_html_e( 'Каталог', 'csscable' ); ?></span>
			</a>

			<a
				class="cc-navigation__item <?php echo csscable_is_active( 'cart' ) ? 'is-active' : ''; ?>"
				href="<?php echo esc_url( csscable_cart_url() ); ?>"
			>
				<?php csscable_icon( 'cart' ); ?>
				<span><?php esc_html_e( 'Корзина', 'csscable' ); ?></span>

				<span class="cc-navigation__badge cc-cart-count">
					<?php echo esc_html( csscable_cart_count() ); ?>
				</span>
			</a>

			<a
				class="cc-navigation__item"
				href="<?php echo esc_url( csscable_account_url() . 'orders/' ); ?>"
			>
				<?php csscable_icon( 'orders' ); ?>
				<span><?php esc_html_e( 'Мои заказы', 'csscable' ); ?></span>
			</a>

			<a
				class="cc-navigation__item"
				href="<?php echo esc_url( csscable_account_url() . 'documents/' ); ?>"

			>
				<?php csscable_icon( 'documents' ); ?>
				<span><?php esc_html_e( 'Документы', 'csscable' ); ?></span>
			</a>

			<a
				class="cc-navigation__item <?php echo csscable_is_active( 'account' ) ? 'is-active' : ''; ?>"
				href="<?php echo esc_url( csscable_account_url() ); ?>"
			>
				<?php csscable_icon( 'company' ); ?>
				<span><?php esc_html_e( 'Моя компания', 'csscable' ); ?></span>
			</a>
		</nav>

		<div class="cc-sidebar__bottom">
			<?php if ( is_user_logged_in() ) : ?>
				<?php $current_user = wp_get_current_user(); ?>

				<div class="cc-user-card">
					<div class="cc-user-card__avatar">
						<?php
						echo esc_html(
							mb_strtoupper(
								mb_substr( $current_user->display_name, 0, 1 )
							)
						);
						?>
					</div>

					<div class="cc-user-card__content">
						<strong><?php echo esc_html( $current_user->display_name ); ?></strong>
						<span><?php echo esc_html( $current_user->user_email ); ?></span>
					</div>
				</div>
			<?php else : ?>
				<a class="cc-login-link" href="<?php echo esc_url( csscable_account_url() ); ?>">
					<?php csscable_icon( 'user' ); ?>
					<?php esc_html_e( 'Войти в кабинет', 'csscable' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</aside>

	<div class="cc-workspace">
		<?php
$cc_section = isset( $_GET['cc_section'] )
	? sanitize_key( wp_unslash( $_GET['cc_section'] ) )
	: 'dashboard';

$cc_cabinet_sections = array(
	'dashboard',
	'cabinet',
	'bonuses',
);

$cc_show_cabinet_navigation =
	is_user_logged_in()
	&& is_front_page()
	&& in_array(
		$cc_section,
		$cc_cabinet_sections,
		true
	);

$cc_home_section_url = home_url( '/' );

$cc_orders_section_url = add_query_arg(
	'cc_section',
	'cabinet',
	home_url( '/' )
);

$cc_bonuses_section_url = add_query_arg(
	'cc_section',
	'bonuses',
	home_url( '/' )
);
?>

	<header class="cc-topbar <?php echo $cc_show_cabinet_navigation ? 'cc-topbar--cabinet' : ''; ?>">

			<button
				class="cc-mobile-menu"
				type="button"
				data-sidebar-open
				aria-controls="cc-sidebar"
				aria-label="<?php esc_attr_e( 'Открыть меню', 'csscable' ); ?>"
			>
				<?php csscable_icon( 'menu' ); ?>
			</button>

<?php if ( $cc_show_cabinet_navigation ) : ?>

	<nav
		class="cc-topbar-sections"
		aria-label="<?php esc_attr_e( 'Разделы кабинета', 'csscable' ); ?>"
	>
		<a
			class="cc-topbar-sections__item <?php echo 'dashboard' === $cc_section ? 'is-active' : ''; ?>"
			href="<?php echo esc_url( $cc_home_section_url ); ?>"
		>
			<svg
				viewBox="0 0 24 24"
				aria-hidden="true"
				fill="none"
				stroke="currentColor"
				stroke-width="1.8"
				stroke-linecap="round"
				stroke-linejoin="round"
			>
				<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>
			</svg>

			<span>Главная</span>
		</a>

		<a
			class="cc-topbar-sections__item <?php echo 'cabinet' === $cc_section ? 'is-active' : ''; ?>"
			href="<?php echo esc_url( $cc_orders_section_url ); ?>"
		>
			<svg
				viewBox="0 0 24 24"
				aria-hidden="true"
				fill="none"
				stroke="currentColor"
				stroke-width="1.8"
				stroke-linecap="round"
				stroke-linejoin="round"
			>
				<path d="M6 3h12v18H6z"/>
				<path d="M9 8h6M9 12h6M9 16h4"/>
			</svg>

			<span>Мои заказы</span>
		</a>

		<a
			class="cc-topbar-sections__item <?php echo 'bonuses' === $cc_section ? 'is-active' : ''; ?>"
			href="<?php echo esc_url( $cc_bonuses_section_url ); ?>"
		>
			<svg
				viewBox="0 0 24 24"
				aria-hidden="true"
				fill="none"
				stroke="currentColor"
				stroke-width="1.8"
				stroke-linecap="round"
				stroke-linejoin="round"
			>
				<path d="M4 8h16v12H4z"/>
				<path d="M12 8v12M4 12h16"/>
				<path d="M12 8H8.5a2.5 2.5 0 1 1 2.5-2.5V8z"/>
				<path d="M12 8h3.5A2.5 2.5 0 1 0 13 5.5V8z"/>
			</svg>

			<span>Бонусы и вывод</span>
		</a>
	</nav>

<?php else : ?>

	<div class="cc-topbar__title">
		<?php
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			esc_html_e( 'Каталог продукции', 'csscable' );
		} elseif ( function_exists( 'is_cart' ) && is_cart() ) {
			esc_html_e( 'Корзина', 'csscable' );
		} elseif ( function_exists( 'is_checkout' ) && is_checkout() ) {
			esc_html_e( 'Оформление заказа', 'csscable' );
		} elseif ( is_singular() ) {
			echo esc_html( get_the_title() );
		} else {
			bloginfo( 'name' );
		}
		?>
	</div>

<?php endif; ?>


			<div class="cc-topbar__spacer"></div>

			<?php
$contract_number = function_exists( 'csscable_b2b_contract' )
	? csscable_b2b_contract()
	: '';
?>

<?php if ( is_user_logged_in() && $contract_number ) : ?>
	<div class="cc-topbar__contract">
		<span><?php esc_html_e( 'Договор', 'csscable' ); ?></span>

		<strong>
			<?php echo esc_html( $contract_number ); ?>
		</strong>
	</div>
<?php endif; ?>


			<a class="cc-topbar__cart" href="<?php echo esc_url( csscable_cart_url() ); ?>">
				<?php csscable_icon( 'cart' ); ?>

				<span class="cc-cart-count">
					<?php echo esc_html( csscable_cart_count() ); ?>
				</span>
			</a>
		</header>

		<main id="primary" class="cc-main">
