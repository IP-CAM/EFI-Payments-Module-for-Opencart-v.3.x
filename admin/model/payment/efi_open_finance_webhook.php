<?php

namespace Opencart\Admin\Model\Extension\Efi\Payment;

require_once DIR_EXTENSION . 'efi/library/vendor/autoload.php';

use Efi\EfiPay;
use Efi\Exception\EfiException;
use Exception;
use Opencart\System\Library\Log;
use Opencart\Extension\Efi\Library\EfiConfigHelper;

class EfiOpenFinanceWebhook extends \Opencart\System\Engine\Model
{
    private Log $log;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->log = new Log('efi_open_finance_webhook.log');
    }

    private function getSecureBaseUrl(): string
    {
        if (!defined('HTTPS_CATALOG')) {
            define('HTTPS_CATALOG', '');
        }

        if (!empty(HTTPS_CATALOG)) {
            return rtrim(HTTPS_CATALOG, '/');
        }

        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $host  = $_SERVER['HTTP_HOST'] ?? null;

        if ($proto === 'https' && $host) {
            return 'https://' . rtrim($host, '/');
        }

        throw new Exception('Sua loja não está configurada com uma URL segura (HTTPS). Por favor, revise a configuração.');
    }

    private function generateHmac(string $url, string $clientId): string
    {
        return hash_hmac('sha256', $url, $clientId);
    }

    public function registerWebhook(array $data): array
    {
        try {
            $config = EfiConfigHelper::getEfiConfig($data);
            $api = new EfiPay($config);

            $baseUrl  = $this->getSecureBaseUrl();
            $language = $this->config->get('config_language_admin');

            if (!$language) {
                throw new Exception('Não foi possível obter o idioma padrão da loja (config_language_admin).');
            }

            $clientId = $data['payment_efi_client_id_production'] ?? null;

            if (!$clientId) {
                throw new Exception('Client ID de produção não foi encontrado nas configurações.');
            }

            $webhookUrl  = $baseUrl . '/index.php?route=extension/efi/payment/efi_open_finance_webhook&language=' . $language;
            $redirectUrl = $baseUrl . '/index.php?route=extension/efi/payment/efi_open_finance_redirect&language=' . $language;
            $hmacHash    = $this->generateHmac($webhookUrl, $clientId);

            $body = [
                'redirectURL'      => $redirectUrl,
                'webhookURL'       => $webhookUrl,
                'webhookSecurity'  => [
                    'type' => 'hmac',
                    'hash' => $hmacHash
                ],
                'processPayment'   => 'sync',
                "generateTxIdForInic" => true
            ];

            $response = $api->ofConfigUpdate([], $body);

            $this->log->write('Open Finance configurado com sucesso (HMAC): ' . json_encode($response));
            return ['success' => 'Configuração do Open Finance realizada com sucesso.'];
        } catch (EfiException $e) {
            $this->log->write('EfiException Code: ' . $e->code);
            $this->log->write('EfiException Error: ' . $e->error);
            $this->log->write('EfiException Description: ' . $e->errorDescription);

            if (isset($config["responseHeaders"]) && $config["responseHeaders"] && isset($e->headers)) {
                $this->log->write('Response Headers: ' . json_encode($e->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            return ['error' => 'Erro ao configurar o Open Finance: ' . $e->errorDescription];
        } catch (Exception $e) {
            $this->log->write('Exception: ' . $e->getMessage());
            return ['error' => 'Erro ao configurar o Open Finance: ' . $e->getMessage()];
        }
    }
}
