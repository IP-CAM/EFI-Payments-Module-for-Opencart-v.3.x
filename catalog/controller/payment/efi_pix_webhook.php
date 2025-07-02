<?php

namespace Opencart\Catalog\Controller\Extension\Efi\Payment;

use Opencart\System\Library\Log;

/**
 * Class EfiPixWebhook
 *
 * Controlador para receber e processar os Webhooks do Pix.
 */
class EfiPixWebhook extends \Opencart\System\Engine\Controller
{
    private Log $log;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->log = new Log('efi_pix_webhook.log');
    }

    public function index(): void
    {
        try {
            $this->response->addHeader('Content-Type: application/json');

            // Carregar configurações do módulo
            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('payment_efi');

            $mtlsAtivo = (int) ($settings['payment_efi_pix_mtls'] ?? 0) === 1;

            if (!$mtlsAtivo) {
                if (!$this->validateHmac($settings)) {
                    return;
                }
            } else {
                $this->log->write('MTLS ativado. Validação de HMAC ignorada.');
            }

            $input = file_get_contents('php://input');
            $webhookData = json_decode($input, true);

            $this->log->write('Webhook recebido: ' . json_encode($webhookData));

            if (!$webhookData || (isset($webhookData['evento']) && $webhookData['evento'] === 'teste_webhook')) {
                $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 200 OK');
                $this->response->setOutput('Webhook processado com sucesso');
                return;
            }

            if (!isset($webhookData['pix'])) {
                $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 400 Bad Request');
                $this->response->setOutput('Webhook inválido');
                return;
            }

            foreach ($webhookData['pix'] as $pix) {
                try {
                    $txid = $pix['txid'] ?? null;

                    if (!$txid) {
                        $this->log->write('Erro: Webhook recebido sem txid.');
                        continue;
                    }

                    $order_id = ltrim(substr($txid, 2), '0');

                    if (!ctype_digit($order_id)) {
                        $this->log->write("Erro: TXID inválido. TXID recebido: $txid");
                        continue;
                    }

                    $this->load->model('checkout/order');
                    $order_info = $this->model_checkout_order->getOrder((int) $order_id);

                    if (!$order_info) {
                        $this->log->write("Erro: Pedido não encontrado para o número: $order_id");
                        continue;
                    }

                    $order_status_id = $settings['payment_efi_order_status_paid'] ?? 0;

                    if ($order_status_id) {
                        $this->model_checkout_order->addHistory($order_info['order_id'], $order_status_id, 'Pagamento confirmado via Pix', true);
                    }
                } catch (\Exception $e) {
                    $this->log->write("Erro ao processar transação Pix: " . $e->getMessage());
                }
            }

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 200 OK');
            $this->response->setOutput('Webhook processado com sucesso');
        } catch (\Exception $e) {
            $this->log->write("Erro inesperado no processamento do webhook: " . $e->getMessage());
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
            $this->response->setOutput('Erro interno no processamento do webhook');
        }
    }

    private function validateHmac(array $settings): bool
    {
        $hmacRecebido = $this->request->get['hmac'] ?? '';

        if (!$hmacRecebido) {
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 401 Unauthorized');
            $this->response->setOutput('HMAC não fornecido.');
            return false;
        }

        $clientId = $settings['payment_efi_client_id_production'] ?? '';
        if (!$clientId) {
            $this->log->write("Erro: Client ID de produção não encontrado.");
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
            $this->response->setOutput('Erro de configuração.');
            return false;
        }

        $webhookUrl = $this->getWebhookUrlBase();
        $hmacCalculado = hash_hmac('sha256', $webhookUrl, $clientId);

        if (!hash_equals($hmacCalculado, $hmacRecebido)) {
            $this->log->write("HMAC inválido. Recebido: $hmacRecebido | Esperado: $hmacCalculado");
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 403 Forbidden');
            $this->response->setOutput('HMAC inválido.');
            return false;
        }

        return true;
    }

    private function getWebhookUrlBase(): string
    {
        $httpsCatalog = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : '';

        if (!empty($httpsCatalog)) {
            return rtrim($httpsCatalog, '/') . '/index.php?route=extension/efi/payment/efi_pix_webhook';
        }

        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $host  = $_SERVER['HTTP_HOST'] ?? null;

        if ($proto === 'https' && $host) {
            return 'https://' . rtrim($host, '/') . '/index.php?route=extension/efi/payment/efi_pix_webhook';
        }

        $this->log->write("Erro: Não foi possível determinar uma URL segura.");
        return '';
    }
}
