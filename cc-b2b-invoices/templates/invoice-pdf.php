<?php
/**
 * @var array $data
 */

defined( 'ABSPATH' ) || exit;

$invoice = $data['invoice'];
$order   = $data['order'];
$seller  = $data['seller'];

$money = static function ( float $amount ): string {
	return number_format( $amount, 2, ',', ' ' );
};
?>
<!doctype html>
<html lang="ru">
<head>
	<meta charset="UTF-8">

	<style>
		@page {
			margin: 25px 28px;
		}

		body {
			margin: 0;
			color: #111;
			font-family: "DejaVu Sans", sans-serif;
			font-size: 9px;
			line-height: 1.4;
		}

		.sample {
			margin-bottom: 14px;
			padding: 8px;
			border: 2px solid #b91c1c;
			color: #b91c1c;
			font-size: 11px;
			font-weight: bold;
			text-align: center;
		}

		.bank-table,
		.items {
			width: 100%;
			border-collapse: collapse;
		}

		.bank-table td {
			padding: 5px 6px;
			border: 1px solid #222;
			vertical-align: top;
		}

		.bank-label {
			color: #555;
			font-size: 7px;
		}

		h1 {
			margin: 22px 0 5px;
			font-size: 17px;
			line-height: 1.25;
		}

		.subtitle {
			margin-bottom: 16px;
			color: #555;
		}

		.party {
			margin: 7px 0;
		}

		.party strong {
			display: inline-block;
			width: 80px;
		}

		.items {
			margin-top: 17px;
		}

		.items th,
		.items td {
			padding: 5px;
			border: 1px solid #222;
		}

		.items th {
			background: #ededed;
			font-size: 8px;
			text-align: center;
		}

		.number {
			text-align: right;
			white-space: nowrap;
		}

		.center {
			text-align: center;
		}

		.totals {
			width: 42%;
			margin: 12px 0 12px auto;
			border-collapse: collapse;
		}

		.totals td {
			padding: 4px 6px;
			border-bottom: 1px solid #bbb;
		}

		.total-main {
			font-size: 11px;
			font-weight: bold;
		}

		.words {
			margin-top: 12px;
			padding: 8px 0;
			border-top: 1px solid #222;
			border-bottom: 1px solid #222;
			font-weight: bold;
		}

		.note {
			margin-top: 12px;
			color: #444;
			font-size: 8px;
		}

		.signatures {
			width: 100%;
			margin-top: 34px;
			border-collapse: collapse;
		}

		.signatures td {
			width: 50%;
			padding: 8px 10px 8px 0;
			vertical-align: bottom;
		}

		.line {
			display: inline-block;
			width: 110px;
			border-bottom: 1px solid #222;
		}
	</style>
</head>

