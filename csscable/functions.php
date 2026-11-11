
<?php
/**
 * Основные функции темы CSS Cable B2B.
 *
 * @package CSSCable
 */

defined( 'ABSPATH' ) || exit;

define( 'CSSCABLE_VERSION', '0.3.3' );
define( 'CSSCABLE_DIR', get_template_directory() );
define( 'CSSCABLE_URI', get_template_directory_uri() );

/**
 * Настройка темы.
 */
function csscable_setup(): void {
	load_theme_textdomain(
		'csscable',
		CSSCABLE_DIR . '/languages'
	);

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 70,
			'width'       => 220,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 420,
			'single_image_width'    => 720,
			'product_grid'          => array(
				'default_rows'    => 12,
				'min_rows'        => 4,
				'max_rows'        => 30,
				'default_columns' => 1,
				'min_columns'     => 1,
				'max_columns'     => 1,
			),
		)
	);

	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary' => esc_html__(
				'Основное меню',
				'csscable'
			),
			'footer'  => esc_html__(
				'Меню в подвале',
				'csscable'
			),
		)
	);
}
add_action( 'after_setup_theme', 'csscable_setup' );

/**
 * Подключение общих стилей и скриптов.
 */
function csscable_enqueue_assets(): void {
	wp_enqueue_style(
		'csscable-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'csscable-style',
		get_stylesheet_uri(),
		array( 'csscable-fonts' ),
		CSSCABLE_VERSION
	);

	wp_enqueue_style(
		'csscable-app',
		CSSCABLE_URI . '/assets/css/app.css',
		array( 'csscable-style' ),
		CSSCABLE_VERSION
	);

	wp_enqueue_script(
		'csscable-theme',
		CSSCABLE_URI . '/assets/js/theme.js',
		array(),
		CSSCABLE_VERSION,
		true
	);

	/*
	 * Стандартный WooCommerce-кабинет:
	 * /my-account/
	 */
	if (
		function_exists( 'is_account_page' )
		&& is_account_page()
	) {
		wp_enqueue_style(
			'csscable-my-account',
			CSSCABLE_URI . '/assets/css/my-account.css',
			array( 'csscable-app' ),
			CSSCABLE_VERSION
		);

		wp_add_inline_script(
			'csscable-theme',
			'document.addEventListener("click",function(event){
				var button=event.target.closest("[data-password-toggle]");
				if(!button){return;}

				var wrap=button.closest(".cc-password-field");
				var input=wrap ? wrap.querySelector("input") : null;

				if(!input){return;}

				var show=input.type==="password";
				input.type=show ? "text" : "password";
				button.setAttribute(
					"aria-label",
					show ? "Скрыть пароль" : "Показать пароль"
				);
			});'
		);
	}
}
add_action( 'wp_enqueue_scripts', 'csscable_enqueue_assets' );

/**
 * Подключение файлов оформления корзины,
 * оформления заказа и страницы успешного заказа.
 */
$csscable_commerce_pages_file = CSSCABLE_DIR . '/inc/commerce-pages.php';

if ( file_exists( $csscable_commerce_pages_file ) ) {
	require_once $csscable_commerce_pages_file;
}

/**
 * Стили внутреннего кабинета:
 *
 * ?cc_section=dashboard
 * ?cc_section=cabinet
 * ?cc_section=bonuses
/**
 * Подключение стилей разделов внутреннего кабинета.
 */
function csscable_cabinet_section_assets(): void {
	if ( ! is_user_logged_in() || ! is_front_page() ) {
		return;
	}

	$section = isset( $_GET['cc_section'] )
		? sanitize_key( wp_unslash( $_GET['cc_section'] ) )
		: 'dashboard';

	$allowed_sections = array(
		'dashboard',
		'cabinet',
		'bonuses',
	);

	if ( ! in_array( $section, $allowed_sections, true ) ) {
		return;
	}

	wp_enqueue_style(
		'csscable-cabinet-topbar',
		CSSCABLE_URI . '/assets/css/cabinet-topbar.css',
		array( 'csscable-app' ),
		CSSCABLE_VERSION
	);

	wp_enqueue_style(
		'csscable-cabinet-unified',
		CSSCABLE_URI . '/assets/css/cabinet-unified.css',
		array(
			'csscable-app',
			'csscable-cabinet-topbar',
		),
		CSSCABLE_VERSION
	);

	if ( 'cabinet' === $section ) {
		wp_enqueue_style(
			'csscable-buyer-orders',
			CSSCABLE_URI . '/assets/css/cabinet-orders.css',
			array(
				'csscable-app',
				'csscable-cabinet-topbar',
				'csscable-cabinet-unified',
			),
			CSSCABLE_VERSION
		);
	}
}
add_action(
	'wp_enqueue_scripts',
	'csscable_cabinet_section_assets',
	50
);

