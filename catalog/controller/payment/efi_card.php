<?php

namespace Opencart\Catalog\Controller\Extension\Efi\Payment;

class EfiCard extends \Opencart\System\Engine\Controller
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
                'phone'    => $this->request->post['payment_efi_customer_phone'] ?? ''
            ];

            $card = [
                'token'        => $this->request->post['payment_efi_customer_card_payment_token'] ?? '',
                'installments' => $this->request->post['payment_efi_customer_card_installments'] ?? 1
            ];

            if (empty($customer['name']) || empty($customer['document']) || empty($card['token'])) {
                $this->logError(json_encode($customer));
                $this->logError(json_encode($card));
                throw new \Exception('Dados obrigatórios ausentes.');
            }

            $order_status_id = 2;
            $this->load->model('extension/efi/payment/card/efi_card');

            if ((int)$order_info['order_status_id'] !== $order_status_id) {
                // Envia order_info completo para a model
                $charge_data = $this->model_extension_efi_payment_card_efi_card
                    ->generateCardCharge($customer, $card, $amount, $order_id, $settings, $order_info);

                if (!$charge_data['success']) {
                    throw new \Exception($charge_data['error']);
                }

                $data = [
                    'message'   => $charge_data['message'],
                    'charge_id' => $charge_data['charge_id'],
                    'status'    => $charge_data['status']
                ];

                $this->model_checkout_order->addHistory(
                    $order_id,
                    $order_status_id,
                    'Cobrança via cartão aguardando confirmação:' . $charge_data['charge_id'],
                    false
                );
            } else {
                // Busca histórico direto do banco
                $histories = $this->getOrderHistories($order_id, 0, 100);

                $previous_charge_id = null;
                foreach ($histories as $history) {
                    if (
                        (int)$history['order_status_id'] === $order_status_id &&
                        strpos($history['comment'], 'Cobrança via cartão aguardando confirmação:') === 0
                    ) {
                        $previous_charge_id = trim(str_replace('Cobrança via cartão aguardando confirmação:', '', $history['comment']));
                        break;
                    }
                }

                if (!$previous_charge_id) {
                    throw new \Exception('Não foi possível recuperar a cobrança anterior para retentativa.');
                }

                // Chama o retry
                $retry_data = $this->model_extension_efi_payment_card_efi_card->cardPaymentRetry(
                    $previous_charge_id,
                    $customer,
                    $card['token'],
                    $settings
                );

                $charge_data = [
                    'message'   => $retry_data['message'] ?? '',
                    'charge_id' => $previous_charge_id,
                    'status'    => $retry_data['status'] ?? null
                ];
            }

            if ($charge_data['status'] === 'approved') {
                $this->model_checkout_order->addHistory(
                    $order_id,
                    $settings['payment_efi_order_status_paid'],
                    'Pagamento confirmado via cartão de crédito.',
                    true
                );

                $data['success'] = true;
                $data['redirect'] = html_entity_decode(
                    $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true),
                    ENT_QUOTES,
                    'UTF-8'
                );
            } else {
                $data['success'] = false;
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($data));
        } catch (\Exception $e) {
            $this->logError("Erro no processamento: " . $e->getMessage());
            $data['error'] = $e->getMessage();
            $view = $this->load->view('extension/efi/payment/efi_error_ajax', $data);

            $this->response->addHeader('Content-Type: text/html; charset=UTF-8');
            $this->response->setOutput($view);
        }
    }

    /**
     * Recupera o histórico de status do pedido diretamente do banco de dados.
     *
     * @param int $order_id
     * @param int $start
     * @param int $limit
     * @return array
     */
    private function getOrderHistories($order_id, $start = 0, $limit = 100): array
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "order_history` WHERE `order_id` = '" . (int)$order_id . "' ORDER BY `date_added` DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int)$start . "," . (int)$limit;
        }
        $query = $this->db->query($sql);
        return $query->rows;
    }

    private function logError(string $message): void
    {
        $log = new \Opencart\System\Library\Log('efi_card.log');
        $log->write($message);
    }
}
