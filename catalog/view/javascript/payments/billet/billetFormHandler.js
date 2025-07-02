class BilletFormHandler {
    constructor() {
        this.formId = 'efi-billet-form';
        this.buttonId = 'button-confirm';

        const languageParam = typeof efiLanguage !== 'undefined' ? `&language=${efiLanguage}` : '';
        this.endpoint = `index.php?route=extension/efi/payment/efi_billet.confirm${languageParam}`;

        const checkExistence = (resolve, reject) => {
            let attempts = 0;
            const maxAttempts = 50; // 10s / 200ms

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
                this.displayAlert('danger', 'Erro ao gerar Boleto. Resposta vazia do servidor.');
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



        // Insere a resposta no DOM
        document.body.appendChild(wrapper);

        // Encontra e executa scripts embutidos no HTML carregado
        wrapper.querySelectorAll("script").forEach(oldScript => {
            const newScript = document.createElement("script");
            if (oldScript.src) {
                // Se for um script externo, recarrega corretamente
                newScript.src = oldScript.src;
                newScript.defer = true;
            } else {
                // Se for um script embutido, recria e executa manualmente
                newScript.textContent = oldScript.textContent;
            }
            document.body.appendChild(newScript);
        });

    }


    executeInlineScripts(wrapper) {
        // Seleciona todos os scripts embutidos no HTML injetado
        let scripts = wrapper.querySelectorAll("script");

        scripts.forEach(script => {
            console.log(script);
            let newScript = document.createElement("script");
            newScript.textContent = script.textContent; // Reexecuta o conteúdo do script

            document.body.appendChild(newScript); // Adiciona ao DOM para ser executado
            script.remove(); // Remove o script antigo
        });
    }

    disableButton(disable) {
        if (disable) {
            this.button.setAttribute('disabled', true);
            this.button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Carregando...`;
        } else {
            this.button.removeAttribute('disabled');
            this.button.innerHTML = 'Gerar';
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


