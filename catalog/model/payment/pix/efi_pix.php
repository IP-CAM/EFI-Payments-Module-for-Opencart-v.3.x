<?php

namespace Opencart\Catalog\Model\Extension\Efi\Payment\Pix;

require_once DIR_EXTENSION . 'efi/library/vendor/autoload.php';

use Opencart\Extension\Efi\Library\EfiConfigHelper;
use Opencart\Extension\Efi\Library\EfiShippingHelper;
use Efi\EfiPay;
use Exception;

class EfiPix extends \Opencart\System\Engine\Model
{
    public function generatePix(string $customer_name, string $customer_document, float $amount, string $order_id, array $settings, array $order_info): array
    {
        try {
            $options = EfiConfigHelper::getEfiConfig($settings);
            $pix_key = $this->config->get('payment_efi_pix_key');
            $pix_expire_at = (int) $this->config->get('payment_efi_pix_expire_at') * 3600;
            $pix_discount = $this->config->get('payment_efi_pix_discount');

            // Log do valor original
            $this->logError("VALOR ORIGINAL: {$amount}");

            // Aplica desconto
            $amount = $this->applyDiscount($amount, $pix_discount);
            $this->logError("VALOR COM DESCONTO: {$amount}");

            // Aplica frete (caso haja)
            $shippings = EfiShippingHelper::getShippingsFromOrder($order_info, 'pix');
            $this->logError('SHIPPING: ' . json_encode($shippings));

            if (isset($shippings['value'])) {
                $amount += $shippings['value'];
            }


            $this->logError("VALOR FINAL (COM FRETE): {$amount}");

            $txid = $this->generateTxid($order_id);
            $devedor = $this->getDevedor($customer_name, $customer_document);
            if (!$devedor) {
                throw new Exception('Documento inválido.');
            }

            $body = [
                'calendario' => ['expiracao' => $pix_expire_at],
                'devedor' => $devedor,
                'valor' => ['original' => number_format($amount, 2, '.', '')],
                'chave' => $pix_key,
                'solicitacaoPagador' => "Pagamento do Pedido #{$order_id}"
            ];

            $efiPay = new EfiPay($options);
            $params = ['txid' => $txid];
            $pix_charge = $efiPay->pixCreateCharge($params, $body);

            if (!isset($pix_charge['loc']['id'])) {
                throw new Exception('Erro ao gerar cobrança Pix.');
            }

            $qrcode = $efiPay->pixGenerateQRCode(['id' => $pix_charge['loc']['id']]);
            $expiration_time = $pix_charge['calendario']['expiracao'] ?? $pix_expire_at;

            return [
                'success' => true,
                'locId' => $pix_charge['loc']['id'],
                'qrcode' => $qrcode['qrcode'],
                'pix_url' => $qrcode['imagemQrcode'],
                'txid' => $txid,
                'expiration_time' => $expiration_time
            ];
        } catch (Exception $e) {
            $this->logError("Erro na geração do Pix: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao gerar Pix. Consulte o log.'
            ];
        }
    }

    public function getLocQRCode(string $locId, array $settings)
    {
        $options = EfiConfigHelper::getEfiConfig($settings);
        $efiPay = new EfiPay($options);

        return $efiPay->pixGenerateQRCode(['id' => $locId]);
    }

    public function getDetailPix(string $txid, array $settings)
    {
        $options = EfiConfigHelper::getEfiConfig($settings);
        $efiPay = new EfiPay($options);

        return $efiPay->pixDetailCharge(['txid' => $txid]);
    }

    private function applyDiscount(float $amount, string $discount): float
    {
        $discount = trim($discount);
        if (strpos($discount, '%') !== false) {
            $percent = (float) str_replace('%', '', $discount);
            $value = ($amount * $percent / 100);
        } else {
            $value = (float) $discount;
        }

        return max(0, $amount - $value);
    }

    private function generateTxid(string $order_id): string
    {
        $zero_fill = str_repeat('0', max(0, 33 - strlen($order_id)));
        return "OC" . substr($zero_fill . $order_id, -33);
    }

    private function getDevedor(string $name, string $document): ?array
    {
        $doc = preg_replace('/\D/', '', $document);
        if (strlen($doc) === 11) {
            return ['nome' => $name, 'cpf' => $doc];
        } elseif (strlen($doc) === 14) {
            return ['nome' => $name, 'cnpj' => $doc];
        }
        return null;
    }

    private function logError(string $message): void
    {
        $log = new \Opencart\System\Library\Log('efi.log');
        $log->write($message);
    }
}
