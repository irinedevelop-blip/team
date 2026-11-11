<?php
/**
 * Plugin Name: CSS Cable — Счета на оплату
 * Description: Формирование, хранение и скачивание PDF-счетов для заказов WooCommerce.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 * Author: CSS Cable
 * Text Domain: cc-b2b-invoices
 */

defined( 'ABSPATH' ) || exit;

final class CC_B2B_Invoices {

	const VERSION  = '1.0.0';
	const ENDPOINT = 'documents';
	const OPTION   = 'cc_b2b_invoice_settings';

	private static ?self $instance = null;

	private string $table;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		global $wpdb;

		$this->table = $wpdb->prefix . 'cc_b2b_invoices';

		add_action( 'init', array( $this, 'register_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );

		add_filter(
			'woocommerce_account_menu_items',
			array( $this, 'account_menu_items' ),
			90
		);

		add_action(
			'woocommerce_account_' . self::ENDPOINT . '_endpoint',
			array( $this, 'documents_page' )
		);

		add_filter(
			'woocommerce_my_account_my_orders_actions',
			array( $this, 'order_actions' ),
			20,
			2
		);

		add_action(
			'woocommerce_order_details_after_order_table',
			array( $this, 'order_invoice_button' ),
			20
		);

		add_action(
			'template_redirect',
			array( $this, 'handle_frontend_action' ),
			5
		);

		add_action(
			'wp_enqueue_scripts',
			array( $this, 'enqueue_assets' )
		);

		add_action(
			'admin_menu',
			array( $this, 'register_settings_page' )
		);

		add_action(
			'admin_post_cc_save_invoice_settings',
			array( $this, 'save_settings' )
		);

		add_action(
			'admin_notices',
			array( $this, 'dependency_notice' )
		);
	}

	public static function activate(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'cc_b2b_invoices';
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			invoice_number varchar(64) NOT NULL,
			invoice_year smallint(4) unsigned NOT NULL,
			sequence_number bigint(20) unsigned NOT NULL,
			document_status varchar(32) NOT NULL DEFAULT 'created',
			order_status varchar(32) NOT NULL DEFAULT '',
			total decimal(20,2) NOT NULL DEFAULT 0.00,
			currency varchar(8) NOT NULL DEFAULT 'KZT',
			file_path text NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY order_id (order_id),
			UNIQUE KEY invoice_number (invoice_number),
			UNIQUE KEY year_sequence (invoice_year, sequence_number),
			KEY user_id (user_id)
		) {$charset_collate};";

		dbDelta( $sql );

		self::create_storage_directory();

		add_rewrite_endpoint(
			self::ENDPOINT,
			EP_ROOT | EP_PAGES
		);

		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	public function register_endpoint(): void {
		add_rewrite_endpoint(
			self::ENDPOINT,
			EP_ROOT | EP_PAGES
		);
	}

	public function register_query_var( array $vars ): array {
		$vars[] = self::ENDPOINT;

		return $vars;
	}

	public function dependency_notice(): void {
		if ( class_exists( '\Dompdf\Dompdf' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<strong>CSS Cable — Счета:</strong>
				библиотека Dompdf не найдена.
				Выполните <code>composer install</code> в папке плагина
				и загрузите папку <code>vendor</code>.
			</p>
		</div>
		<?php
	}

	public function account_menu_items( array $items ): array {
		unset( $items['downloads'] );

		$result   = array();
		$inserted = false;

		foreach ( $items as $endpoint => $label ) {
			$result[ $endpoint ] = $label;

			if ( 'orders' === $endpoint ) {
				$result[ self::ENDPOINT ] = 'Документы';
				$inserted                 = true;
			}
		}

		if ( ! $inserted ) {
			$result[ self::ENDPOINT ] = 'Документы';
		}

		return $result;
	}

	public function enqueue_assets(): void {
		if (
			! function_exists( 'is_account_page' )
			|| ! is_account_page()
		) {
			return;
		}

		wp_enqueue_style(
			'cc-b2b-invoices',
			plugin_dir_url( __FILE__ ) . 'assets/invoices.css',
			array(),
			self::VERSION
		);
	}

	public function order_actions(
		array $actions,
		WC_Order $order
	): array {
		if ( ! $this->can_access_order( $order ) ) {
			return $actions;
		}

		$invoice = $this->get_invoice_by_order(
			$order->get_id()
		);

		if ( $invoice ) {
			$actions['cc-download-invoice'] = array(
				'url'  => $this->action_url(
					'download',
					$order->get_id()
				),
				'name' => 'Скачать счёт',
			);
		} else {
			$actions['cc-generate-invoice'] = array(
				'url'  => $this->action_url(
					'generate',
					$order->get_id()
				),
				'name' => 'Сформировать счёт',
			);
		}

		return $actions;
	}

	public function order_invoice_button( WC_Order $order ): void {
		if ( ! $this->can_access_order( $order ) ) {
			return;
		}

		$invoice = $this->get_invoice_by_order(
			$order->get_id()
		);

		$url = $invoice
			? $this->action_url( 'download', $order->get_id() )
			: $this->action_url( 'generate', $order->get_id() );

		$label = $invoice
			? 'Скачать счёт на оплату PDF'
			: 'Сформировать счёт на оплату';
		?>
		<section class="cc-order-invoice-action">
			<div>
				<strong>Счёт на оплату</strong>

				<p>
					<?php if ( $invoice ) : ?>
						Счёт уже сформирован и доступен для скачивания.
					<?php else : ?>
						Сформируйте PDF-счёт по данным этого заказа.
					<?php endif; ?>
				</p>
			</div>

			<a class="button cc-invoice-button" href="<?php echo esc_url( $url ); ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		</section>
		<?php
	}

	private function action_url(
	string $action,
	int $order_id
): string {
	$nonce = wp_create_nonce(
		'cc_invoice_' . $action . '_' . $order_id
	);

	return add_query_arg(
		array(
			'cc_invoice_action' => $action,
			'order_id'          => $order_id,
			'cc_invoice_nonce'  => $nonce,
		),
		wc_get_account_endpoint_url( self::ENDPOINT )
	);
}


	public function handle_frontend_action(): void {
		if ( empty( $_GET['cc_invoice_action'] ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$action = sanitize_key(
			wp_unslash( $_GET['cc_invoice_action'] )
		);

		$order_id = isset( $_GET['order_id'] )
			? absint( $_GET['order_id'] )
			: 0;

		$nonce = isset( $_GET['cc_invoice_nonce'] )
			? sanitize_text_field(
				wp_unslash( $_GET['cc_invoice_nonce'] )
			)
			: '';

		if (
			! $order_id
			|| ! in_array( $action, array( 'generate', 'download' ), true )
			|| ! wp_verify_nonce(
				$nonce,
				'cc_invoice_' . $action . '_' . $order_id
			)
		) {
			wp_die(
				'Ссылка недействительна или устарела.',
				'Ошибка',
				array( 'response' => 403 )
			);
		}

		$order = wc_get_order( $order_id );

		if (
			! $order instanceof WC_Order
			|| ! $this->can_access_order( $order )
		) {
			wp_die(
				'У вас нет доступа к этому заказу.',
				'Доступ запрещён',
				array( 'response' => 403 )
			);
		}

		if ( 'generate' === $action ) {
			$result = $this->generate_invoice( $order );

			if ( is_wp_error( $result ) ) {
				wp_die(
					esc_html( $result->get_error_message() ),
					'Не удалось сформировать счёт',
					array( 'response' => 500 )
				);
			}

			wp_safe_redirect(
				$this->action_url( 'download', $order_id )
			);
			exit;
		}

		$this->download_invoice( $order );
	}

	private function can_access_order( WC_Order $order ): bool {
		if (
			current_user_can( 'manage_woocommerce' )
			|| current_user_can( 'manage_options' )
		) {
			return true;
		}

		return get_current_user_id() === (int) $order->get_customer_id();
	}

	private function generate_invoice( WC_Order $order ) {
		$existing = $this->get_invoice_by_order(
			$order->get_id()
		);

		if ( $existing ) {
			return $existing;
		}

		if ( ! class_exists( '\Dompdf\Dompdf' ) ) {
			$autoload = plugin_dir_path( __FILE__ )
				. 'vendor/autoload.php';

			if ( file_exists( $autoload ) ) {
				require_once $autoload;
			}
		}

		if ( ! class_exists( '\Dompdf\Dompdf' ) ) {
			return new WP_Error(
				'missing_dompdf',
				'Библиотека Dompdf не установлена.'
			);
		}

		$invoice = $this->reserve_invoice_number( $order );

		if ( is_wp_error( $invoice ) ) {
			return $invoice;
		}

		try {
			$data = $this->invoice_data(
				$order,
				$invoice
			);

			$html = $this->render_pdf_template( $data );

			$options = new \Dompdf\Options();
			$options->set( 'defaultFont', 'DejaVu Sans' );
			$options->set( 'isRemoteEnabled', false );
			$options->set( 'isHtml5ParserEnabled', true );

			$dompdf = new \Dompdf\Dompdf( $options );
			$dompdf->setPaper( 'A4', 'portrait' );
			$dompdf->loadHtml( $html, 'UTF-8' );
			$dompdf->render();

			$file_path = $this->invoice_file_path(
				$invoice->invoice_number,
				(int) $invoice->invoice_year
			);

			$result = file_put_contents(
				$file_path,
				$dompdf->output(),
				LOCK_EX
			);

			if ( false === $result ) {
				throw new RuntimeException(
					'Не удалось сохранить PDF-файл.'
				);
			}

			global $wpdb;

			$wpdb->update(
				$this->table,
				array(
					'file_path'  => $file_path,
					'updated_at' => current_time( 'mysql' ),
				),
				array(
					'id' => (int) $invoice->id,
				),
				array( '%s', '%s' ),
				array( '%d' )
			);

			return $this->get_invoice_by_order(
				$order->get_id()
			);
		} catch ( Throwable $exception ) {
			global $wpdb;

			$wpdb->delete(
				$this->table,
				array(
					'id' => (int) $invoice->id,
				),
				array( '%d' )
			);

			return new WP_Error(
				'invoice_generation_failed',
				$exception->getMessage()
			);
		}
	}

	private function reserve_invoice_number( WC_Order $order ) {
		global $wpdb;

		$year = (int) wp_date( 'Y' );

		for ( $attempt = 0; $attempt < 5; $attempt++ ) {
			$last_number = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(sequence_number)
					FROM {$this->table}
					WHERE invoice_year = %d",
					$year
				)
			);

			$sequence = $last_number + 1;

			$invoice_number = sprintf(
				'S-Onl-%d-%06d',
				$year,
				$sequence
			);

			$inserted = $wpdb->insert(
				$this->table,
				array(
					'order_id'        => $order->get_id(),
					'user_id'         => $order->get_customer_id(),
					'invoice_number'  => $invoice_number,
					'invoice_year'    => $year,
					'sequence_number' => $sequence,
					'document_status' => $this->document_status( $order ),
					'order_status'    => $order->get_status(),
					'total'           => $order->get_total(),
					'currency'        => $order->get_currency(),
					'file_path'       => '',
					'created_at'      => current_time( 'mysql' ),
					'updated_at'      => current_time( 'mysql' ),
				),
				array(
					'%d',
					'%d',
					'%s',
					'%d',
					'%d',
					'%s',
					'%s',
					'%f',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);

			if ( false !== $inserted ) {
				return $this->get_invoice_by_order(
					$order->get_id()
				);
			}

			$existing = $this->get_invoice_by_order(
				$order->get_id()
			);

			if ( $existing ) {
				return $existing;
			}
		}

		return new WP_Error(
			'invoice_number_failed',
			'Не удалось зарезервировать номер счёта.'
		);
	}

	private function invoice_data(
		WC_Order $order,
		object $invoice
	): array {
		$settings = $this->settings();
		$rows     = array();

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product();
			$quantity = (float) $item->get_quantity();

			$line_total = (float) $item->get_total()
				+ (float) $item->get_total_tax();

			$unit_price = $quantity > 0
				? $line_total / $quantity
				: $line_total;

			$unit = 'шт.';

			if (
				$product
				&& function_exists( 'csscable_b2b_product_unit' )
			) {
				$unit = csscable_b2b_product_unit( $product );
			}

			$rows[] = array(
				'sku'        => $product ? $product->get_sku() : '',
				'name'       => $item->get_name(),
				'unit'       => $unit ?: 'шт.',
				'quantity'   => $quantity,
				'unit_price' => $unit_price,
				'total'      => $line_total,
			);
		}

		$shipping_total = (float) $order->get_shipping_total()
			+ (float) $order->get_shipping_tax();

		if ( $shipping_total > 0 ) {
			$rows[] = array(
				'sku'        => '',
				'name'       => 'Доставка',
				'unit'       => 'усл.',
				'quantity'   => 1,
				'unit_price' => $shipping_total,
				'total'      => $shipping_total,
			);
		}

		foreach ( $order->get_items( 'fee' ) as $fee ) {
			$fee_total = (float) $fee->get_total()
				+ (float) $fee->get_total_tax();

			$rows[] = array(
				'sku'        => '',
				'name'       => $fee->get_name(),
				'unit'       => 'усл.',
				'quantity'   => 1,
				'unit_price' => $fee_total,
				'total'      => $fee_total,
			);
		}

		$total = (float) $order->get_total();
		$vat   = (float) $order->get_total_tax();

		if ( $vat <= 0 && $total > 0 ) {
			$vat = $total * 16 / 116;
		}

		$company = trim(
			(string) $order->get_billing_company()
		);

		$buyer_name = $company;

		if ( ! $buyer_name ) {
			$buyer_name = trim(
				$order->get_formatted_billing_full_name()
			);
		}

		$buyer_bin = $this->buyer_bin(
			$order
		);

		$buyer_address = wp_strip_all_tags(
			str_replace(
				'<br/>',
				', ',
				$order->get_formatted_billing_address()
			)
		);

		return array(
			'invoice'       => $invoice,
			'order'         => $order,
			'seller'        => $settings,
			'rows'          => $rows,
			'total'         => $total,
			'vat'           => $vat,
			'buyer_name'    => $buyer_name ?: 'Не указан',
			'buyer_bin'     => $buyer_bin ?: 'Не указан',
			'buyer_address' => $buyer_address ?: 'Не указан',
			'buyer_phone'   => $order->get_billing_phone() ?: 'Не указан',
			'buyer_email'   => $order->get_billing_email() ?: 'Не указан',
			'total_words'   => $this->amount_in_words( $total ),
			'is_sample'     => $this->settings_incomplete( $settings ),
		);
	}

	private function buyer_bin( WC_Order $order ): string {
		$keys = array(
			'_billing_bin',
			'billing_bin',
			'_cc_b2b_bin',
			'cc_b2b_bin',
		);

		foreach ( $keys as $key ) {
			$value = trim(
				(string) $order->get_meta( $key )
			);

			if ( $value ) {
				return $value;
			}
		}

		$user_id = $order->get_customer_id();

		if ( $user_id ) {
			foreach (
				array(
					'_cc_b2b_bin',
					'cc_b2b_bin',
					'_billing_bin',
				) as $key
			) {
				$value = trim(
					(string) get_user_meta(
						$user_id,
						$key,
						true
					)
				);

				if ( $value ) {
					return $value;
				}
			}
		}

		return '';
	}

	private function render_pdf_template( array $data ): string {
		$template = plugin_dir_path( __FILE__ )
			. 'templates/invoice-pdf.php';

		if ( ! file_exists( $template ) ) {
			throw new RuntimeException(
				'Шаблон PDF не найден.'
			);
		}

		ob_start();

		include $template;

		return (string) ob_get_clean();
	}

	private function download_invoice( WC_Order $order ): void {
		$invoice = $this->get_invoice_by_order(
			$order->get_id()
		);

		if (
			! $invoice
			|| ! $invoice->file_path
			|| ! file_exists( $invoice->file_path )
		) {
			wp_die(
				'PDF-счёт не найден.',
				'Документ не найден',
				array( 'response' => 404 )
			);
		}

		$storage = realpath(
			self::storage_directory()
		);

		$file = realpath(
			$invoice->file_path
		);

		if (
			! $storage
			|| ! $file
			|| 0 !== strpos( $file, $storage )
		) {
			wp_die(
				'Некорректный путь к документу.',
				'Ошибка безопасности',
				array( 'response' => 403 )
			);
		}

		nocache_headers();

		header( 'Content-Type: application/pdf' );
		header(
			'Content-Disposition: attachment; filename="'
			. sanitize_file_name( $invoice->invoice_number )
			. '.pdf"'
		);
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'X-Content-Type-Options: nosniff' );

		readfile( $file );
		exit;
	}

	public function documents_page(): void {
		global $wpdb;

		$user_id = get_current_user_id();

		if (
			current_user_can( 'manage_woocommerce' )
			|| current_user_can( 'manage_options' )
		) {
			$documents = $wpdb->get_results(
				"SELECT * FROM {$this->table}
				ORDER BY created_at DESC"
			);
		} else {
			$documents = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->table}
					WHERE user_id = %d
					ORDER BY created_at DESC",
					$user_id
				)
			);
		}
		?>
		<div class="cc-documents-page">
			<header class="cc-documents-header">
				<div>
					<span>Личный кабинет</span>
					<h1>Документы</h1>

