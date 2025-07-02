<?php

namespace Opencart\Admin\Model\Extension\Efi\Payment;

use Opencart\System\Library\Language;
use Opencart\System\Library\Log;

class EfiConfigRequired extends \Opencart\System\Engine\Model
{
    /**
     * Retorna os campos de entrada formatados para configurações obrigatórias.
     *
     * @param Language $language Objeto de linguagem para carregar os rótulos.
     * @return array Dados formatados contendo os campos de entrada.
     */
    public function getEntryFormatted(Language $language): array
    {
        $statusOptions = $this->getOrderStatuses();

        return [
            'name' => 'Configurações gerais',
            'icon' => 'fa-solid fa-gear',
            'inputs' => [
                [
                    'name'     => 'payment_efi_client_id_production',
                    'required' => true,
                    'label'    => $language->get('entry_required_client_id_production'),
                    'tooltip'  => $language->get('tooltip_required_client_id_production'),
                    'type'     => 'text',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_client_secret_production',
                    'required' => true,
                    'label'    => $language->get('entry_required_client_secret_production'),
                    'tooltip'  => $language->get('tooltip_required_client_secret_production'),
                    'type'     => 'text',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_client_id_sandbox',
                    'required' => true,
                    'label'    => $language->get('entry_required_client_id_sandbox'),
                    'tooltip'  => $language->get('tooltip_required_client_id_sandbox'),
                    'type'     => 'text',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_client_secret_sandbox',
                    'required' => true,
                    'label'    => $language->get('entry_required_client_secret_sandbox'),
                    'tooltip'  => $language->get('tooltip_required_client_secret_sandbox'),
                    'type'     => 'text',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_account_code',
                    'required' => true,
                    'label'    => $language->get('entry_required_account_code'),
                    'tooltip'  => $language->get('tooltip_required_account_code'),
                    'type'     => 'text',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_sort_order',
                    'required' => true,
                    'label'    => $language->get('entry_required_sort_order'),
                    'tooltip'  => $language->get('tooltip_required_sort_order'),
                    'type'     => 'number',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_order_status_paid',
                    'required' => true,
                    'label'    => $language->get('entry_required_status_order_status'),
                    'tooltip'  => $language->get('tooltip_required_status_order_status'),
                    'type'     => 'select',
                    'value'    => '',
                    'options'  => $statusOptions
                ],
                [
                    'name'     => 'payment_efi_enviroment',
                    'required' => false,
                    'label'    => $language->get('entry_required_enviroment'),
                    'tooltip'  => '', // sem tooltip definido
                    'type'     => 'checkbox',
                    'value'    => ''
                ],
                [
                    'name'     => 'payment_efi_status',
                    'required' => false,
                    'label'    => $language->get('entry_required_status'),
                    'tooltip'  => '', // sem tooltip definido
                    'type'     => 'checkbox',
                    'value'    => ''
                ]
            ]
        ];
    }

    /**
     * Recupera os status de pedido disponíveis no sistema para preencher o campo Status do pedido ao finalizar o pagamento.
     *
     * @return array
     */
    private function getOrderStatuses(): array
    {
        $options = [];

        try {
            $this->load->model('localisation/order_status');
            $results = $this->model_localisation_order_status->getOrderStatuses();

            foreach ($results as $status) {
                $options[] = [
                    'value' => $status['order_status_id'],
                    'label' => $status['name']
                ];
            }
        } catch (\Exception $e) {
            $log = new Log('efi.log');
            $log->write('Erro ao carregar os status de pedido: ' . $e->getMessage());
        }

        return $options;
    }
}
