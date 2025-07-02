<?php

namespace Opencart\Admin\Model\Extension\Efi\Payment;

use Opencart\System\Library\Language;

class EfiConfigBillet extends \Opencart\System\Engine\Model
{
    /**
     * Retorna os campos de entrada formatados para configurações do Boleto.
     *
     * @param Language $language Objeto de linguagem para carregar os rótulos.
     * @return array Dados formatados contendo os campos de entrada.
     */
    public function getEntryFormatted(Language $language): array
    {
        return [
            'name' => 'Boleto',
            'icon' => 'fa-solid fa-file-invoice',
            'inputs' => [
                [
                    'name'     => 'payment_efi_billet_expire_at',
                    'required' => true,
                    'label'    => $language->get('entry_billet_expire_at'),
                    'tooltip'  => $language->get('tooltip_billet_expire_at'),
                    'type'     => 'number',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_billet_discount',
                    'required' => false,
                    'label'    => $language->get('entry_billet_discount'),
                    'tooltip'  => $language->get('tooltip_billet_discount'),
                    'type'     => 'text',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_billet_fine',
                    'required' => false,
                    'label'    => $language->get('entry_billet_fine'),
                    'tooltip'  => $language->get('tooltip_billet_fine'),
                    'type'     => 'number',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_billet_interest',
                    'required' => false,
                    'label'    => $language->get('entry_billet_interest'),
                    'tooltip'  => $language->get('tooltip_billet_interest'),
                    'type'     => 'number',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_billet_message',
                    'required' => false,
                    'label'    => $language->get('entry_billet_message'),
                    'tooltip'  => $language->get('tooltip_billet_message'),
                    'type'     => 'text',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_billet_email',
                    'required' => false,
                    'label'    => $language->get('entry_billet_email'),
                    'tooltip'  => $language->get('tooltip_billet_email'),
                    'type'     => 'checkbox',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_billet_status',
                    'required' => false,
                    'label'    => $language->get('entry_billet_status'),
                    'tooltip'  => '',
                    'type'     => 'checkbox',
                    'value'    => ''
                ],
            ]
        ];
    }
}
