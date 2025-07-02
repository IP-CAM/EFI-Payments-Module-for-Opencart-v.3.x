<?php

namespace Opencart\Catalog\Model\Extension\Efi\Payment\Billet;

require_once DIR_EXTENSION . 'efi/library/vendor/autoload.php';

use Opencart\Extension\Efi\Library\EfiConfigHelper;
use Opencart\Extension\Efi\Library\EfiShippingHelper;
use Efi\EfiPay;
use Exception;

class EfiBillet extends \Opencart\System\Engine\Model
{
    public function generateBilletCharge(array $customer, float $amount, string $order_id, array $settings, array $order_info): array
    {
        try {
            $options        = EfiConfigHelper::getEfiConfig($settings);
            $customer_data  = $this->getFormattedCustomer($customer);
            $expireAt       = $this->getExpireAt();
            $metadata       = $this->getMetadata($order_id);
            $configurations = $this->getConfigurations();
            $message        = $this->getMessage();
            $discount       = $this->getDiscount($amount);

            $paymentData = [
                'customer'  => $customer_data,
                'expire_at' => $expireAt
            ];

            if (!empty($configurations)) {
                $paymentData['configurations'] = $configurations;
            }

            if (!empty($message)) {
                $paymentData['message'] = $message;
            }

            if (!empty($discount)) {
                $paymentData['discount'] = $discount;
            }

            $body = [
                'items' => [
                    [
                        'name'   => "Pedido #{$order_id}",
                        'value'  => intval($amount * 100),
                        'amount' => 1
                    ]
                ],
                'metadata' => $metadata,
                'payment'  => [
                    'banking_billet' => $paymentData
                ]
            ];

            // Adiciona frete se houver
            $shippings = EfiShippingHelper::getShippingsFromOrder($order_info);
            $this->logInfo('SHIPPINGS: ' . json_encode($shippings));

            if (!empty($shippings)) {
                $body['shippings'] = $shippings;
            }

            $efiPay     = new EfiPay($options);
            $charge     = $efiPay->createOneStepCharge([], $body);
            $chargeData = $charge['data'];

            $this->logInfo(json_encode($chargeData));

            return [
                'success'   => true,
                'charge_id' => $chargeData['charge_id'] ?? null,
                'status'    => $chargeData['status'] ?? null,
                'link'      => $chargeData['pdf']['charge'] ?? null,
                'message'   => 'Pagamento aguardando confirmação.'
            ];
        } catch (Exception $e) {
            $this->logError("Erro na geração da cobrança via boleto: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'Erro ao gerar cobrança via boleto.'
            ];
        }
    }

    private function getFormattedCustomer(array $customer): array
    {
        $document = preg_replace('/\D/', '', $customer['document']);
        $base     = [];

        if (strlen($document) === 11) {
            $base['name'] = $customer['name'];
            $base['cpf']  = $document;
        } elseif (strlen($document) === 14) {
            $base['juridical_person'] = [
                'corporate_name' => $customer['name'],
                'cnpj'           => $document
            ];
        }

        if ($this->config->get('payment_efi_billet_email')) {
            $base['email'] = $customer['email'];
        }

        return $base;
    }

    private function getExpireAt(): string
    {
        $expireAtDays = (int) $this->config->get('payment_efi_billet_expire_at') ?? 0;

        $dueDate = new \DateTime();
        $dueDate->modify("+{$expireAtDays} days");

        return $dueDate->format('Y-m-d');
    }

    private function getMetadata(string $order_id): array
    {
        return [
            'custom_id'        => $order_id,
            'notification_url' => $this->buildNotificationUrl()
        ];
    }

    private function buildNotificationUrl(): string
    {
        $baseUrl = '';

        if (defined('HTTPS_CATALOG') && !empty(HTTPS_CATALOG)) {
            $baseUrl = HTTPS_CATALOG;
        } elseif (defined('HTTP_CATALOG') && !empty(HTTP_CATALOG)) {
            $baseUrl = HTTP_CATALOG;
        } else {
            $baseUrl = $this->config->get('config_url') ?: '';
        }

        $url = rtrim($baseUrl, '/') . '/index.php?route=extension/efi/payment/efi_charge_notification';

        $language = $this->config->get('config_language');
        $url .= '&language=' . urlencode($language);

        return $url;
    }

    private function getMessage(): string
    {
        return $this->config->get('payment_efi_billet_message') ?? '';
    }

    private function getConfigurations(): array
    {
        $fine     = $this->config->get('payment_efi_billet_fine');
        $interest = $this->config->get('payment_efi_billet_interest');

        $configurations = [
            'fine'     => 0,
            'interest' => 0
        ];

        if ($fine !== '' && $fine !== null) {
            $configurations['fine'] = (int) $fine;
        }

        if ($interest !== '' && $interest !== null) {
            $configurations['interest'] = (int) $interest;
        }

        return $configurations;
    }

    private function getDiscount(float $amount): array
    {
        $discountValue = $this->config->get('payment_efi_billet_discount');

        if (!$discountValue || $amount < 10) {
            return [];
        }

        $discount = [];

        if (str_ends_with($discountValue, '%')) {
            $numericValue      = (float) rtrim($discountValue, '%');
            $discount['type']  = 'percentage';
            $discount['value'] = (int) ($numericValue * 100);
        } else {
            $numericValue      = (float) str_replace(',', '.', $discountValue);
            $discount['type']  = 'currency';
            $discount['value'] = (int) ($numericValue * 100);
        }

        $this->logInfo("Desconto aplicado: tipo={$discount['type']}, valor={$discount['value']} (entrada: {$discountValue})");

        return $discount;
    }

    private function logError(string $message): void
    {
        $log = new \Opencart\System\Library\Log('efi_charge_billet.log');
        $log->write('[ERROR] ' . $message);
    }

    private function logInfo(string $message): void
    {
        $log = new \Opencart\System\Library\Log('efi_charge_billet.log');
        $log->write('[INFO] ' . $message);
    }
}
