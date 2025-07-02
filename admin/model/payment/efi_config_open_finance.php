<?php

namespace Opencart\Admin\Model\Extension\Efi\Payment;

use Opencart\System\Library\Language;

class EfiConfigOpenFinance extends \Opencart\System\Engine\Model
{
    /**
     * Retorna os campos de entrada formatados para configurações do Open Finance.
     *
     * @param Language $language Objeto de linguagem para carregar os labels.
     * @return array Dados formatados contendo os campos de entrada.
     */
    public function getEntryFormatted(Language $language): array
    {
        return [
            'name' => 'Open Finance',
            'icon' => 'fa-solid fa-money-bill-transfer',
            'inputs' => [
                [
                    'name'     => 'payment_efi_open_finance_key',
                    'required' => true,
                    'label'    => $language->get('entry_open_finance_key'),
                    'tooltip'  => $language->get('tooltip_open_finance_key'),
                    'type'     => 'text',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_open_finance_discount',
                    'required' => false,
                    'label'    => $language->get('entry_open_finance_discount'),
                    'tooltip'  => $language->get('tooltip_open_finance_discount'),
                    'type'     => 'text',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_open_finance_certificate',
                    'required' => true,
                    'label'    => $language->get('entry_open_finance_certificate'),
                    'tooltip'  => $language->get('tooltip_open_finance_certificate'),
                    'type'     => 'file',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_open_finance_status',
                    'required' => false,
                    'label'    => $language->get('entry_open_finance_status'),
                    'tooltip'  => '', // Não exibe tooltip nesse campo
                    'type'     => 'checkbox',
                    'value'    => ''
                ],
            ]
        ];
    }
}
