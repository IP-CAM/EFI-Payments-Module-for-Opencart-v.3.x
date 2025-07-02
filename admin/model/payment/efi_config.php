<?php

namespace Opencart\Admin\Model\Extension\Efi\Payment;

use \Opencart\System\Library\Language;
use \Opencart\System\Library\Log;

/**
 * Classe responsável por gerenciar configurações específicas do plugin.
 */
class EfiConfig extends \Opencart\System\Engine\Model
{
    /**
     * Retorna todas as configurações formatadas com os valores salvos no banco de dados.
     *
     * @param Language $language Instância do objeto Language.
     * @return array Todas as configurações formatadas.
     */
    public function getConfig(Language $language): array
    {
        $log = new Log('efi.log');

        $requiredConfig = [];
        $pixConfig = [];

        try {
            // Carregar e formatar configurações obrigatórias
            $this->load->model('extension/efi/payment/efi_config_required');
            $requiredConfig = $this->model_extension_efi_payment_efi_config_required->getEntryFormatted($language);
            $requiredConfig = $this->populateConfigValues($requiredConfig);
        } catch (\Exception $e) {
            $log->write("Erro ao carregar configurações obrigatórias: " . $e->getMessage());
        }

        try {
            // Carregar e formatar configurações Pix
            $this->load->model('extension/efi/payment/efi_config_pix');
            $pixConfig = $this->model_extension_efi_payment_efi_config_pix->getEntryFormatted($language);
            $pixConfig = $this->populateConfigValues($pixConfig);
        } catch (\Exception $e) {
            $log->write("Erro ao carregar configurações Pix: " . $e->getMessage());
        }
        try {
            // Carregar e formatar configurações Cartão
            $this->load->model('extension/efi/payment/efi_config_card');
            $cardConfig = $this->model_extension_efi_payment_efi_config_card->getEntryFormatted($language);
            $cardConfig = $this->populateConfigValues($cardConfig);
        } catch (\Exception $e) {
            $log->write("Erro ao carregar configurações Cartão: " . $e->getMessage());
        }
        try {
            // Carregar e formatar configurações Boleto
            $this->load->model('extension/efi/payment/efi_config_billet');
            $billetConfig = $this->model_extension_efi_payment_efi_config_billet->getEntryFormatted($language);
            $billetConfig = $this->populateConfigValues($billetConfig);
        } catch (\Exception $e) {
            $log->write("Erro ao carregar configurações Boleto: " . $e->getMessage());
        }
        try {
            // Carregar e formatar configurações Open Finance
            $this->load->model('extension/efi/payment/efi_config_open_finance');
            $openFinancetConfig = $this->model_extension_efi_payment_efi_config_open_finance->getEntryFormatted($language);
            $openFinancetConfig = $this->populateConfigValues($openFinancetConfig);
        } catch (\Exception $e) {
            $log->write("Erro ao carregar configurações Boleto: " . $e->getMessage());
        }

        // Retornar todas as configurações agrupadas
        return [
            'required' => $requiredConfig,
            'pix' => $pixConfig,
            'billet' => $billetConfig,
            'open_finance' => $openFinancetConfig,
            'card' => $cardConfig,
        ];
    }

    /**
     * Popula as configurações com os valores salvos no banco de dados.
     *
     * @param array $config Configurações formatadas com inputs.
     * @return array Configurações com valores preenchidos.
     */
    private function populateConfigValues(array $config): array
    {
        foreach ($config['inputs'] as &$input) {
            $savedValue = $this->config->get($input['name']);
            if ($savedValue !== null) {
                $input['value'] = $savedValue;
            }
        }
        return $config;
    }
}
