<?php

namespace Opencart\Catalog\Controller\Extension\Efi\Payment;

class EfiOpenFinance extends \Opencart\System\Engine\Controller
{
    public function confirm(): void
    {
        try {
            $this->validarRequisicaoPost();

            $customer_document_cpf  = $this->request->post['payment_efi_open_finance_customer_cpf'] ?? '';
            $customer_document_cnpj = $this->request->post['payment_efi_open_finance_customer_cnpj'] ?? '';
            $customer_bank          = $this->request->post['payment_efi_open_finance_customer_bank'] ?? '';
            $order_id               = $this->session->data['order_id'] ?? 0;

            $this->logEfi("Processando pedido ID: $order_id");

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);

            if (!$order_info) {
                throw new \Exception('Pedido não encontrado.');
            }

            $amount = $this->cart->getTotal();
            [$openFinanceModel, $settings] = $this->loadOpenFinanceDependencies();

            // Agora enviando também o $order_info
            $openFinanceData = $openFinanceModel->generatePayment(
                $customer_document_cpf,
                $customer_document_cnpj,
                $customer_bank,
                $amount,
                $order_id,
                $settings,
                $order_info
            );

            if (!$openFinanceData['success']) {
                throw new \Exception($openFinanceData['error']);
            }

            $order_status_id = 2;
            $redirectUrl     = $openFinanceData['redirect_url'];

            $comment = 'Pagamento via Open Finance iniciado:<br><a href="' . $redirectUrl . '" class="btn btn-sm btn-primary mt-2" target="_blank">Autorizar no banco</a>';
            $this->model_checkout_order->addHistory($order_id, $order_status_id, $comment, true);

            $this->cart->clear();
            unset($this->session->data['order_id']);

            $data = [
                'success'  => true,
                'message'  => 'Redirecionando para banco...',
                'redirect' => $redirectUrl
            ];

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($data));
        } catch (\Exception $e) {
            $this->logEfi("Erro no processamento Open Finance: " . $e->getMessage());

            $data['error']  = $e->getMessage();
            $data['header'] = $this->load->controller('common/header');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->addHeader('Content-Type: text/html; charset=UTF-8');
            $this->response->setOutput(
                $this->load->view('extension/efi/payment/efi_error_ajax', $data)
            );
        }
    }

    public function detailopenfinance(): void
    {
        try {
            $identificadorPagamento = $this->request->get['identificadorPagamento'] ?? '';

            if (empty($identificadorPagamento)) {
                throw new \Exception('Identificador de pagamento não encontrado.');
            }

            [$openFinanceModel, $settings] = $this->loadOpenFinanceDependencies();
            $detail = $openFinanceModel->getDetailPayment($identificadorPagamento, $settings);

            $data['status']   = $detail['status'];
            $data['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'));

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($data));
        } catch (\Exception $e) {
            $this->logEfi("Erro na verificação Open Finance: " . $e->getMessage());

            $this->response->clearHeaders();
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]));
        }
    }

    private function logEfi(string $message): void
    {
        $log = new \Opencart\System\Library\Log('efi_open_finance.log');
        $log->write($message);
    }

    private function loadOpenFinanceDependencies(): array
    {
        $this->load->model('setting/setting');
        $this->load->model('extension/efi/payment/openfinance/efi_open_finance');

        return [
            $this->model_extension_efi_payment_openfinance_efi_open_finance,
            $this->model_setting_setting->getSetting('payment_efi')
        ];
    }

    private function validarRequisicaoPost(): void
    {
        if (!$this->request->server['REQUEST_METHOD'] || strtoupper($this->request->server['REQUEST_METHOD']) !== 'POST') {
            throw new \Exception('Método inválido.');
        }
    }
}
