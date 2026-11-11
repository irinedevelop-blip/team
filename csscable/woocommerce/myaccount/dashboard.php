<?php
/**
 * Обзор личного кабинета.
 *
 * @package CSSCable
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();

$first_name = (string) $current_user->first_name;
$last_name  = (string) $current_user->last_name;
$email      = (string) $current_user->user_email;

$full_name = trim( $first_name . ' ' . $last_name );

if ( ! $full_name ) {
	$full_name = (string) $current_user->display_name;
}

$company = (string) get_user_meta(
	$current_user->ID,
	'_cc_b2b_company',
	true
);

$bin = (string) get_user_meta(
	$current_user->ID,
	'_cc_b2b_bin',
	true
);

$order_count = 0;

if ( function_exists( 'wc_get_orders' ) ) {
	$order_count = count(
		wc_get_orders(
			array(
				'customer_id' => $current_user->ID,
				'limit'       => -1,
				'return'      => 'ids',
			)
		)
	);
}
?>

<div class="cc-account-dashboard">
	<header class="cc-account-page-header">
		<div>
			<span class="cc-account-page-header__eyebrow">
				Обзор
			</span>

			<h1>
				Добро пожаловать, <?php echo esc_html( $first_name ?: $full_name ); ?>
			</h1>

			<p>
				Здесь собрана основная информация по вашему аккаунту.
			</p>
		</div>

		<a
			class="cc-account-primary-button"
			href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"
		>
			Перейти в каталог
		</a>
	</header>

	<div class="cc-account-stats">
		<article class="cc-account-stat">
			<div class="cc-account-stat__icon">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path d="M6 3h12v18H6z"></path>
					<path d="M9 8h6"></path>
					<path d="M9 12h6"></path>
					<path d="M9 16h4"></path>
				</svg>
			</div>

			<div>
				<span>Всего заказов</span>
				<strong><?php echo esc_html( (string) $order_count ); ?></strong>
			</div>
		</article>

		<article class="cc-account-stat">
			<div class="cc-account-stat__icon">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<circle cx="12" cy="8" r="4"></circle>
					<path d="M4 21a8 8 0 0 1 16 0"></path>
				</svg>
			</div>

			<div>
				<span>Статус аккаунта</span>
				<strong class="cc-account-stat__success">Активен</strong>
			</div>
		</article>

		<article class="cc-account-stat">
			<div class="cc-account-stat__icon">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path d="M4 5h16v14H4z"></path>
					<path d="M4 8l8 5 8-5"></path>
				</svg>
			</div>

			<div>
				<span>Email</span>
				<strong class="cc-account-stat__email">
					<?php echo esc_html( $email ); ?>
				</strong>
			</div>
		</article>
	</div>

	<div class="cc-account-dashboard__grid">
		<section class="cc-account-panel">
			<header class="cc-account-panel__header">
				<div>
					<h2>Данные компании</h2>
					<p>Основная информация о вашей организации</p>
				</div>

				<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>">
					Настройки
				</a>
			</header>

			<div class="cc-account-details">
				<div>
					<span>Пользователь</span>
					<strong><?php echo esc_html( $full_name ); ?></strong>
				</div>

				<div>
					<span>Компания</span>
					<strong>
						<?php echo esc_html( $company ?: 'Не указана' ); ?>
					</strong>
				</div>

				<div>
					<span>БИН</span>
					<strong>
						<?php echo esc_html( $bin ?: 'Не указан' ); ?>
					</strong>
				</div>

				<div>
					<span>Email</span>
					<strong><?php echo esc_html( $email ); ?></strong>
				</div>
			</div>
		</section>

		<section class="cc-account-panel">
			<header class="cc-account-panel__header">
				<div>
					<h2>Быстрые действия</h2>
					<p>Основные разделы личного кабинета</p>
				</div>
			</header>

			<div class="cc-account-actions">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">
					<span>Мои заказы</span>
					<strong>Открыть →</strong>
				</a>

				<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>">
					<span>Адреса</span>
					<strong>Настроить →</strong>
				</a>

				<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>">
					<span>Профиль</span>
					<strong>Изменить →</strong>
				</a>
			</div>
		</section>
	</div>
</div>
