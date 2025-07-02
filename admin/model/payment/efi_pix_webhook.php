<?php

namespace Opencart\Admin\Model\Extension\Efi\Payment;

require_once DIR_EXTENSION . 'efi/library/vendor/autoload.php';

use Efi\EfiPay;
use Efi\Exception\EfiException;
use Exception;
use Opencart\System\Library\Log;
use Opencart\Extension\Efi\Library\EfiConfigHelper;

/**
 * Classe responsável pelo cadastro do Webhook Pix no OpenCart.
 */
class EfiPixWebhook extends \Opencart\System\Engine\Model
{
    private Log $log;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->log = new Log('efi.log');
    }

    /**
     * Retorna a URL base segura da loja (HTTPS), com suporte a proxy reverso.
     *
     * @throws Exception Se nenhuma URL segura puder ser encontrada.
     * @return string
     */
    private function getSecureBaseUrl(): string
    {
        $httpsCatalog = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : '';

        if (!empty($httpsCatalog)) {
            return rtrim($httpsCatalog, '/');
        }

        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $host  = $_SERVER['HTTP_HOST'] ?? null;

        if ($proto === 'https' && $host) {
            return 'https://' . rtrim($host, '/');
        }

        throw new Exception('Não conseguimos identificar que sua loja está usando conexão segura (HTTPS). Verifique a configuração da URL da loja.');
    }

    /**
     * Gera um hash HMAC com base na URL e Client ID.
     */
    private function generateHmac(string $url, string $clientId): string
    {
        return hash_hmac('sha256', $url, $clientId);
    }

    /**
     * Monta a URL final do webhook com segurança.
     */
    public function getWebhookUrl(array $data): string
    {
        $baseUrl = $this->getSecureBaseUrl();

        $webhookBaseUrl = $baseUrl . '/index.php?route=extension/efi/payment/efi_pix_webhook';

        $language = $this->config->get('config_language_admin');
        if (!$language) {
            throw new Exception('Não foi possível obter o idioma padrão da loja (config_language_admin).');
        }

        // Caso o mtls esteja ativado (valor 1), NÃO adiciona HMAC
        if (!empty($data['payment_efi_pix_mtls']) && (int)$data['payment_efi_pix_mtls'] === 1) {
            $this->log->write("MTLS ativado, webhook será cadastrado sem HMAC.");
            return $webhookBaseUrl . '&language=' . $language . '&ignorar=';
        }

        // Caso contrário, inclui HMAC normalmente
        $clientId = $data['payment_efi_client_id_production'] ?? null;
        if (!$clientId) {
            throw new Exception('Client ID de produção não foi encontrado nas configurações.');
        }

        $hmac = $this->generateHmac($webhookBaseUrl, $clientId);
        return $webhookBaseUrl . '&language=' . $language . '&hmac=' . $hmac . '&ignorar=';
    }

    /**
     * Registra o webhook Pix com os dados fornecidos.
     */
    public function registerWebhook(array $data): array
    {
        try {
            $config = EfiConfigHelper::getEfiConfig($data);
            $config["headers"] = [
                "x-skip-mtls-checking" => ($data["payment_efi_pix_mtls"] != 1)
            ];
            $api = new EfiPay($config);

            $params = [
                "chave" => $data["payment_efi_pix_key"]
            ];

            $webhookUrl = $this->getWebhookUrl($data);
            $body = ["webhookUrl" => $webhookUrl];

            $response = $api->pixConfigWebhook($params, $body);
            $this->log->write('Webhook Pix cadastrado com sucesso: ' . json_encode($response));

            return ['success' => 'Webhook Pix cadastrado com sucesso.'];
        } catch (EfiException $e) {
            $this->log->write('EfiException Code: ' . $e->code);
            $this->log->write('EfiException Error: ' . $e->error);
            $this->log->write('EfiException Description: ' . $e->errorDescription);

            if (isset($config["responseHeaders"]) && $config["responseHeaders"] && isset($e->headers)) {
                $this->log->write('Response Headers: ' . json_encode($e->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            return ['error' => 'Erro ao cadastrar o webhook do Pix: ' . $e->errorDescription];
        } catch (Exception $e) {
            $this->log->write('Exception: ' . $e->getMessage());
            return ['error' => 'Erro ao cadastrar o webhook do Pix: ' . $e->getMessage()];
        }
    }
}
