<?php

use Cardinity\Method\Payment\Finalize;

if (version_compare(phpversion(), '5.4.0', '>=')) {
    include_once( __DIR__ . '/../../gn_lib/constants.php');
    include_once( __DIR__  .'/../../gn_lib/gatewayPix.php');
} else {
    echo "A versão do PHP instalado no servidor não é compatível com o módulo da Gerencianet. Por favor, verifique os requisitos do módulo.";
    die();
}

/**
 * Controlador responsável por gerir a opção de pagamento via gerencianet
 */
class ControllerExtensionPaymentGerencianetCallbackPix extends Controller
{

    /**
     * Redireciona o usuário para a página de pagamento
     */
    public function index()
    {
        $this->load->model('extension/payment/gerencianet');

        // Hook data retrieving
        @ob_clean();
        $postData = json_decode(file_get_contents('php://input'));
        

        // Hook validation
        if (isset($postData->evento) && isset($postData->data_criacao)) {
            header('HTTP/1.0 200 OK');
            exit();
        }
            
            $pixPaymentData = isset($postData->pix) ? $postData->pix : null;
            $this->log->write('Efi:' . json_encode($pixPaymentData) );
            // Hook manipulation
            if (empty($pixPaymentData)) {
                $this->log->write('Efi: Pagamento Pix não recebido pelo Webhook.');
                exit();
            } else {
                header('HTTP/1.0 200 OK');

                $txID  = $pixPaymentData[0]->txid;
                $e2eID = $pixPaymentData[0]->endToEndId;

                // Recebe o id do Status
                $statusComplete = $this->config->get(Constants::orderStatus['COMPLETE']);

                $conditions = [
                    'tx_id' => $txID
                ];
                $data = [
                    'e2e_id' => $e2eID,
                    'status' => $statusComplete
                ];

                // Salva o EndToEndId no Banco
                $this->model_extension_payment_gerencianet->update($conditions, $data, 'gerencianet');

                // Busca no banco o id do Pedido
                $column = 'tx_id';
                $dataDB = $this->model_extension_payment_gerencianet->find($column, $txID,'gerencianet');

                // Muda o status do pedido no OpenCart
                $this->changeStatusOrder($dataDB['order_id'], $statusComplete);
            }
        
       
    }

    /**
     * Muda o status do pedido
     * @param string $status
     * @param string $order_id 
     */
    private function changeStatusOrder($order_id, $id_status)
    {

        $this->load->model('checkout/order');


        $this->model_checkout_order->addOrderHistory($order_id, intval($id_status));
    }
    
    

    

    

    
   
    



    

    
}
