<?php

namespace Opencart\Catalog\Model\Extension\Efi\Payment\Billet;

use Opencart\System\Library\Language;

class EfiBilletInputs extends \Opencart\System\Engine\Model
{
    /**
     * Retorna os campos de entrada formatados para configurações Pix.
     *
     * @param Language $language Objeto de linguagem para carregar os rótulos.
     * @return array Dados formatados contendo os campos de entrada.
     */
    public function getEntryFormatted(Language $language): array
    {
        // Tenta recuperar dados da sessão do cliente
        $customerName  = '';
        $customerEmail = '';

        if ($this->customer->isLogged()) {
            $customerName  = trim($this->customer->getFirstName() . ' ' . $this->customer->getLastName());
            $customerEmail = $this->customer->getEmail();
        }

        return [
            [
                'name'       => 'payment_efi_customer_name',
                'required'   => true,
                'label'      => $language->get('text_label_customer_name'),
                'type'       => 'text',
                'data-mask'  => 'nome',
                'value'      => $customerName
            ],
            [
                'name'       => 'payment_efi_customer_document',
                'required'   => true,
                'label'      => $language->get('text_label_customer_document'),
                'type'       => 'text',
                'data-mask'  => 'documento'
            ],
            [
                'name'       => 'payment_efi_customer_email',
                'required'   => true,
                'label'      => $language->get('text_label_customer_email'),
                'type'       => 'text',
                'data-mask'  => 'email',
                'value'      => $customerEmail
            ],
        ];
    }
}
