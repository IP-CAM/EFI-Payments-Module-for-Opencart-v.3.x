<?php

namespace Opencart\Admin\Controller\Extension\Efi\Event;

use Efi\EfiPay;
use Opencart\Extension\Efi\Library\EfiConfigHelper;
use Opencart\System\Library\Log;

/**
 * Listener para eventos de atualização de status do pedido.
 * Cancela boleto Efí caso o pedido seja cancelado.
 */
class EfiCancelListener extends \Opencart\System\Engine\Controller
{
    /**
     * Executa quando o status do pedido é alterado.
     *
     * @param string $route Rota do evento
     * @param array $args Argumentos do evento [order_id, new_status_id]
     * @param mixed $output Saída do evento
     */
    public function onOrderStatusUpdate(string &$route, array &$args, mixed &$output): void
    {
        $order_id = (int) ($args[0] ?? 0);
        $new_status_id = (int) ($args[1] ?? 0);
        $cancel_status_id = 7; // Ajuste conforme o ID de status de cancelamento da sua loja

        $this->log("Evento acionado. dados repassados: " . json_encode($args));

        if ($new_status_id !== $cancel_status_id) {
            $this->log("Status diferente de cancelamento. Ignorando.");
            return;
        }

        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('payment_efi');

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        if (!$order_info) {
            $this->log("Pedido não encontrado para o ID: {$order_id}");
            return;
        }

        $this->log("Payment code do pedido #{$order_id}: {$order_info['payment_code']}");

        if ($order_info['payment_code'] !== 'efi_billet') {
            $this->log("Pagamento não é 'efi_billet'. Ignorando.");
            return;
        }

        $charge_id = $this->getChargeIdFromEfi($order_id, $settings);

        if (!$charge_id) {
            $this->log("Charge ID não encontrado para o pedido #{$order_id}");
            return;
        }

        try {
            $this->cancelarCobrancaEfi($charge_id, $settings);
        } catch (\Exception $e) {
            $this->log("Erro ao cancelar cobrança (pedido #{$order_id}): " . $e->getMessage());
        }
    }

    private function getChargeIdFromEfi(int $order_id, array $settings): ?int
    {
        $efiPay = new EfiPay(EfiConfigHelper::getEfiConfig($settings));

        $end = new \DateTimeImmutable();
        $begin = $end->sub(new \DateInterval('P1Y'));

        $params = [
            "charge_type" => "billet",
            "begin_date" => $begin->format('Y-m-d'),
            "end_date" => $end->format('Y-m-d'),
            "custom_id" => (string) $order_id,
        ];

        try {
            $response = $efiPay->listCharges($params);

            if (!empty($response['data'][0]['charge_id'])) {
                return (int) $response['data'][0]['charge_id'];
            }

            return null;
        } catch (\Exception $e) {
            $this->log("Erro ao buscar charge_id para o pedido #{$order_id}: " . $e->getMessage());
            return null;
        }
    }

    private function cancelarCobrancaEfi(int $charge_id, array $settings): void
    {
        $efiPay = new EfiPay(EfiConfigHelper::getEfiConfig($settings));
        $efiPay->cancelCharge(['id' => $charge_id]);
        $this->log("Cobrança cancelada com sucesso (charge_id: {$charge_id})");
    }

    private function log(string $message): void
    {
        $log = new Log('efi_cancel.log');
        $log->write($message);
    }
}