add_action(
	'wp_enqueue_scripts',
	'csscable_cabinet_section_assets',
	50
);

/**
 * Отключает стандартный сайдбар WooCommerce.
 */
function csscable_disable_woocommerce_sidebar(): void {
	if ( class_exists( 'WooCommerce' ) ) {
		remove_action(
			'woocommerce_sidebar',
			'woocommerce_get_sidebar',
			10
		);
	}
}
add_action(
	'wp',
	'csscable_disable_woocommerce_sidebar'
);

/**
 * Количество товаров в корзине.
 */
function csscable_cart_count(): int {
	if (
		class_exists( 'WooCommerce' )
		&& WC()->cart
	) {
		return (int) WC()->cart->get_cart_contents_count();
	}

	return 0;
}

/**
 * Обновляет счётчик корзины через AJAX-фрагменты WooCommerce.
 *
 * @param array<string,string> $fragments Фрагменты.
 * @return array<string,string>
 */
function csscable_cart_count_fragment(
	array $fragments
): array {
	ob_start();
	?>
	<span class="cc-cart-count">
		<?php echo esc_html( (string) csscable_cart_count() ); ?>
	</span>
	<?php

	$fragments['.cc-cart-count'] = (string) ob_get_clean();

	return $fragments;
}
add_filter(
	'woocommerce_add_to_cart_fragments',
	'csscable_cart_count_fragment'
);

/**
 * URL каталога.
 */
function csscable_shop_url(): string {
	if (
		function_exists( 'wc_get_page_permalink' )
		&& wc_get_page_permalink( 'shop' )
	) {
		return wc_get_page_permalink( 'shop' );
	}

	return home_url( '/catalog/' );
}

/**
 * URL корзины.
 */
function csscable_cart_url(): string {
	if ( function_exists( 'wc_get_cart_url' ) ) {
		return wc_get_cart_url();
	}

	return home_url( '/cart/' );
}

/**
 * URL личного кабинета.
 */
function csscable_account_url(): string {
	if (
		function_exists( 'wc_get_page_permalink' )
		&& wc_get_page_permalink( 'myaccount' )
	) {
		return wc_get_page_permalink( 'myaccount' );
	}

	return wp_login_url();
}

/**
 * Проверяет активность пункта боковой навигации.
 *
 * @param string $section Раздел.
 */
function csscable_is_active( string $section ): bool {
	switch ( $section ) {
		case 'home':
			return is_front_page();

		case 'catalog':
			return function_exists( 'is_shop' )
				&& (
					is_shop()
					|| is_product_taxonomy()
					|| is_product()
				);

		case 'cart':
			return function_exists( 'is_cart' )
				&& is_cart();

		case 'checkout':
			return function_exists( 'is_checkout' )
				&& is_checkout();

		case 'account':
			return function_exists( 'is_account_page' )
				&& is_account_page();

		default:
			return false;
	}
}

/**
 * SVG-иконки темы.
 *
 * @param string $name Название иконки.
 */
function csscable_icon( string $name ): string {
	$icons = array(
		'home' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11.2 12 4l9 7.2v8.3a1.5 1.5 0 0 1-1.5 1.5H15v-6H9v6H4.5A1.5 1.5 0 0 1 3 19.5z"/></svg>',

		'catalog' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="7" height="7" rx="1"/><rect x="14" y="4" width="7" height="7" rx="1"/><rect x="3" y="15" width="7" height="6" rx="1"/><rect x="14" y="15" width="7" height="6" rx="1"/></svg>',

		'cart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H6"/><circle cx="10" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>',

		'orders' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16l-3-2-4 2-4-2-3 2V5a2 2 0 0 1 2-2z"/><path d="M8 8h8M8 12h8"/></svg>',

		'documents' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>',

		'company' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V7l8-4v18M12 9h8v12M8 8v2M8 13v2M8 18v2M16 13v2M16 18v2M2 21h20"/></svg>',

		'user' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',

		'menu' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',

		'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>',

		'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M18 12H9"/></svg>',
	);

	return $icons[ $name ] ?? '';
}

/**
 * Открывает обёртку основного контента.
 */
function csscable_content_wrapper_start(): void {
	echo '<div class="cc-content">';
}

/**
 * Закрывает обёртку основного контента.
 */
function csscable_content_wrapper_end(): void {
	echo '</div>';
}

/**
 * Заменяет стандартные обёртки WooCommerce.
 */
function csscable_woocommerce_wrappers(): void {
	remove_action(
		'woocommerce_before_main_content',
		'woocommerce_output_content_wrapper',
		10
	);

	remove_action(
		'woocommerce_after_main_content',
		'woocommerce_output_content_wrapper_end',
		10
	);

	add_action(
		'woocommerce_before_main_content',
		'csscable_content_wrapper_start',
		10
	);

	add_action(
		'woocommerce_after_main_content',
		'csscable_content_wrapper_end',
		10
	);
}
add_action(
	'after_setup_theme',
	'csscable_woocommerce_wrappers',
	20
);

 
add_filter(
	'woocommerce_result_count',
	'csscable_product_count_text',
	10,
	2
);

