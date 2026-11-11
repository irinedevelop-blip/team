document.addEventListener('DOMContentLoaded', () => {
	const product = document.querySelector('.cc-sku-product');

	if (!product) {
		return;
	}

	const quantityInput = product.querySelector('.qty');
	const minusButton = product.querySelector('[data-quantity-minus]');
	const plusButton = product.querySelector('[data-quantity-plus]');

	const price = Number.parseFloat(product.dataset.price || '0');
	const basePrice = Number.parseFloat(product.dataset.basePrice || '0');
	const cashbackPercent = Number.parseFloat(product.dataset.cashback || '0');

	const totalOutput = product.querySelector('[data-sku-total]');
	const savingOutput = product.querySelector('[data-sku-saving]');
	const bonusOutput = product.querySelector('[data-sku-bonus]');
	const quantityOutput = product.querySelector('[data-sku-quantity-output]');

	const moneyFormatter = new Intl.NumberFormat('ru-RU', {
		style: 'currency',
		currency: 'KZT',
		minimumFractionDigits: 0,
		maximumFractionDigits: 2,
	});

	const numberFormatter = new Intl.NumberFormat('ru-RU', {
		maximumFractionDigits: 3,
	});

	const getStep = () => {
		if (!quantityInput) {
			return 1;
		}

		return Number.parseFloat(quantityInput.step || '1') || 1;
	};

	const getMinimum = () => {
		if (!quantityInput) {
			return getStep();
		}

		return Number.parseFloat(quantityInput.min || String(getStep())) || getStep();
	};

	const getMaximum = () => {
		if (!quantityInput || !quantityInput.max) {
			return Number.POSITIVE_INFINITY;
		}

		const maximum = Number.parseFloat(quantityInput.max);

		return Number.isFinite(maximum)
			? maximum
			: Number.POSITIVE_INFINITY;
	};

	const normalizeQuantity = (value) => {
		const step = getStep();
		const minimum = getMinimum();
		const maximum = getMaximum();

		let quantity = Number.parseFloat(value);

		if (!Number.isFinite(quantity)) {
			quantity = minimum;
		}

		quantity = Math.max(minimum, Math.min(maximum, quantity));

		const steps = Math.round((quantity - minimum) / step);
		quantity = minimum + steps * step;

		return Number(quantity.toFixed(3));
	};

	const updateCalculation = () => {
		if (!quantityInput) {
			return;
		}

		const quantity = normalizeQuantity(quantityInput.value);

		quantityInput.value = quantity;

		if (quantityOutput) {
			quantityOutput.textContent = numberFormatter.format(quantity);
		}

		if (totalOutput) {
			totalOutput.textContent = moneyFormatter.format(price * quantity);
		}

		if (savingOutput) {
			const saving = Math.max(0, basePrice - price) * quantity;
			savingOutput.textContent = moneyFormatter.format(saving);
		}

		if (bonusOutput) {
			const bonus = price * quantity * (cashbackPercent / 100);
			bonusOutput.textContent = moneyFormatter.format(bonus);
		}
	};

	if (minusButton && quantityInput) {
		minusButton.addEventListener('click', () => {
			quantityInput.value = normalizeQuantity(
				Number.parseFloat(quantityInput.value || '0') - getStep()
			);

			updateCalculation();
		});
	}

	if (plusButton && quantityInput) {
		plusButton.addEventListener('click', () => {
			quantityInput.value = normalizeQuantity(
				Number.parseFloat(quantityInput.value || '0') + getStep()
			);

			updateCalculation();
		});
	}

	if (quantityInput) {
		quantityInput.addEventListener('input', updateCalculation);
		quantityInput.addEventListener('change', updateCalculation);
		updateCalculation();
	}

	product.querySelectorAll('[data-product-image]').forEach((thumbnail) => {
		thumbnail.addEventListener('click', () => {
			const mainImage = document.querySelector('#cc-sku-main-image');
			const imageUrl = thumbnail.dataset.productImage;

			if (!mainImage || !imageUrl) {
				return;
			}

			mainImage.src = imageUrl;

			product.querySelectorAll('[data-product-image]').forEach((item) => {
				item.classList.remove('is-active');
			});

			thumbnail.classList.add('is-active');
		});
	});

	const tabButtons = product.querySelectorAll('[data-sku-tab]');
	const tabPanels = product.querySelectorAll('[data-sku-panel]');

	tabButtons.forEach((button) => {
		button.addEventListener('click', () => {
			const tabName = button.dataset.skuTab;

			tabButtons.forEach((item) => {
				item.classList.toggle('is-active', item === button);
			});

			tabPanels.forEach((panel) => {
				const active = panel.dataset.skuPanel === tabName;

				panel.classList.toggle('is-active', active);
				panel.hidden = !active;
			});
		});
	});
});
