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
class ControllerExtensionPaymentGerencianetCallbackCharge extends Controller
{

    /**
     * Redireciona o usuário para a página de pagamento
     */
    public function index()
    {
        header('HTTP/1.0 200 OK');
        $token =  $this->request->post['notification'];
        $dataConfig = $this->getConfigOptionsCharge();
        $gatewayPix = new GatewayPix($dataConfig);
        $dadosCharge = $gatewayPix->getCharge($token);
        $statusComplete = $this->config->get(Constants::orderStatus['COMPLETE']);


        if ($dadosCharge['status'] == 'paid') {
            $this->changeStatusOrder($dadosCharge['custom_id'], $statusComplete);
        }
       
    }
    private function getConfigOptionsCharge()
    {
        $option = Constants::configOptionsCharge;

        $dataCharge = array();
        foreach ($option as $key => $value) {
            //recebendo as configurações salvas no Admin
            $dataCharge[$key] =  $this->config->get($value);
        }
        $dataCharge['timeout'] = 60;
        return $dataCharge;
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