add_filter(
	'woocommerce_result_count',
	'csscable_product_count_text',
	10,
	2
);

/**
 * Классы body.
 *
 * @param string[] $classes Классы страницы.
 * @return string[]
 */
function csscable_body_classes(
	array $classes
): array {
	$classes[] = 'cc-theme';
	$classes[] = 'pro';

	if (
		function_exists( 'is_woocommerce' )
		&& (
			is_woocommerce()
			|| is_cart()
			|| is_checkout()
			|| is_account_page()
		)
	) {
		$classes[] = 'cc-woocommerce-page';
	}

	return $classes;
}
add_filter( 'body_class', 'csscable_body_classes' );

/**
 * Классы body для разделов внутреннего кабинета.
 *
 * @param string[] $classes Классы страницы.
 * @return string[]
 */
function csscable_cabinet_section_body_classes(
	array $classes
): array {
	$section = isset( $_GET['cc_section'] )
		? sanitize_key( wp_unslash( $_GET['cc_section'] ) )
		: 'dashboard';

	$allowed_sections = array(
		'dashboard',
		'cabinet',
		'bonuses',
	);

	if (
		is_user_logged_in()
		&& is_front_page()
		&& in_array( $section, $allowed_sections, true )
	) {
		$classes[] = 'cc-cabinet-section';
		$classes[] = 'cc-section-' . $section;
	}

	return $classes;
}
add_filter(
	'body_class',
	'csscable_cabinet_section_body_classes',
	30
);

/**
 * Скрывает стандартный заголовок WooCommerce.
 */
add_filter( 'woocommerce_show_page_title', '__return_false' );

/**
 * Один товар в строке каталога.
 */
function csscable_loop_columns(): int {
	return 1;
}
add_filter( 'loop_shop_columns', 'csscable_loop_columns' );

/**
 * Стили и скрипты страницы товара.
 */
function csscable_single_product_assets(): void {
	if (
		! function_exists( 'is_product' )
		|| ! is_product()
	) {
		return;
	}

	wp_enqueue_style(
		'csscable-single-product',
		CSSCABLE_URI . '/assets/css/single-product.css',
		array( 'csscable-app' ),
		CSSCABLE_VERSION
	);

	wp_enqueue_script(
		'csscable-single-product',
		CSSCABLE_URI . '/assets/js/single-product.js',
		array(),
		CSSCABLE_VERSION,
		true
	);
}
add_action(
	'wp_enqueue_scripts',
	'csscable_single_product_assets',
	30
);

/**
 * Редирект кнопки «Заказать в один клик»
 * на страницу оформления заказа.
 */
function csscable_buy_now_redirect(
	string $url
): string {
	$buy_now = isset( $_REQUEST['cc_buy_now'] )
		? sanitize_text_field(
			wp_unslash( $_REQUEST['cc_buy_now'] )
		)
		: '';

	if (
		'1' === $buy_now
		&& function_exists( 'wc_get_checkout_url' )
	) {
		return wc_get_checkout_url();
	}

	return $url;
}
add_filter(
	'woocommerce_add_to_cart_redirect',
	'csscable_buy_now_redirect'
);

/**
 * Убирает стандартные хлебные крошки
 * на странице товара.
 */
function csscable_remove_single_breadcrumbs(): void {
	if (
		function_exists( 'is_product' )
		&& is_product()
	) {
		remove_action(
			'woocommerce_before_main_content',
			'woocommerce_breadcrumb',
			20
		);
	}
}
add_action(
	'wp',
	'csscable_remove_single_breadcrumbs',
	20
);

 
/**
 * Сообщение после добавления товара в корзину.
 *
 * @param string $message Стандартное сообщение.
 * @param array  $products Товары и количество.
 */
function csscable_add_to_cart_message(
	string $message,
	array $products
): string {
	$notices = array();

	foreach ( $products as $product_id => $quantity ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			continue;
		}

		$notices[] = sprintf(
			'«%1$s» — %2$s %3$s',
			wp_strip_all_tags( $product->get_name() ),
			wc_format_decimal(
				(float) $quantity,
				0
			),
			csscable_b2b_product_unit( $product )
		);
	}

	if ( empty( $notices ) ) {
		return $message;
	}

	$cart_link = sprintf(
		'<a href="%1$s" class="cc-cart-notice-link">%2$s</a>',
		esc_url( wc_get_cart_url() ),
		esc_html__( 'Перейти в корзину', 'csscable' )
	);

	return sprintf(
		'<span class="cc-cart-notice-text">%1$s %2$s</span> %3$s',
		esc_html__( 'В корзину добавлено:', 'csscable' ),
		esc_html( implode( ', ', $notices ) ),
		$cart_link
	);
}
add_filter(
	'wc_add_to_cart_message_html',
	'csscable_add_to_cart_message',
	10,
	2
);

