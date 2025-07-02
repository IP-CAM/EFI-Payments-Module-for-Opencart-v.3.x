class PixFormHandler {
    constructor() {
        this.formId = 'efi-pix-form';
        this.buttonId = 'button-confirm';

        const languageParam = typeof efiLanguage !== 'undefined' ? `&language=${efiLanguage}` : '';
        this.endpoint = `index.php?route=extension/efi/payment/efi_pix.confirm${languageParam}`;

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
            .catch(() => {
                console.error(`Erro: Formulário (${this.formId}) ou botão (${this.buttonId}) não encontrados após 10 segundos.`);
            });
    }

    init() {
        new MaskHandler();
        this.button.addEventListener('click', (e) => {
            e.preventDefault();
            this.handleFormSubmission();
        });
    }

    handleFormSubmission() {
        if (!CommonValidations.validate()) {
            this.displayAlert('danger', 'Por favor, preencha corretamente todos os campos obrigatórios.');
            return;
        }

        this.submitForm();
    }

    async submitForm() {
        if (!this.form || !this.button) return;

        let formData = new FormData(this.form);
        this.disableButton(true);

        try {
            let response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            let htmlResponse = await response.text();

            if (!htmlResponse.trim()) {
                this.displayAlert('danger', 'Erro ao gerar o QR Code. Resposta vazia do servidor.');
                return;
            }

            this.appendResponseToPage(htmlResponse);
        } catch (error) {
            console.error('Erro ao enviar formulário:', error);
            this.displayAlert('danger', 'Erro ao processar o pagamento.');
        } finally {
            this.disableButton(false);
        }
    }

    appendResponseToPage(html) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;

        const existingModal = document.getElementById('pix-payment-modal');
        if (existingModal) {
            existingModal.remove();
        }

        document.body.appendChild(wrapper);

        wrapper.querySelectorAll("script").forEach(oldScript => {
            const newScript = document.createElement("script");
            if (oldScript.src) {
                newScript.src = oldScript.src;
                newScript.defer = true;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            document.body.appendChild(newScript);
        });

        const modalElement = document.getElementById('pix-payment-modal');
        if (modalElement) {
            let pixModal = new bootstrap.Modal(modalElement, {
                keyboard: false,
                backdrop: 'static'
            });
            pixModal.show();
        }
    }

    disableButton(disable) {
        if (disable) {
            this.button.setAttribute('disabled', true);
            this.button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Carregando...`;
        } else {
            this.button.removeAttribute('disabled');
            this.button.innerHTML = 'Gerar QrCode';
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
