<?php

class ControllerExtensionPaymentGerencianetOpenFinanceRedirect extends Controller
{
    public function index()
    {
        $this->load->model('extension/payment/gerencianet');
        
        $this->document->addStyle('catalog/view/theme/default/stylesheet/payment/open-finance/redirect.css');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['system_url'] = $this->config->get('config_url');

        if(isset($this->request->get['identificadorPagamento'])){
           
            $data['identificadorPagamento'] = $this->request->get['identificadorPagamento'];

        }else{
            $data['erro'] = $this->request->get['erro'];
        }
        
        
        return $this->response->setOutput($this->load->view('extension/payment/finalize_open_finance', $data));
    } 
}