<?php

namespace Opencart\Admin\Model\Extension\Efi\Payment;

use Opencart\System\Library\Log;

/**
 * Classe de validação para o plugin Efi no OpenCart.
 */
class EfiValidator extends \Opencart\System\Engine\Model
{
    /**
     * @var Log
     */
    private Log $log;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->log = new Log('efi.log');
    }

    /**
     * Valida as configurações do plugin Efi.
     *
     * @param array $configArray Configuração completa do plugin.
     * @param array $postData Dados enviados via POST.
     * @return array|null Retorna um erro formatado caso um campo obrigatório esteja vazio, ou null se tudo estiver válido.
     */
    public function validateConfig(array $configArray, array $postData): ?array
    {
        try {


            // Valida obrigatoriamente as configurações gerais
            if (isset($configArray['required'])) {
                $error = $this->validateSection($configArray['required'], $postData);
                if ($error) {
                    $this->log->write('Erro encontrado: ' . json_encode($error));
                    return $error;
                }
            }

            if (!empty($postData['payment_efi_pix_status']) && isset($configArray['pix'])) {
                $error = $this->validateSection($configArray['pix'], $postData);
                if ($error) {
                    $this->log->write('Erro encontrado: ' . json_encode($error));
                    return $error;
                }
            }
            if (!empty($postData['payment_efi_billet_status']) && isset($configArray['billet'])) {
                $error = $this->validateSection($configArray['billet'], $postData);
                if ($error) {
                    $this->log->write('Erro encontrado: ' . json_encode($error));
                    return $error;
                }
            }

            $this->log->write('Validação concluída sem erros.');
            return null; // Nenhum erro encontrado
        } catch (\Exception $e) {
            $this->log->write('Erro inesperado na validação: ' . $e->getMessage());
            return ["error" => "Ocorreu um erro inesperado na validação."];
        }
    }

    /**
     * Valida uma seção de configuração.
     *
     * @param array $sectionConfig Configuração de uma seção específica.
     * @param array $postData Dados enviados via POST.
     * @return array|null Retorna erro formatado se um campo obrigatório estiver vazio.
     */
    private function validateSection(array $sectionConfig, array $postData): ?array
    {
        try {
            foreach ($sectionConfig['inputs'] as $input) {
                if ($input['required'] && empty($postData[$input['name']])) {
                    $error = [
                        "error" => "O campo '{$input['label']}' da seção '{$sectionConfig['name']}' é obrigatório."
                    ];
                    $this->log->write('Erro de validação: ' . json_encode($error));
                    return $error;
                }
            }
            return null; // Nenhum erro encontrado
        } catch (\Exception $e) {
            $this->log->write('Erro inesperado na validação de seção: ' . $e->getMessage());
            return ["error" => "Ocorreu um erro inesperado na validação da seção."];
        }
    }
}