					<p>
						Счета на оплату, сформированные по вашим заказам.
					</p>
				</div>
			</header>

			<?php if ( empty( $documents ) ) : ?>
				<div class="cc-documents-empty">
					<strong>Документов пока нет</strong>

					<p>
						Откройте нужный заказ и нажмите
						«Сформировать счёт на оплату».
					</p>

					<a href="<?php echo esc_url(
						wc_get_account_endpoint_url( 'orders' )
					); ?>">
						Перейти к заказам
					</a>
				</div>
			<?php else : ?>
				<div class="cc-documents-table-wrap">
					<table class="cc-documents-table">
						<thead>
							<tr>
								<th>Документ</th>
								<th>Заказ</th>
								<th>Дата</th>
								<th>Сумма</th>
								<th>Статус</th>
								<th></th>
							</tr>
						</thead>

						<tbody>
							<?php foreach ( $documents as $document ) : ?>
								<?php
								$order = wc_get_order(
									(int) $document->order_id
								);

								if ( ! $order ) {
									continue;
								}

								$status = $this->document_status(
									$order
								);
								?>
								<tr>
									<td>
										<strong>
											<?php echo esc_html(
												$document->invoice_number
											); ?>
										</strong>

										<span>Счёт на оплату</span>
									</td>

									<td>
										<a href="<?php echo esc_url(
											$order->get_view_order_url()
										); ?>">
											№<?php echo esc_html(
												$order->get_order_number()
											); ?>
										</a>
									</td>

									<td>
										<?php echo esc_html(
											wp_date(
												'd.m.Y',
												strtotime(
													$document->created_at
												)
											)
										); ?>
									</td>

									<td>
										<?php echo wp_kses_post(
											wc_price(
												(float) $document->total,
												array(
													'currency' => $document->currency,
												)
											)
										); ?>
									</td>

									<td>
										<span class="cc-document-status cc-document-status--<?php echo esc_attr( $status ); ?>">
											<?php echo esc_html(
												$this->document_status_label(
													$status
												)
											); ?>
										</span>
									</td>

									<td>
										<a
											class="cc-document-download"
											href="<?php echo esc_url(
												$this->action_url(
													'download',
													$order->get_id()
												)
											); ?>"
										>
											Скачать PDF
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function document_status( WC_Order $order ): string {
		if ( $order->is_paid() ) {
			return 'paid';
		}

		if (
			$order->has_status(
				array( 'cancelled', 'refunded', 'failed' )
			)
		) {
			return 'cancelled';
		}

		return 'created';
	}

	private function document_status_label(
		string $status
	): string {
		$labels = array(
			'paid'      => 'Оплачен',
			'cancelled' => 'Аннулирован',
			'created'   => 'Сформирован',
		);

		return $labels[ $status ] ?? 'Сформирован';
	}

	private function get_invoice_by_order(
		int $order_id
	): ?object {
		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE order_id = %d
				LIMIT 1",
				$order_id
			)
		);

		return $result ?: null;
	}

	private static function storage_directory(): string {
		$upload = wp_upload_dir();

		return trailingslashit( $upload['basedir'] )
			. 'cc-b2b-invoices';
	}

	private static function create_storage_directory(): void {
		$directory = self::storage_directory();

		wp_mkdir_p( $directory );

		if ( ! file_exists( $directory . '/index.php' ) ) {
			file_put_contents(
				$directory . '/index.php',
				"<?php\n// Silence is golden.\n"
			);
		}

		if ( ! file_exists( $directory . '/.htaccess' ) ) {
			file_put_contents(
				$directory . '/.htaccess',
				"Require all denied\nDeny from all\n"
			);
		}
	}

	private function invoice_file_path(
		string $invoice_number,
		int $year
	): string {
		self::create_storage_directory();

		$directory = trailingslashit(
			self::storage_directory()
		) . $year;

		wp_mkdir_p( $directory );

		$random = wp_generate_password(
			20,
			false,
			false
		);

		return trailingslashit( $directory )
			. sanitize_file_name(
				$invoice_number . '-' . $random . '.pdf'
			);
	}

	private function settings(): array {
		$defaults = array(
			'seller_name' => 'Товарищество с ограниченной ответственностью "Кабельный завод "ЦСС-КАБЕЛЬ"',
			'bin'         => '231040028227',
			'address'     => 'Республика Казахстан, 100000, Карагандинская область, г. Караганда, район имени Казыбек Би, мкр. Таугуль, ул. Жанторе Абишева, строение 1/1, офис 209',
			'director'    => 'ҚАЙРЖАН БИЖІГІТ АЙТҚОЖАҰЛЫ',
			'accountant'  => '',
			'bank'        => 'НЕ ЗАПОЛНЕНО',
			'iban'        => 'НЕ ЗАПОЛНЕНО',
			'bic'         => 'НЕ ЗАПОЛНЕНО',
			'kbe'         => 'НЕ ЗАПОЛНЕНО',
			'knp'         => 'НЕ ЗАПОЛНЕНО',
			'phone'       => '',
			'email'       => '',
			'vat_number'  => '',
			'valid_days'  => '3',
		);

		return wp_parse_args(
			get_option( self::OPTION, array() ),
			$defaults
		);
	}

	private function settings_incomplete(
		array $settings
	): bool {
		foreach (
			array( 'bank', 'iban', 'bic', 'kbe', 'knp' )
			as $key
		) {
			if (
				empty( $settings[ $key ] )
				|| 'НЕ ЗАПОЛНЕНО' === $settings[ $key ]
			) {
				return true;
			}
		}

		return false;
	}

	public function register_settings_page(): void {
		add_submenu_page(
			'woocommerce',
			'Настройки счетов',
			'Счета на оплату',
			'manage_woocommerce',
			'cc-b2b-invoices',
			array( $this, 'settings_page' )
		);
	}

	public function settings_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings = $this->settings();

		$fields = array(
			'seller_name' => 'Полное наименование',
			'bin'         => 'БИН',
			'address'     => 'Юридический адрес',
			'director'    => 'Руководитель',
			'accountant'  => 'Главный бухгалтер',
			'bank'        => 'Банк',
			'iban'        => 'ИИК / IBAN',
			'bic'         => 'БИК',
			'kbe'         => 'КБе',
			'knp'         => 'КНП',
			'phone'       => 'Телефон',
			'email'       => 'Email',
			'vat_number'  => 'Свидетельство по НДС',
			'valid_days'  => 'Срок оплаты, банковских дней',
		);
		?>
		<div class="wrap">
			<h1>Настройки счетов на оплату</h1>

			<form
				action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				method="post"
			>
				<input
					type="hidden"
					name="action"
					value="cc_save_invoice_settings"
				>

				<?php wp_nonce_field( 'cc_save_invoice_settings' ); ?>

				<table class="form-table">
					<?php foreach ( $fields as $key => $label ) : ?>
						<tr>
							<th>
								<label for="<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $label ); ?>
								</label>
							</th>

							<td>
								<?php if ( 'address' === $key ) : ?>
									<textarea
										class="large-text"
										id="<?php echo esc_attr( $key ); ?>"
										name="settings[<?php echo esc_attr( $key ); ?>]"
										rows="3"
									><?php echo esc_textarea( $settings[ $key ] ); ?></textarea>
								<?php else : ?>
									<input
										class="regular-text"
										id="<?php echo esc_attr( $key ); ?>"
										name="settings[<?php echo esc_attr( $key ); ?>]"
										value="<?php echo esc_attr( $settings[ $key ] ); ?>"
									>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<?php submit_button( 'Сохранить реквизиты' ); ?>
			</form>
		</div>
		<?php
	}

	public function save_settings(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Недостаточно прав.' );
		}

		check_admin_referer( 'cc_save_invoice_settings' );

		$raw = isset( $_POST['settings'] )
			? (array) wp_unslash( $_POST['settings'] )
			: array();

		$settings = array();

		foreach ( $raw as $key => $value ) {
			$settings[ sanitize_key( $key ) ] = sanitize_text_field(
				(string) $value
			);
		}

		update_option(
			self::OPTION,
			$settings,
			false
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'cc-b2b-invoices',
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private function amount_in_words(
		float $amount
	): string {
		$integer = (int) floor( $amount );
		$coins   = (int) round( ( $amount - $integer ) * 100 );

		if ( $coins >= 100 ) {
			$integer++;
			$coins = 0;
		}

		$words = $this->integer_in_words( $integer );

		return trim(
			$words
			. ' тенге '
			. str_pad( (string) $coins, 2, '0', STR_PAD_LEFT )
			. ' тиын'
		);
	}

	private function integer_in_words(
		int $number
	): string {
		if ( 0 === $number ) {
			return 'ноль';
		}

		$ones_male = array(
			'',
			'один',
			'два',
			'три',
			'четыре',
			'пять',
			'шесть',
			'семь',
			'восемь',
			'девять',
		);

		$ones_female = array(
			'',
			'одна',
			'две',
			'три',
			'четыре',
			'пять',
			'шесть',
			'семь',
			'восемь',
			'девять',
		);

		$teens = array(
			10 => 'десять',
			11 => 'одиннадцать',
			12 => 'двенадцать',
			13 => 'тринадцать',
			14 => 'четырнадцать',
			15 => 'пятнадцать',
			16 => 'шестнадцать',
			17 => 'семнадцать',
			18 => 'восемнадцать',
			19 => 'девятнадцать',
		);

		$tens = array(
			2 => 'двадцать',
			3 => 'тридцать',
			4 => 'сорок',
			5 => 'пятьдесят',
			6 => 'шестьдесят',
			7 => 'семьдесят',
			8 => 'восемьдесят',
			9 => 'девяносто',
		);

		$hundreds = array(
			1 => 'сто',
			2 => 'двести',
			3 => 'триста',
			4 => 'четыреста',
			5 => 'пятьсот',
			6 => 'шестьсот',
			7 => 'семьсот',
			8 => 'восемьсот',
			9 => 'девятьсот',
		);

		$groups = array(
			array( '', '', '', false ),
			array( 'тысяча', 'тысячи', 'тысяч', true ),
			array( 'миллион', 'миллиона', 'миллионов', false ),
			array( 'миллиард', 'миллиарда', 'миллиардов', false ),
		);

		$result      = array();
		$group_index = 0;

		while ( $number > 0 ) {
			$part = $number % 1000;

			if ( $part > 0 ) {
				$part_words = array();

				$hundred = intdiv( $part, 100 );

				if ( $hundred ) {
					$part_words[] = $hundreds[ $hundred ];
				}

				$remainder = $part % 100;

				if ( $remainder >= 10 && $remainder <= 19 ) {
					$part_words[] = $teens[ $remainder ];
				} else {
					$ten = intdiv( $remainder, 10 );
					$one = $remainder % 10;

					if ( $ten ) {
						$part_words[] = $tens[ $ten ];
					}

					if ( $one ) {
						$ones = ! empty( $groups[ $group_index ][3] )
							? $ones_female
							: $ones_male;

						$part_words[] = $ones[ $one ];
					}
				}

				if ( $group_index > 0 ) {
					$form = $this->plural_form(
						$part,
						$groups[ $group_index ][0],
						$groups[ $group_index ][1],
						$groups[ $group_index ][2]
					);

					$part_words[] = $form;
				}

				array_unshift(
					$result,
					implode( ' ', $part_words )
				);
			}

			$number = intdiv( $number, 1000 );
			$group_index++;
		}

		return implode( ' ', $result );
	}

	private function plural_form(
		int $number,
		string $one,
		string $few,
		string $many
	): string {
		$number = abs( $number ) % 100;
		$last   = $number % 10;

		if ( $number > 10 && $number < 20 ) {
			return $many;
		}

		if ( 1 === $last ) {
			return $one;
		}

		if ( $last >= 2 && $last <= 4 ) {
			return $few;
		}

		return $many;
	}
}

$autoload = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

register_activation_hook(
	__FILE__,
	array( 'CC_B2B_Invoices', 'activate' )
);

register_deactivation_hook(
	__FILE__,
	array( 'CC_B2B_Invoices', 'deactivate' )
);

add_action(
	'plugins_loaded',
	array( 'CC_B2B_Invoices', 'instance' ),
	20
);
