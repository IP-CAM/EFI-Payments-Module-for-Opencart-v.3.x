class CardPaymentToken {
	constructor(config) {
		this.config = config;
		this.account = config.accountCode;
		this.environment = config.environment ? 'sandbox' : 'production';
		this.reuse = false;
		this.formId = 'efi-card-form';

		this.fields = this.extractFields();



	}

	extractFields() {
		const getValue = (id) => document.getElementById(id)?.value || '';
		const [month, yearSuffix] = getValue(this.config.cardExpirationInputId).split('/');
		const clean = (value) => value.replace(/\D/g, '');

		return {
			number: clean(getValue(this.config.cardNumberInputId)),
			cvv: getValue(this.config.cardCvvInputId),
			expirationMonth: month,
			expirationYear: `20${yearSuffix}`,
			holderName: getValue(this.config.customerNameInputId),
			holderDocument: clean(getValue(this.config.customerDocumentInputId))
		};
	}

	async generateToken() {
		try {
			const brand = await this.identifyBrand();

			const result = await EfiPay.CreditCard
				.setAccount(this.account)
				.setEnvironment(this.environment)
				.setCreditCardData({
					brand: brand,
					number: this.fields.number,
					cvv: this.fields.cvv,
					expirationMonth: this.fields.expirationMonth,
					expirationYear: this.fields.expirationYear,
					holderName: this.fields.holderName,
					holderDocument: this.fields.holderDocument,
					reuse: this.reuse
				})
				.getPaymentToken();


			let responseSuccess = this.insertTokenHiddenInput(result.payment_token);

			return responseSuccess;
		} catch (error) {
			console.error("Erro ao gerar token de pagamento:", error);
			throw error;
		}
	}

	insertTokenHiddenInput(token) {
		const input = document.getElementById('payment_efi_customer_card_payment_token');

		if (!input) {
			console.error('Input hidden para token não encontrado.');
			return false;
		}

		// Aguarda explicitamente até garantir que o valor foi inserido no DOM
		input.value = token;

		// Força o DOM a processar o valor inserido antes de continuar
		return new Promise((resolve) => {
			requestAnimationFrame(() => {
				this.clearCardFields();
				resolve(true);
			});
		});
	}


	clearCardFields() {
		const ids = [
			this.config.cardNumberInputId,
			this.config.cardCvvInputId,
			this.config.cardExpirationInputId,
		];

		ids.forEach(id => {
			const el = document.getElementById(id);
			if (el) el.value = '';
		});
	}

	async identifyBrand() {
		try {
			const brand = await EfiPay.CreditCard
				.setCardNumber(this.fields.number)
				.verifyCardBrand();

			return brand;
		} catch (error) {
			console.error("Erro ao identificar a bandeira:", error);
			throw error;
		}
	}
}