/**
 * Стили страницы входа.
 */
function csscable_login_assets(): void {
	wp_enqueue_style(
		'csscable-login-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'csscable-login',
		CSSCABLE_URI . '/assets/css/login.css',
		array( 'csscable-login-fonts' ),
		CSSCABLE_VERSION
	);
}
add_action(
	'login_enqueue_scripts',
	'csscable_login_assets'
);

/**
 * Дополнительная разметка страницы входа.
 *
 * @param string $message Стандартное сообщение.
 */
function csscable_login_message(
	string $message
): string {
	static $rendered = false;

	if ( $rendered ) {
		return $message;
	}

	$rendered = true;

	ob_start();
	?>
	<section class="cc-login-hero" aria-label="ЦСС Кабель B2B">
		<div class="cc-login-hero__inner">
			<a
				class="cc-login-brand"
				href="<?php echo esc_url( home_url( '/' ) ); ?>"
			>
				<img
					class="cc-login-brand__logo"
					src="<?php echo esc_url( CSSCABLE_URI . '/assets/images/logow.png' ); ?>"
					alt="ЦСС Кабель"
					width="347"
					height="215"
				>
			</a>

			<div class="cc-login-hero__content">
				<p class="cc-login-hero__eyebrow">B2B-портал</p>

				<h2>Всё для работы в одном кабинете</h2>

				<p class="cc-login-hero__lead">
					Заказы, документы, персональные цены
					и поддержка менеджера — в одном месте.
				</p>

				<ul class="cc-login-benefits">
					<li>
						<span aria-hidden="true">✓</span>
						Быстрое оформление заказов
					</li>

					<li>
						<span aria-hidden="true">✓</span>
						Актуальные цены и наличие
					</li>

					<li>
						<span aria-hidden="true">✓</span>
						История заказов и документы
					</li>
				</ul>
			</div>
		</div>
	</section>

	<div class="cc-login-heading">
		<p class="cc-login-heading__eyebrow">
			Личный кабинет
		</p>

		<h1>Добро пожаловать</h1>

		<p>
			Введите данные для входа в B2B-портал.
		</p>
	</div>
	<?php

	return (string) ob_get_clean() . $message;
}
add_filter(
	'login_message',
	'csscable_login_message'
);

/**
 * URL логотипа страницы входа.
 */
function csscable_login_logo_url(): string {
	return home_url( '/' );
}
add_filter(
	'login_headerurl',
	'csscable_login_logo_url'
);

/**
 * Текст логотипа страницы входа.
 */
function csscable_login_logo_text(): string {
	return get_bloginfo( 'name' );
}
add_filter(
	'login_headertext',
	'csscable_login_logo_text'
);

/**
 * После входа отправляет пользователя
 * на главную страницу — раздел «Обзор».
 *
 * @param string           $redirect_to Запланированный URL.
 * @param string           $requested_redirect_to Запрошенный URL.
 * @param WP_User|WP_Error $user Пользователь.
 */
function csscable_login_redirect_to_overview(
	string $redirect_to,
	string $requested_redirect_to,
	$user
): string {
	if (
		is_wp_error( $user )
		|| ! $user instanceof WP_User
	) {
		return $redirect_to;
	}

	return add_query_arg(
		'cc_section',
		'dashboard',
		home_url( '/' )
	);
}
add_filter(
	'login_redirect',
	'csscable_login_redirect_to_overview',
	999,
	3
);

/**
 * Меню стандартного WooCommerce-кабинета.
 *
 * @param array<string,string> $items Пункты меню.
 * @return array<string,string>
 */
function csscable_account_menu_items(
	array $items
): array {
	unset( $items['downloads'] );

	$labels = array(
		'dashboard'       => 'Обзор',
		'orders'          => 'Мои заказы',
		'edit-address'    => 'Адреса',
		'payment-methods' => 'Способы оплаты',
		'edit-account'    => 'Настройки профиля',
		'customer-logout' => 'Выйти',
	);

	$ordered = array();

	foreach ( $labels as $key => $label ) {
		if ( isset( $items[ $key ] ) ) {
			$ordered[ $key ] = $label;
		}
	}

	foreach ( $items as $key => $label ) {
		if ( ! isset( $ordered[ $key ] ) ) {
			$ordered[ $key ] = $label;
		}
	}

	return $ordered;
}
add_filter(
	'woocommerce_account_menu_items',
	'csscable_account_menu_items',
	100
);
