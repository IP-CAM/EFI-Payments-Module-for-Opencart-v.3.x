<?php

namespace Opencart\Catalog\Controller\Extension\Efi\Payment;

require_once DIR_EXTENSION . 'efi/library/vendor/autoload.php';


use Efi\EfiPay;
use Efi\Exception\EfiException;
use Opencart\Extension\Efi\Library\EfiConfigHelper;
use Opencart\System\Library\Log;

/**
 * Controlador responsável por processar notificações (webhooks) de cobranças via Efi.
 */
class EfiChargeNotification extends \Opencart\System\Engine\Controller
{
    /**
     * @var Log
     */
    private Log $log;

    /**
     * Construtor da classe.
     *
     * @param \Opencart\System\Engine\Registry $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->log = new Log('efi_charge_notification.log');
    }

    /**
     * Endpoint principal que recebe e processa notificações da API da Efi.
     *
     * @return void
     */
    public function index(): void
    {
        $this->response->addHeader('Content-Type: application/json');

        try {
            if (!isset($_POST['notification'])) {
                $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 400 Bad Request');
                $this->response->setOutput('Parâmetro "notification" ausente.');
                return;
            }

            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('payment_efi');
            $options = EfiConfigHelper::getEfiConfig($settings);

            $notificationToken = $_POST['notification'];
            $params = ['token' => $notificationToken];

            $api = new EfiPay($options);
            $response = $api->getNotification($params);

            $lastNotification = end($response['data']);
            $order_id = $lastNotification['custom_id'] ?? null;
            $status = $lastNotification['status']['current'] ?? null;

            if ($order_id && $status) {
                $this->setOrderStatus($status, $order_id);
                $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 200 OK');
                $this->response->setOutput('Notificação processada com sucesso.');
            } else {
                $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity');
                $this->response->setOutput('Dados da notificação incompletos.');
            }
        } catch (\Exception $e) {
            $this->log->write("Erro ao processar notificação: " . $e->getMessage());
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
            $this->response->setOutput('Erro interno no processamento da notificação.');
        }
    }

    /**
     * Define o status do pedido com base no status recebido da Efi.
     *
     * @param string $status   Status da cobrança (ex: 'paid', 'settled')
     * @param string $order_id ID do pedido (custom_id enviado na criação da cobrança)
     *
     * @return void
     */
    private function setOrderStatus(string $status, string $order_id): void
    {
        $this->load->model('checkout/order');
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('payment_efi');

        switch ($status) {
            case 'paid':
                $this->model_checkout_order->addHistory(
                    $order_id,
                    $settings['payment_efi_order_status_paid'],
                    'Pagamento confirmado via boleto bancário.',
                    true
                );
                break;

            case 'settled':
                $this->model_checkout_order->addHistory(
                    $order_id,
                    $settings['payment_efi_order_status_paid'],
                    'Pagamento confirmado por ação da loja.',
                    true
                );
                break;
        }
    }
}
