<?php
class ControllerExtensionPaymentGerencianetAjaxOFPaymentStatusAjaxHandler extends Controller
{

    public function index()
    {

        $this->load->model('extension/payment/gerencianet');

        $dataOrder = $this->model_extension_payment_gerencianet->find('identificadorPagamento', $this->request->get['identificadorPagamento'], 'gerencianet_of');
        $pagamento['paid'] = false;

        if (isset($dataOrder['e2e_id']) && $dataOrder['e2e_id'] != null){
            $pagamento['paid'] = true;
            $pagamento['idFatura'] = $dataOrder['order_id'];

        }
            


        echo json_encode($pagamento);
    }
}
