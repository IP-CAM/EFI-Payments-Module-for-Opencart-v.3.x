class CardFormHandler {
    constructor(config) {
        this.formId = 'efi-card-form';
        this.buttonId = 'button-confirm';
        const languageParam = typeof efiLanguage !== 'undefined' ? `&language=${efiLanguage}` : '';
        this.endpoint = `index.php?route=extension/efi/payment/efi_card.confirm${languageParam}`;
        this.config = config;

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'payment_efi_customer_card_payment_token';
        input.id = 'payment_efi_customer_card_payment_token';
        const form = document.getElementById(this.formId);
        form.appendChild(input);

        const checkExistence = (resolve, reject) => {
            let attempts = 0;
            const maxAttempts = 50;

            const interval = setInterval(() => {
                this.form = document.getElementById(this.formId);
                this.button = document.getElementById(this.buttonId);

                if (this.form && this.button) {
                    clearInterval(interval);
                    resolve(true);
                }

                attempts++;
                if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    reject();
                }
            }, 200);
        };

        new Promise(checkExistence)
            .then(() => this.init())
            .catch((error) => {
                console.log(error);
                console.error(`Erro: Formulário (${this.formId}) ou botão (${this.buttonId}) não encontrados após 10 segundos.`);
            });
    }

    init() {
        new MaskHandler();
        new CardInstallments(this.config);
        this.button.addEventListener('click', (e) => {
            e.preventDefault();
            this.disableButton(true);
            this.handleFormSubmission();
        });
    }

    async handleFormSubmission() {
        const cardPaymentToken = new CardPaymentToken(this.config);

        if (!CommonValidations.validate()) {
            this.displayAlert('danger', 'Por favor, preencha corretamente todos os campos obrigatórios.');
            this.disableButton(false);
            return;
        }

        const tokenReady = await cardPaymentToken.generateToken();

        if (tokenReady) {
            this.submitForm();
        }
    }

    async submitForm() {
        if (!this.form || !this.button) return;

        let formData = new FormData(this.form);

        try {
            let response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            let jsonResponse = await response.json();

            if (jsonResponse.success) {
                this.displayAlert('success', jsonResponse.message);
                window.location.href = jsonResponse.redirect;
            } else {
                document.getElementById('payment_efi_customer_card_payment_token').value = '';
                this.displayAlert('danger', jsonResponse.message);
            }

        } catch (error) {
            console.error('Erro ao enviar formulário:', error);
            this.displayAlert('danger', 'Erro ao processar o pagamento.');
        } finally {
            this.disableButton(false);
        }
    }

    disableButton(disable) {
        if (disable) {
            this.button.setAttribute('disabled', true);
            this.button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processando...`;
        } else {
            this.button.removeAttribute('disabled');
            this.button.innerHTML = 'Pagar';
        }
    }

    displayAlert(type, message) {
        let alertDiv = document.createElement('div');
        alertDiv.classList.add('alert', `alert-${type}`, 'alert-dismissible');
        alertDiv.innerHTML = `
            <i class="fa-solid fa-circle-exclamation"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        let alertContainer = document.getElementById('alert') || document.body;
        alertContainer.prepend(alertDiv);
    }
}
