<?php

namespace Opencart\Catalog\Controller\Extension\Efi\Payment;

class EfiBillet extends \Opencart\System\Engine\Controller
{
    public function confirm(): void
    {
        try {
            if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Requisição inválida.');
            }

            $order_id = $this->session->data['order_id'] ?? 0;
            $this->logError("Processando pedido ID: " . $order_id);

            if (!$order_id) {
                throw new \Exception('ID do pedido não encontrado.');
            }

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);

            if (!$order_info) {
                throw new \Exception('Pedido não encontrado.');
            }

            $amount = $this->cart->getTotal();

            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('payment_efi');

            $customer = [
                'name'     => $this->request->post['payment_efi_customer_name'] ?? '',
                'document' => $this->request->post['payment_efi_customer_document'] ?? '',
                'email'    => $this->request->post['payment_efi_customer_email'] ?? '',
            ];

            if (empty($customer['name']) || empty($customer['document'])) {
                $this->logError(json_encode($customer));
                throw new \Exception('Dados obrigatórios ausentes.');
            }

            $this->load->model('extension/efi/payment/billet/efi_billet');
            $charge_data = $this->model_extension_efi_payment_billet_efi_billet
                ->generateBilletCharge($customer, $amount, $order_id, $settings, $order_info);

            if (!$charge_data['success']) {
                throw new \Exception($charge_data['error']);
            }

            $order_status_id = 2;
            $this->model_checkout_order->addHistory(
                $order_id,
                $order_status_id,
                'Cobrança via boleto aguardando confirmação:<br><a href="' . $charge_data['link'] . '" class="btn btn-sm btn-primary mt-2" target="_blank">Visualizar Boleto</a>',
                true
            );

            $data = [
                'link_download_billet' => $charge_data['link']
            ];

            $this->cart->clear();
            unset($this->session->data['order_id']);

            $this->response->addHeader('Content-Type: text/html; charset=UTF-8');
            $this->response->setOutput($this->load->view('extension/efi/payment/efi_script_billet', $data));
        } catch (\Exception $e) {
            $this->logError("Erro no processamento: " . $e->getMessage());

            $data['error'] = $e->getMessage();
            $view = $this->load->view('extension/efi/payment/efi_error_ajax', $data);

            $this->response->addHeader('Content-Type: text/html; charset=UTF-8');
            $this->response->setOutput($view);
        }
    }

    private function logError(string $message): void
    {
        $log = new \Opencart\System\Library\Log('efi.log');
        $log->write($message);
    }
}
