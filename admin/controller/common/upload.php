<?php

namespace Opencart\Admin\Controller\Extension\Efi\Common;

use Opencart\System\Library\Log;

class Upload extends \Opencart\System\Engine\Controller
{
    public function index(): void
    {
        $this->response->addHeader('Content-Type: application/json');
        $result = ['success' => null, 'error' => null];
        $log = new Log('efi.log');

        try {
            $file = reset($_FILES);

            if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
                $result['error'] = 'Certificado (.p12) não enviado ou houve erro no upload.';
                $this->response->setOutput(json_encode($result));
                return;
            }

            $allowed = ['application/x-pkcs12'];
            if (!in_array($file['type'], $allowed)) {
                $result['error'] = 'O arquivo enviado não é um certificado .p12 válido.';
                $this->response->setOutput(json_encode($result));
                return;
            }

            $uploadDir = DIR_STORAGE . 'efi_certificates/';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new \RuntimeException('Falha ao criar diretório de upload.');
            }

            $filePath = $uploadDir . basename($file['name']);

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $this->load->model('setting/setting');
                $settings = $this->model_setting_setting->getSetting('payment_efi');
                $settings['payment_efi_pix_certificate'] = $filePath;
                $settings['payment_efi_open_finance_certificate'] = $filePath;
                $this->model_setting_setting->editSetting('payment_efi', $settings);

                $result['success'] = 'Certificado carregado com sucesso! Recarregue a página para ver o novo caminho.';
            } else {
                $result['error'] = 'Erro ao salvar o certificado (.p12).';
            }
        } catch (\Throwable $e) {
            $log->write('Upload.php exception: ' . $e->getMessage());
            $result['error'] = 'Ocorreu um erro inesperado ao processar o upload. Confira o log para mais detalhes.';
        }

        $this->response->setOutput(json_encode($result));
    }
}