<body>
	<?php if ( $data['is_sample'] ) : ?>
		<div class="sample">
			ОБРАЗЕЦ — БАНКОВСКИЕ РЕКВИЗИТЫ НЕ ЗАПОЛНЕНЫ
		</div>
	<?php endif; ?>

	<table class="bank-table">
		<tr>
			<td rowspan="2" style="width: 55%;">
				<div class="bank-label">Бенефициар</div>

				<strong>
					<?php echo esc_html( $seller['seller_name'] ); ?>
				</strong>

				<br>

				БИН <?php echo esc_html( $seller['bin'] ); ?>
			</td>

			<td style="width: 15%;">
				<div class="bank-label">КБе</div>
				<?php echo esc_html( $seller['kbe'] ); ?>
			</td>

			<td style="width: 30%;">
				<div class="bank-label">ИИК / IBAN</div>
				<?php echo esc_html( $seller['iban'] ); ?>
			</td>
		</tr>

		<tr>
			<td>
				<div class="bank-label">КНП</div>
				<?php echo esc_html( $seller['knp'] ); ?>
			</td>

			<td>
				<div class="bank-label">БИК</div>
				<?php echo esc_html( $seller['bic'] ); ?>
			</td>
		</tr>

		<tr>
			<td colspan="2">
				<div class="bank-label">Банк бенефициара</div>
				<?php echo esc_html( $seller['bank'] ); ?>
			</td>

			<td>
				<div class="bank-label">Валюта</div>
				KZT
			</td>
		</tr>
	</table>

	<h1>
		Счёт на оплату №
		<?php echo esc_html( $invoice->invoice_number ); ?>
		от
		<?php echo esc_html(
			wp_date(
				'd.m.Y',
				strtotime( $invoice->created_at )
			)
		); ?>
	</h1>

	<div class="subtitle">
		Основание: заказ №<?php echo esc_html(
			$order->get_order_number()
		); ?>
	</div>

	<div class="party">
		<strong>Поставщик:</strong>

		<?php echo esc_html( $seller['seller_name'] ); ?>,
		БИН <?php echo esc_html( $seller['bin'] ); ?>,
		<?php echo esc_html( $seller['address'] ); ?>
	</div>

	<div class="party">
		<strong>Покупатель:</strong>

		<?php echo esc_html( $data['buyer_name'] ); ?>,
		БИН/ИИН <?php echo esc_html( $data['buyer_bin'] ); ?>,
		<?php echo esc_html( $data['buyer_address'] ); ?>
	</div>

	<table class="items">
		<thead>
			<tr>
				<th style="width: 4%;">№</th>
				<th style="width: 12%;">Артикул</th>
				<th>Наименование</th>
				<th style="width: 8%;">Ед.</th>
				<th style="width: 9%;">Кол-во</th>
				<th style="width: 14%;">Цена с НДС</th>
				<th style="width: 15%;">Сумма с НДС</th>
			</tr>
		</thead>

		<tbody>
			<?php foreach ( $data['rows'] as $index => $row ) : ?>
				<tr>
					<td class="center">
						<?php echo esc_html( (string) ( $index + 1 ) ); ?>
					</td>

					<td>
						<?php echo esc_html( $row['sku'] ?: '—' ); ?>
					</td>

					<td>
						<?php echo esc_html( $row['name'] ); ?>
					</td>

					<td class="center">
						<?php echo esc_html( $row['unit'] ); ?>
					</td>

					<td class="number">
						<?php echo esc_html(
							number_format(
								(float) $row['quantity'],
								2,
								',',
								' '
							)
						); ?>
					</td>

					<td class="number">
						<?php echo esc_html(
							$money( (float) $row['unit_price'] )
						); ?>
					</td>

					<td class="number">
						<?php echo esc_html(
							$money( (float) $row['total'] )
						); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<table class="totals">
		<tr>
			<td>Итого:</td>
			<td class="number">
				<?php echo esc_html( $money( $data['total'] ) ); ?>
				₸
			</td>
		</tr>

		<tr>
			<td>В том числе НДС 16%:</td>
			<td class="number">
				<?php echo esc_html( $money( $data['vat'] ) ); ?>
				₸
			</td>
		</tr>

		<tr class="total-main">
			<td>Всего к оплате:</td>
			<td class="number">
				<?php echo esc_html( $money( $data['total'] ) ); ?>
				₸
			</td>
		</tr>
	</table>

	<div class="words">
		Всего наименований:
		<?php echo esc_html( (string) count( $data['rows'] ) ); ?>,
		на сумму
		<?php echo esc_html( $money( $data['total'] ) ); ?> ₸.

		<br>

		Сумма прописью:
		<?php echo esc_html( $data['total_words'] ); ?>.
	</div>

	<div class="note">
		Счёт действителен в течение
		<?php echo esc_html( $seller['valid_days'] ); ?>
		банковских дней.

		<br>

		Назначение платежа: оплата за товар по счёту
		<?php echo esc_html( $invoice->invoice_number ); ?>,
		заказ №<?php echo esc_html( $order->get_order_number() ); ?>.
	</div>

	<table class="signatures">
		<tr>
			<td>
				Руководитель:
				<span class="line"></span>

				<?php echo esc_html( $seller['director'] ); ?>
			</td>

			<td>
				Главный бухгалтер:
				<span class="line"></span>

				<?php echo esc_html(
					$seller['accountant'] ?: '________________'
				); ?>
			</td>
		</tr>
	</table>
</body>
</html>
