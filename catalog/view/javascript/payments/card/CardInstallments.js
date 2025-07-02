class CardInstallments {
	constructor(config) {

		this.accountCode = config.accountCode; // Identificador da conta
		this.environment = config.environment ? 'sandbox' : 'production'; // 'sandbox' ou 'production'

		// IDs dos campos (defina conforme necessário)
		this.cardNumberInputId = config.cardNumberInputId;
		this.amountInputId = config.amountInputId;
		this.installmentsSelectId = config.installmentsSelectId;
		this.total = config.total;

		this.initListeners();
	}

	initListeners() {
		const cardInput = document.getElementById(this.cardNumberInputId);

		if (cardInput) {
			cardInput.addEventListener('blur', () => {
				const cardNumber = cardInput.value.replace(/\D/g, '');
				if (cardNumber.length >= 6) {
					this.identifyBrand(cardNumber);
				}
			});
		}
	}

	async identifyBrand(cardNumber) {
		try {
			const brand = await EfiPay.CreditCard
				.setCardNumber(cardNumber)
				.verifyCardBrand();


			this.loadInstallments(brand);
		} catch (error) {
			console.error("Erro ao identificar a bandeira:", error.error_description || error.message);
		}
	}

	async loadInstallments(brand) {
		try {
			const total = Math.round(parseFloat(this.total) * 100);




			if (!total || isNaN(total)) {
				console.warn("Valor inválido para cálculo de parcelas.");
				return;
			}

			const installments = await EfiPay.CreditCard
				.setAccount(this.accountCode)
				.setEnvironment(this.environment)
				.setBrand(brand)
				.setTotal(total)
				.getInstallments();


			const select = document.getElementById(this.installmentsSelectId);
			select.innerHTML = '';

			installments.installments.forEach(parcela => {
				const option = document.createElement('option');
				option.value = parcela.installment;
				option.text = `${parcela.installment}x de R$ ${parcela.currency} ${parcela.has_interest ? 'com juros' : 'sem juros'}`;
				select.appendChild(option);
			});
		} catch (error) {
			console.error("Erro ao carregar parcelas:", error.error_description || error.message);
		}
	}
}
