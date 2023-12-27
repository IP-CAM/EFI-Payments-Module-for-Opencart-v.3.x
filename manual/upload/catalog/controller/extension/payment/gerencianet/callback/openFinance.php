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
class ControllerExtensionPaymentGerencianetCallbackOpenFinance extends Controller
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
        header('HTTP/1.0 200 OK');

        // Hook validation
        if (!isset($postData->identificadorPagamento) ) {
            echo "webhook processado com sucesso";
        }else{
            $this->log->write('Efi Open Finance:' . json_encode($postData) );
            $tipo = $postData->tipo;
            $identificadorPagamento = $postData->identificadorPagamento;
            $status = $postData->status;
            $idProprio = $postData->idProprio;
            $e2eID = $postData->endToEndId;
            if($tipo == 'pagamento' & $status == 'aceito' ){
                $statusComplete = $this->config->get(Constants::orderStatus['COMPLETE']);

                $conditions = [
                    'identificadorPagamento' => $identificadorPagamento
                ];
                $data = [
                    'e2e_id' => $e2eID,
                    'status' => $statusComplete
                ];
    
                // Salva o EndToEndId no Banco
                $this->model_extension_payment_gerencianet->update($conditions, $data,'gerencianet_of');
    
               
    
                // Muda o status do pedido no OpenCart
                $this->changeStatusOrder($idProprio, $statusComplete);
            }

           
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
