<?php

namespace Opencart\Catalog\Model\Extension\Efi\Payment\Openfinance;

require_once DIR_EXTENSION . 'efi/library/vendor/autoload.php';


use Opencart\Extension\Efi\Library\EfiConfigHelper;
use Efi\EfiPay;
use Exception;
use Opencart\System\Library\Language;

class EfiOpenFinanceInputs extends \Opencart\System\Engine\Model
{
    /**
     * Retorna os campos de entrada formatados para pagamento via Open Finance.
     *
     * @param Language $language Objeto de linguagem para carregar os labels.
     * @param array $settings Configurações do módulo.
     * @return array Campos formatados.
     */
    public function getEntryFormatted(Language $language, array $settings): array
    {
        $inputs = [
            [
                'name' => 'payment_efi_open_finance_customer_cpf',
                'required' => true,
                'label' => $language->get('text_label_customer_cpf'),
                'type' => 'text',
                'data-mask' => 'documento'
            ],
            [
                'name' => 'payment_efi_open_finance_customer_cnpj',
                'required' => false,
                'label' => $language->get('text_label_customer_cnpj'),
                'type' => 'text',
                'data-mask' => 'documento'
            ],
            [
                'name' => 'payment_efi_open_finance_customer_bank',
                'required' => true,
                'label' => $language->get('text_label_customer_bank'),
                'type' => 'select',
                'data-mask' => '',
                'options' => [
                    [
                        'label' => 'Escolha o banco para pagamento',
                        'value' => ''
                    ]
                ]
            ],
        ];

        return $this->addBanksToOptions($inputs, $settings);
    }

    /**
     * Consulta os participantes do Open Finance via API e adiciona como opções no campo de banco.
     *
     * @param array $inputs Campos de entrada atuais.
     * @param array $settings Configurações do módulo.
     * @return array Campos atualizados.
     */
    private function addBanksToOptions(array $inputs, array $settings): array
    {
        try {
            $config = EfiConfigHelper::getEfiConfig($settings);
            $api = new EfiPay($config);

            $response = $api->ofListParticipants();

            if (isset($response['participantes']) && is_array($response['participantes'])) {
                foreach ($response['participantes'] as $participant) {
                    $inputs[2]['options'][] = [
                        'label' => $participant['nome'],
                        'value' => $participant['identificador']
                    ];
                }
            }
        } catch (Exception $e) {
            $this->log->write('Erro ao carregar bancos do Open Finance: ' . $e->getMessage());
        }

        return $inputs;
    }
}
