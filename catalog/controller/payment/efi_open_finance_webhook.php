<?php

namespace Opencart\Catalog\Controller\Extension\Efi\Payment;

use Opencart\System\Library\Log;

/**
 * Class EfiOpenFinanceWebhook
 *
 * Controlador para receber e processar os Webhooks do Open Finance.
 */
class EfiOpenFinanceWebhook extends \Opencart\System\Engine\Controller
{
    private Log $log;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->log = new Log('efi_open_finance_webhook.log');
    }

    public function index(): void
    {
        try {
            $this->response->addHeader('Content-Type: application/json');

            // 1. Validação HMAC via query param ?hmac=
            $hmacReceived = $this->request->get['hmac'] ?? '';
            if (!$hmacReceived) {
                $this->log->write('HMAC não informado na query.');
                $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 403 Forbidden');
                $this->response->setOutput('HMAC ausente');
                return;
            }

            // Sempre usa https na montagem da base URL para o HMAC
            $baseUrl = 'https://' . $this->request->server['HTTP_HOST'];
            $language = $this->request->get['language'] ?? $this->config->get('config_language');
            $webhookUrlBase = $baseUrl . '/index.php?route=extension/efi/payment/efi_open_finance_webhook&language=' . $language;

            // Recupera o segredo da config
            $clientId = $this->config->get('payment_efi_client_id_production');
            if (!$clientId) {
                $this->log->write('Client ID não encontrado na configuração.');
                $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
                $this->response->setOutput('Configuração do módulo ausente');
                return;
            }
            $hmacExpected = hash_hmac('sha256', $webhookUrlBase, $clientId);

            // Compara os HMACs
            if (!hash_equals($hmacExpected, $hmacReceived)) {
                $this->log->write("HMAC inválido. Recebido: $hmacReceived | Esperado: $hmacExpected | Base: $webhookUrlBase | ClientId: $clientId");
                $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 403 Forbidden');
                $this->response->setOutput('HMAC inválido');
                return;
            }

            $input = file_get_contents('php://input');
            $webhookData = json_decode($input, true);

            $this->log->write('Webhook recebido: ' . json_encode($webhookData));

            if (!$webhookData || !isset($webhookData['tipo']) || $webhookData['tipo'] !== 'pagamento') {
                $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 200 Bad Request');
                $this->response->setOutput('Webhook configurado com sucesso');
                return;
            }

            $idProprio = $webhookData['idProprio'] ?? null;
            $status = $webhookData['status'] ?? null;

            if (!$idProprio || !$status) {
                $this->log->write('Erro: Webhook recebido sem idProprio ou status.');
                $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 400 Bad Request');
                $this->response->setOutput('Webhook incompleto');
                return;
            }

            // Extrai o order_id do idProprio no formato "OC-Order-518"
            $partes = explode('-', $idProprio);
            $order_id = isset($partes[2]) && ctype_digit($partes[2]) ? (int) $partes[2] : 0;

            if (!$order_id) {
                $this->log->write("Erro: idProprio inválido ou sem número de pedido. Valor: $idProprio");
                return;
            }

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);

            if (!$order_info) {
                $this->log->write("Erro: Pedido não encontrado para o número: $order_id");
                return;
            }

            $order_status_id = $this->config->get('payment_efi_order_status_paid');

            if ($status === 'aceito' && $order_status_id) {
                $this->model_checkout_order->addHistory(
                    $order_info['order_id'],
                    $order_status_id,
                    'Pagamento confirmado via Open Finance',
                    true
                );
            }

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 200 OK');
            $this->response->setOutput('Webhook processado com sucesso');
        } catch (\Exception $e) {
            $this->log->write("Erro inesperado no processamento do webhook Open Finance: " . $e->getMessage());
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
            $this->response->setOutput('Erro interno no processamento do webhook');
        }
    }
}
