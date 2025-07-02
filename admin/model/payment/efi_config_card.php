<?php

namespace Opencart\Admin\Model\Extension\Efi\Payment;

use Opencart\System\Library\Language;

class EfiConfigCard extends \Opencart\System\Engine\Model
{
    /**
     * Retorna os campos de entrada formatados para configurações do Cartão de crédito.
     *
     * @param Language $language Objeto de linguagem para carregar os rótulos.
     * @return array Dados formatados contendo os campos de entrada.
     */
    public function getEntryFormatted(Language $language): array
    {
        return [
            'name' => 'Cartão de Crédito',
            'icon' => 'fa-solid fa-credit-card',
            'inputs' => [
                [
                    'name' => 'payment_efi_card_status',
                    'required' => false,
                    'label' => $language->get('entry_credit_card_status'),
                    'type' => 'checkbox',
                    'value' => ''
                ],
            ]
        ];
    }
}
