class MaskHandler {
    constructor() {
        this.initMasks();
    }

    initMasks() {
        document.querySelectorAll('[data-mask]').forEach(input => {
            const maskType = input.getAttribute('data-mask');
            this.applyMask(input, maskType);
        });
    }

    applyMask(element, type) {
        let maskOptions;

        switch (type) {
            case 'nome':
                maskOptions = { mask: /^[A-Za-zÀ-ÖØ-öø-ÿ\s]*$/ };
                return;

            case 'telefone':
                maskOptions = { mask: '(00) 00000-0000' };
                element.placeholder = '(99) 99999-9999';

                break;

            case 'cep':
                maskOptions = { mask: '00000-000' };
                break;

            case 'data-nascimento':
                maskOptions = { mask: '00/00/0000' };
                break;

            case 'cartao':
                maskOptions = { mask: '0000 0000 0000 0000' };
                element.placeholder = '0000 0000 0000 0000';
                break;

            case 'cartao-vencimento':
                maskOptions = { mask: '00/00' }; // Formato MM/AA
                element.placeholder = 'MM/AA';
                break;

            case 'cartao-cvv':
                maskOptions = { mask: '000[0]' }; // 3 ou 4 dígitos
                element.placeholder = 'CVV';
                break;

            case 'documento':
                maskOptions = {
                    mask: [
                        { mask: '000.000.000-00' }, // CPF
                        { mask: '00.000.000/0000-00' } // CNPJ
                    ]
                };
                break;

            case 'email':
                maskOptions = { mask: /^[\s\S]*$/ };
                break;

            default:
                console.warn(`Máscara não reconhecida: ${type}`);
                return;
        }

        IMask(element, maskOptions);
    }
}
