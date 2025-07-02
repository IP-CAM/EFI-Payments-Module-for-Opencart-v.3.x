<?php

namespace Opencart\Catalog\Model\Extension\Efi\Payment\Card;

require_once DIR_EXTENSION . 'efi/library/vendor/autoload.php';

use Opencart\Extension\Efi\Library\EfiConfigHelper;
use Opencart\Extension\Efi\Library\EfiShippingHelper;
use Efi\EfiPay;
use Exception;

class EfiCard extends \Opencart\System\Engine\Model
{
    /**
     * Gera uma cobrança via cartão de crédito utilizando a API do EfiPay.
     *
     * @param array  $customer  Dados do cliente (nome, documento, email, telefone).
     * @param array  $card      Dados do cartão (installments, token).
     * @param float  $amount    Valor da cobrança.
     * @param string $order_id  ID do pedido.
     * @param array  $settings  Configurações da conta EfiPay.
     * @param array  $order_info Informações completas do pedido (necessário para frete).
     * @return array Resultado da operação.
     */
    public function generateCardCharge(array $customer, array $card, float $amount, string $order_id, array $settings, array $order_info): array
    {
        try {
            $options = EfiConfigHelper::getEfiConfig($settings);

            $customer_data = $this->getFormattedCustomer($customer);

            $body = [
                'items' => [
                    [
                        'name' => "Pedido #{$order_id}",
                        'value' => intval($amount * 100),
                        'amount' => 1
                    ]
                ],
                'payment' => [
                    'credit_card' => [
                        'installments'   => (int) $card['installments'],
                        'payment_token'  => $card['token'],
                        'customer'       => $customer_data
                    ]
                ]
            ];

            // Adiciona o frete usando a helper
            $shippings = EfiShippingHelper::getShippingsFromOrder($order_info);
            $this->logError('SHIPPING: ' . json_encode($shippings));

            if (!empty($shippings)) {
                $body['shippings'] = $shippings;
            }

            $efiPay = new EfiPay($options);
            $charge = $efiPay->createOneStepCharge([], $body);
            $chargeData = $charge['data'];

            if ($chargeData['status'] == 'approved') {
                return [
                    'success'   => true,
                    'charge_id' => $chargeData['charge_id'] ?? null,
                    'status'    => $chargeData['status'] ?? null,
                    'message'   => 'Pagamento confirmado via cartão de crédito.'
                ];
            } else {
                return [
                    'success'   => true,
                    'charge_id' => $chargeData['charge_id'] ?? null,
                    'status'    => $chargeData['status'] ?? null,
                    'message'   => 'Não foi possível processar o pagamento. Verifique os dados do cartão e tente novamente.'
                ];
            }
        } catch (Exception $e) {
            $this->logError("Erro na geração da cobrança via cartão: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'Erro ao gerar cobrança via cartão.'
            ];
        }
    }

    /**
     * Retentativa de pagamento via cartão de crédito usando a API do EfiPay.
     *
     * @param string $charge_id      ID da cobrança original.
     * @param array  $customer       Dados do cliente (nome, documento, email, telefone).
     * @param string $payment_token  Token do cartão a ser tentado.
     * @param array  $settings       Configurações da conta EfiPay.
     * @return array Resultado da operação.
     */
    public function cardPaymentRetry(string $charge_id, array $customer, string $payment_token, array $settings): array
    {
        try {
            $options = EfiConfigHelper::getEfiConfig($settings);
            $params = ["id" => $charge_id];

            $customer_data = $this->getFormattedCustomer($customer);

            $body = [
                'payment' => [
                    'credit_card' => [
                        'customer'       => $customer_data,
                        'payment_token'  => $payment_token
                    ]
                ]
            ];

            $efiPay = new EfiPay($options);
            $charge = $efiPay->cardPaymentRetry($params, $body);
            $chargeData = $charge['data'];

            if (isset($chargeData['status']) && $chargeData['status'] === 'approved') {
                return [
                    'success'   => true,
                    'charge_id' => $charge_id,
                    'status'    => $chargeData['status'],
                    'message'   => 'Pagamento confirmado via cartão de crédito.'
                ];
            } else {
                return [
                    'success'   => false,
                    'charge_id' => $charge_id,
                    'status'    => $chargeData['status'] ?? null,
                    'message'   => 'Não foi possível processar o pagamento. Verifique os dados do cartão e tente novamente.'
                ];
            }
        } catch (Exception $e) {
            $this->logError("Erro na retentativa do cartão: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'Erro na retentativa de pagamento via cartão.'
            ];
        }
    }

    /**
     * Formata os dados do cliente para pessoa física ou jurídica.
     *
     * @param array $customer
     * @return array
     */
    public function getFormattedCustomer(array $customer): array
    {
        $document = preg_replace('/\D/', '', $customer['document']);

        $base = [
            'phone_number' => preg_replace('/\D/', '', $customer['phone'] ?? ''),
            'email'        => $customer['email'] ?? '',
        ];

        if (strlen($document) === 11) {
            $base['name'] = $customer['name'] ?? '';
            $base['cpf']  = $document;
        } elseif (strlen($document) === 14) {
            $base['juridical_person'] = [
                'corporate_name' => $customer['name'] ?? '',
                'cnpj'           => $document
            ];
        }

        return $base;
    }

    /**
     * Registra erros no log do OpenCart.
     *
     * @param string $message
     * @return void
     */
    private function logError(string $message): void
    {
        $log = new \Opencart\System\Library\Log('efi_card.log');
        $log->write($message);
    }
}
