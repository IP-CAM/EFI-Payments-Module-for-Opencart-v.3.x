<?php

namespace Opencart\Catalog\Controller\Extension\Efi\Payment;

class EfiPix extends \Opencart\System\Engine\Controller
{
    public function confirm(): void
    {
        try {
            $this->validarRequisicaoPost();

            $customer_name = $this->request->post['payment_efi_customer_name'] ?? '';
            $customer_document = $this->request->post['payment_efi_customer_document'] ?? '';
            $order_id = $this->session->data['order_id'] ?? 0;

            $this->logEfi("Processando pedido ID: $order_id");

            $this->validarDadosCliente($customer_name, $customer_document);

            if (!$order_id) {
                throw new \Exception('ID do pedido não encontrado.');
            }

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);

            if (!$order_info) {
                throw new \Exception('Pedido não encontrado.');
            }

            $amount = $this->cart->getTotal();
            [$pixModel, $settings] = $this->loadPixDependencies();


            // Passa o order_info para o model
            $pix_data = $pixModel->generatePix($customer_name, $customer_document, $amount, $order_id, $settings, $order_info);

            if (!$pix_data['success']) {
                throw new \Exception($pix_data['error']);
            }

            $order_status_id = 2;
            $pixUrl = $this->url->link('extension/efi/payment/efi_pix.qrcode', 'locId=' . $pix_data['locId'] . '&language=' . $this->config->get('config_language'));
            $ajaxUrl = html_entity_decode($this->url->link('extension/efi/payment/efi_pix.detailpix', 'txid=' . $pix_data['txid'] . '&language=' . $this->config->get('config_language')));

            $comment = 'Pagamento via Pix aguardando confirmação:<br><a href="' . $pixUrl . '" class="btn btn-sm btn-primary mt-2" target="_blank">Visualizar QR Code</a>';
            $this->model_checkout_order->addHistory($order_id, $order_status_id, $comment, true);

            $this->cart->clear();
            unset($this->session->data['order_id']);

            $data = [
                'success' => true,
                'qrcode' => $pix_data['qrcode'],
                'pix_url' => $pix_data['pix_url'],
                'txid' => $pix_data['txid'],
                'expiration_time' => $pix_data['expiration_time'],
                'ajax_verify_payment_controller_url' => $ajaxUrl
            ];

            $this->response->addHeader('Content-Type: text/html; charset=UTF-8');
            $this->response->setOutput($this->load->view('extension/efi/payment/efi_pix_modal', $data));
        } catch (\Exception $e) {
            $this->logEfi("Erro no processamento Pix: " . $e->getMessage());
            $data['error'] = $e->getMessage();
            $this->response->addHeader('Content-Type: text/html; charset=UTF-8');
            $this->response->setOutput($this->load->view('extension/efi/payment/efi_error_ajax', $data));
        }
    }

    public function qrcode(): void
    {
        try {
            $locId = $this->request->get['locId'] ?? '';
            if (empty($locId)) {
                throw new \Exception('Dados do Loc não fornecidos.');
            }

            $data = $this->loadCommonLayout();
            [$pixModel, $settings] = $this->loadPixDependencies();

            $qrCode = $pixModel->getLocQRCode($locId, $settings);
            $data['qrcode'] = $qrCode['qrcode'];
            $data['pix_url'] = $qrCode['imagemQrcode'];

            $this->response->setOutput($this->load->view('extension/efi/payment/efi_visualize_pix_for_payment', $data));
        } catch (\Exception $e) {
            $this->logEfi("Erro ao exibir QR Code Pix: " . $e->getMessage());
            $data = $this->loadCommonLayout();
            $data['error'] = 'Não foi possível exibir o QR Code. Consulte o suporte.';

            $this->response->setOutput($this->load->view('extension/efi/payment/efi_error_ajax', $data));
        }
    }

    public function detailpix(): void
    {
        try {
            $txid = $this->request->get['txid'] ?? '';

            if (empty($txid)) {
                throw new \Exception('Dados do txid não encontrados.');
            }

            $order_id = ltrim(substr($txid, 2), '0');

            if (!ctype_digit($order_id)) {
                $this->log->write("Erro: TXID inválido ou não contém um número de pedido válido. TXID recebido: $txid");
                $this->response->setOutput(json_encode(['error' => 'Pedido inválido.']));
                return;
            }

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder((int) $order_id);

            if (!$order_info) {
                $this->log->write("Erro: Pedido não encontrado para o número: $order_id");
                $this->response->setOutput(json_encode(['error' => 'Pedido não encontrado.']));
                return;
            }

            $order_status_id_atual = $order_info['order_status_id'];
            $order_status_id_pago  = $this->config->get('payment_efi_order_status_paid');

            $data['status'] = ($order_status_id_atual == $order_status_id_pago) ? 'CONCLUIDA' : null;
            $data['redirect'] = html_entity_decode($this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true), ENT_QUOTES, 'UTF-8');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($data));
        } catch (\Exception $e) {
            $this->logEfi("Erro no processamento: " . $e->getMessage());
            $this->response->clearHeaders();
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => $e->getMessage()]));
        }
    }

    private function logEfi(string $message): void
    {
        $log = new \Opencart\System\Library\Log('efi.log');
        $log->write($message);
    }

    private function loadPixDependencies(): array
    {
        $this->load->model('setting/setting');
        $this->load->model('extension/efi/payment/pix/efi_pix');

        return [
            $this->model_extension_efi_payment_pix_efi_pix,
            $this->model_setting_setting->getSetting('payment_efi')
        ];
    }

    private function loadCommonLayout(): array
    {
        return [
            'header' => $this->load->controller('common/header'),
            'footer' => $this->load->controller('common/footer'),
            'column_left' => $this->load->controller('common/column_left')
        ];
    }

    private function validarRequisicaoPost(): void
    {
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            throw new \Exception('Requisição inválida.');
        }
    }

    private function validarDadosCliente(string $nome, string $documento): void
    {
        if (empty($nome) || empty($documento)) {
            throw new \Exception('Nome e documento são obrigatórios.');
        }
    }
}
