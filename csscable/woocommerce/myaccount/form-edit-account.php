<?php
/**
 * Редактирование профиля клиента.
 *
 * @package CSSCable
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $user ) || ! $user instanceof WP_User ) {
	$user = wp_get_current_user();
}

$first_name   = (string) $user->first_name;
$last_name    = (string) $user->last_name;
$display_name = (string) $user->display_name;
$email        = (string) $user->user_email;

$initials = '';

if ( $first_name ) {
	$initials .= mb_strtoupper( mb_substr( $first_name, 0, 1 ) );
}

if ( $last_name ) {
	$initials .= mb_strtoupper( mb_substr( $last_name, 0, 1 ) );
}

if ( ! $initials ) {
	$initials = mb_strtoupper(
		mb_substr(
			$display_name ?: $email,
			0,
			1
		)
	);
}

$company = (string) get_user_meta(
	$user->ID,
	'_cc_company_name',
	true
);

$contract = (string) get_user_meta(
	$user->ID,
	'_cc_contract_number',
	true
);
?>

<div class="cc-account-edit">
	<header class="cc-account-edit__header">
		<div>
			<span class="cc-account-edit__eyebrow">
				Личный кабинет
			</span>

			<h1>Настройки профиля</h1>

			<p>
				Управляйте личными данными и безопасностью аккаунта.
			</p>
		</div>

		<a
			class="cc-account-edit__back"
			href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>"
		>
			<span aria-hidden="true">←</span>
			Вернуться в кабинет
		</a>
	</header>

	<div class="cc-account-edit__layout">
		<aside class="cc-account-summary">
			<div class="cc-account-summary__avatar" aria-hidden="true">
				<?php echo esc_html( $initials ); ?>
			</div>

			<div class="cc-account-summary__identity">
				<strong>
					<?php echo esc_html( $display_name ?: $email ); ?>
				</strong>

				<span>
					<?php echo esc_html( $email ); ?>
				</span>
			</div>

			<div class="cc-account-summary__divider"></div>

			<dl class="cc-account-summary__details">
				<?php if ( $company ) : ?>
					<div>
						<dt>Компания</dt>
						<dd><?php echo esc_html( $company ); ?></dd>
					</div>
				<?php endif; ?>

				<?php if ( $contract ) : ?>
					<div>
						<dt>Договор</dt>
						<dd><?php echo esc_html( $contract ); ?></dd>
					</div>
				<?php endif; ?>

				<div>
					<dt>Статус</dt>
					<dd>
						<span class="cc-account-summary__status">
							Активен
						</span>
					</dd>
				</div>
			</dl>

			<p class="cc-account-summary__note">
				Данные компании и договора изменяются через вашего менеджера.
			</p>
		</aside>

		<form
			class="cc-account-form woocommerce-EditAccountForm edit-account"
			action=""
			method="post"
		>
			<section class="cc-account-card">
				<header class="cc-account-card__header">
					<div class="cc-account-card__icon" aria-hidden="true">
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="1.8"
							stroke-linecap="round"
							stroke-linejoin="round"
						>
							<circle cx="12" cy="8" r="4"></circle>
							<path d="M4 21a8 8 0 0 1 16 0"></path>
						</svg>
					</div>

					<div>
						<h2>Личные данные</h2>

						<p>
							Информация, которая используется в вашем аккаунте.
						</p>
					</div>
				</header>

				<div class="cc-account-card__body">
					<div class="cc-account-form__grid">
						<div class="cc-account-field">
							<label for="account_first_name">
								Имя
								<span aria-hidden="true">*</span>
							</label>

							<input
								id="account_first_name"
								type="text"
								name="account_first_name"
								autocomplete="given-name"
								value="<?php echo esc_attr( $first_name ); ?>"
								required
							>
						</div>

						<div class="cc-account-field">
							<label for="account_last_name">
								Фамилия
								<span aria-hidden="true">*</span>
							</label>

							<input
								id="account_last_name"
								type="text"
								name="account_last_name"
								autocomplete="family-name"
								value="<?php echo esc_attr( $last_name ); ?>"
								required
							>
						</div>
					</div>

					<div class="cc-account-field">
						<label for="account_display_name">
							Отображаемое имя
							<span aria-hidden="true">*</span>
						</label>

						<input
							id="account_display_name"
							type="text"
							name="account_display_name"
							value="<?php echo esc_attr( $display_name ); ?>"
							required
						>

						<small>
							Так ваше имя будет отображаться в личном кабинете.
						</small>
					</div>

					<div class="cc-account-field">
						<label for="account_email">
							Email
							<span aria-hidden="true">*</span>
						</label>

						<input
							id="account_email"
							type="email"
							name="account_email"
							autocomplete="email"
							value="<?php echo esc_attr( $email ); ?>"
							required
						>
					</div>
				</div>
			</section>

			<section class="cc-account-card">
				<header class="cc-account-card__header">
					<div class="cc-account-card__icon" aria-hidden="true">
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="1.8"
							stroke-linecap="round"
							stroke-linejoin="round"
						>
							<rect x="5" y="10" width="14" height="11" rx="2"></rect>
							<path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
						</svg>
					</div>

					<div>
						<h2>Безопасность</h2>

						<p>
							Оставьте поля пустыми, если не хотите менять пароль.
						</p>
					</div>
				</header>

				<div class="cc-account-card__body">
					<div class="cc-account-field">
						<label for="password_current">
							Текущий пароль
						</label>

						<div class="cc-account-password">
							<input
								id="password_current"
								type="password"
								name="password_current"
								autocomplete="current-password"
							>

							<button
								class="cc-account-password__toggle"
								type="button"
								data-password-toggle="password_current"
								aria-label="Показать пароль"
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
									<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12"></path>
									<circle cx="12" cy="12" r="2.5"></circle>
								</svg>
							</button>
						</div>
					</div>

					<div class="cc-account-form__grid">
						<div class="cc-account-field">
							<label for="password_1">
								Новый пароль
							</label>

							<div class="cc-account-password">
								<input
									id="password_1"
									type="password"
									name="password_1"
									autocomplete="new-password"
								>

								<button
									class="cc-account-password__toggle"
									type="button"
									data-password-toggle="password_1"
									aria-label="Показать пароль"
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
										<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12"></path>
										<circle cx="12" cy="12" r="2.5"></circle>
									</svg>
								</button>
							</div>
						</div>

						<div class="cc-account-field">
							<label for="password_2">
								Повторите новый пароль
							</label>

							<div class="cc-account-password">
								<input
									id="password_2"
									type="password"
									name="password_2"
									autocomplete="new-password"
								>

								<button
									class="cc-account-password__toggle"
									type="button"
									data-password-toggle="password_2"
									aria-label="Показать пароль"
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
										<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12"></path>
										<circle cx="12" cy="12" r="2.5"></circle>
									</svg>
								</button>
							</div>
						</div>
					</div>
				</div>
			</section>

			<footer class="cc-account-form__footer">
				<a
					class="cc-account-button cc-account-button--secondary"
					href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>"
				>
					Отмена
				</a>

				<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>

				<button
					class="cc-account-button cc-account-button--primary"
					type="submit"
					name="save_account_details"
					value="Сохранить изменения"
				>
					Сохранить изменения
				</button>

				<input
					type="hidden"
					name="action"
					value="save_account_details"
				>
			</footer>
		</form>
	</div>
</div>
