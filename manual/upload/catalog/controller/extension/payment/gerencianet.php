<?php

use Cardinity\Method\Payment\Finalize;

if (version_compare(phpversion(), '5.4.0', '>=')) {
    include_once('gn_lib/constants.php');
    include_once('gn_lib/gatewayPix.php');
    include_once(__DIR__ . '/../../../model/extension/payment/cliente.php');
} else {
    echo "A versão do PHP instalado no servidor não é compatível com o módulo da Gerencianet. Por favor, verifique os requisitos do módulo.";
    die();
}

/**
 * Controlador responsável por gerir a opção de pagamento via gerencianet
 */
class ControllerExtensionPaymentGerencianet extends Controller
{

    /**
     * Redireciona o usuário para a página de pagamento
     */
    public function index()
    {
        $this->load->language('extension/payment/gerencianet');
        $this->load->model('checkout/order');

        $data = array();
        // Verifica se a moeda utilizada é o Real('BRL')
        if ($this->session->data['currency'] != Constants::constantValues['currency']) {
            $messageError = $this->language->get('message_error_gerencianet');

            return "<span><h4>$messageError</h4></span>";
        }
        if ($this->config->get('payment_gerencianet_transparent') == 1) {
            $data['action'] = $this->url->link('extension/payment/gerencianet/finalize');
            $data['button_confirm'] = $this->language->get('button_confirm');
        } else {
            $data['action'] = $this->url->link('extension/payment/gerencianet/finalizegateway');
        }


        // Informações Sobre Simbolos da Moeda
        $data['currency_symbol_left'] = $this->currency->getSymbolLeft($this->session->data['currency']);
        $data['currency_symbol_right'] = $this->currency->getSymbolRight($this->session->data['currency']);

        // Informações do Pedido
        $data['order_id'] = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($data['order_id']);

        $this->descontoPix($data, $order_info);
        $this->descontoOpenFinance($data, $order_info);
        $this->descontoBoleto($data, $order_info);


        //opções de pagamentos a serem inseridas na pagina
        $data['options_payment'] = [
            'boleto' => ['text' => ($this->config->get('payment_gerencianet_boleto_active') == 1) ? 'Boleto Bancário' : false, 'id' => 'gerencianetBoleto', 'img' => 'boleto.png'],
            'cartao' => ['text' => ($this->config->get('payment_gerencianet_cartao_active') == 1) ? 'Cartão de Crédito' : false, 'id' => 'gerencianetCartao', 'img' => 'credit-card-check.png'],
            'pix' => ['text' => ($this->config->get('payment_gerencianet_pix_active') == 1) ? 'Pix' : false, 'id' => 'gerencianetPix', 'img' => 'pix.svg'],
            'openfinance' => ['text' => ($this->config->get('payment_gerencianet_OF_active') == 1) ? 'Open Finance' : false, 'id' => 'gerencianetOF', 'img' => 'openfinance.svg']
        ];
        $codigoConta = $this->config->get('payment_gerencianet_payee_id');
        if ($this->config->get('payment_gerencianet_sandbox') == 1) {
            $data['script_cartao'] = "<script type='text/javascript'>var s=document.createElement('script');s.type='text/javascript';var v=parseInt(Math.random()*1000000);s.src='https://sandbox.gerencianet.com.br/v1/cdn/$codigoConta/'+v;s.async=false;s.id='$codigoConta';if(!document.getElementById('$codigoConta')){document.getElementsByTagName('head')[0].appendChild(s);};\$gn={validForm:true,processed:false,done:{},ready:function(fn){\$gn.done=fn;}};</script>";
        } else {
            $data['script_cartao'] = "<script type='text/javascript'>var s=document.createElement('script');s.type='text/javascript';var v=parseInt(Math.random()*1000000);s.src='https://api.gerencianet.com.br/v1/cdn/$codigoConta/'+v;s.async=false;s.id='$codigoConta';if(!document.getElementById('$codigoConta')){document.getElementsByTagName('head')[0].appendChild(s);};\$gn={validForm:true,processed:false,done:{},ready:function(fn){\$gn.done=fn;}};</script>";
        }
        $data['total_pedido'] = number_format($order_info['total'], 2, ',', '.');
        $cliente = new ModelCliente($order_info);
        $this->setCustomerClient($data, $cliente);

        $data['transparent'] = $this->config->get('payment_gerencianet_transparent') == 1;
        $efi = new GatewayPix($this->getConfigOptionsOF());
        $ofList = $efi->getParticipantsOF();
        $data['banks'] = $this->getOpenFinanceBankData($ofList);

        return $this->load->view('extension/payment/gerencianet', $data);
    }
    /**
     * Direciona para o checkout em dois passos
     * @return array
     */
    public function finalizegateway()
    {
        $this->load->model('checkout/order');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['transparent'] = true;
        $data['options_payment'] = [
            'boleto' => ['text' => ($this->config->get('payment_gerencianet_boleto_active') == 1) ? 'Boleto Bancário' : false, 'id' => 'gerencianetBoleto', 'img' => 'boleto.png'],
            'cartao' => ['text' => ($this->config->get('payment_gerencianet_cartao_active') == 1) ? 'Cartão de Crédito' : false, 'id' => 'gerencianetCartao', 'img' => 'credit-card-check.png'],
            'pix' => ['text' => ($this->config->get('payment_gerencianet_pix_active') == 1) ? 'Pix' : false, 'id' => 'gerencianetPix', 'img' => 'pix.svg'],
            'openfinance' => ['text' => ($this->config->get('payment_gerencianet_OF_active') == 1) ? 'Open Finance' : false, 'id' => 'gerencianetOF', 'img' => 'openfinance.svg']
        ];
        $data['order_id'] = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($data['order_id']);
        $data['total_pedido'] = number_format($order_info['total'], 2, ',', '');
        $this->descontoPix($data, $order_info);
        $this->descontoBoleto($data, $order_info);
        $cliente = new ModelCliente($order_info);
        $this->setCustomerClient($data, $cliente);

        $produtos = $this->model_checkout_order->getOrderProducts($data['order_id']);
        for ($i = 0; $i < count($produtos); $i++) {

            $produtos[$i]['price'] =  str_replace(',', '', number_format($produtos[$i]['price'], 2, '.', ','));
        }
        $data['produtos'] = $produtos;
        if (isset($this->session->data['shipping_method']['cost'])) {
            $data['frete'] = $this->session->data['shipping_method']['cost'];
        }



        $codigoConta = $this->config->get('payment_gerencianet_payee_id');
        if ($this->config->get('payment_gerencianet_sandbox') == 1) {
            $data['script_cartao'] = "<script type='text/javascript'>var s=document.createElement('script');s.type='text/javascript';var v=parseInt(Math.random()*1000000);s.src='https://sandbox.gerencianet.com.br/v1/cdn/$codigoConta/'+v;s.async=false;s.id='$codigoConta';if(!document.getElementById('$codigoConta')){document.getElementsByTagName('head')[0].appendChild(s);};\$gn={validForm:true,processed:false,done:{},ready:function(fn){\$gn.done=fn;}};</script>";
        } else {
            $data['script_cartao'] = "<script type='text/javascript'>var s=document.createElement('script');s.type='text/javascript';var v=parseInt(Math.random()*1000000);s.src='https://api.gerencianet.com.br/v1/cdn/$codigoConta/'+v;s.async=false;s.id='$codigoConta';if(!document.getElementById('$codigoConta')){document.getElementsByTagName('head')[0].appendChild(s);};\$gn={validForm:true,processed:false,done:{},ready:function(fn){\$gn.done=fn;}};</script>";
        }
        $efi = new GatewayPix($this->getConfigOptionsOF());
        $ofList = $efi->getParticipantsOF();
        $data['banks'] = $this->getOpenFinanceBankData($ofList);

        $data['action'] = $this->url->link('extension/payment/gerencianet/finalize');

        // Informações Sobre Simbolos da Moeda
        $data['currency_symbol_left'] = $this->currency->getSymbolLeft($this->session->data['currency']);
        $data['currency_symbol_right'] = $this->currency->getSymbolRight($this->session->data['currency']);

        return $this->response->setOutput($this->load->view('extension/payment/finalizeoptions', $data));
    }
    /**
     * Direciona para a tela de pagamento 
     * @return array
     */
    public function finalize()
    {
        switch ($this->request->post['paymentOption']) {
            case 'gerencianetPix':
                $this->finalizePix();
                break;
            case 'gerencianetBoleto':
                $this->finalizeBoleto();
                break;
            case 'gerencianetCartao':
                $this->finalizeCartao();
                break;
            case 'gerencianetOF':
                $this->finalizeOpenFinance();
                break;

            default:
                break;
        }
    }
    private function setCustomerClient(&$data, $cliente)
    {
        $data['customerName'] =  $cliente->getNome();
        $data['customerEmail'] =  $cliente->getEmail();
        $data['customerTelefone'] =  $cliente->getTelefone();
        $data['customerRua'] =  $cliente->getRua();
        $data['customerNumero'] =  $cliente->getNumero();
        $data['customerBairro'] =  $cliente->getBairro();
        $data['customerCidade'] =  $cliente->getCidade();
        $data['customerCep'] =  $cliente->getCep();
    }
    /**
     * Exibe o QR CODE e Chave PIX do pagamento
     * @return array
     */
    private function finalizePix()
    {
        $this->load->language('extension/payment/gerencianet');
        $this->load->model('checkout/order');

        // Estilo personalizado
        $this->document->addScript('catalog/view/javascript/payment/script.js');
        $this->document->addStyle('catalog/view/theme/default/stylesheet/payment/style.css');
        

        $data = array();

        // Informações do Pedido
        $data['order_id'] = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($data['order_id']);
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        // Gera uma nova cobrança
        $qrCode = $this->createCharge($order_info['total'], $data['order_id']);
        $data['qrCodeImage'] = $qrCode['imagemQrcode'];
        $data['qrCodeCopy']  = $qrCode['qrcode'];
        // Limpa o carrinho
        $this->cart->clear();

        return $this->response->setOutput($this->load->view('extension/payment/finalizepix', $data));
    }

    /**
     * Exibe o boleto para pagamento
     * @return array
     */
    private function finalizeBoleto()
    {
        $this->load->language('extension/payment/gerencianet');
        $this->load->model('checkout/order');
        // Estilo personalizado

        $this->document->addStyle('catalog/view/theme/default/stylesheet/payment/boleto/finalizeSuccess.css');

        $data = array();

        // Informações do Pedido
        $data['order_id'] = $this->session->data['order_id'];
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $frete = 0;
        // Gera uma nova cobrança


        if (isset($this->session->data['shipping_method']['cost'])) {
            $frete = $this->totalValueWithDiscount($this->session->data['shipping_method']['cost'], $this->config->get("payment_gerencianet_boleto_desconto"));
        }

        $produtos = $this->model_checkout_order->getOrderProducts($data['order_id']);
        $order_info = $this->model_checkout_order->getOrder($data['order_id']);
        $boleto_pdf = $this->createChargeTwoSteps($order_info, $produtos, $frete, 1);
        if (isset($boleto_pdf['code']) && $boleto_pdf['code'] == 200) {
            $data['billet_pdf'] = $boleto_pdf['pdf'];
            $data['billet_copia_e_cola'] = $boleto_pdf['copia_e_cola'];
            $data['billet_pix_imagem'] = $boleto_pdf['imagem_pix'];
            $data['billet_barcode'] = $boleto_pdf['barcode'];
            // Limpa o carrinho
            $this->cart->clear();
        } else {
            $data['erros'] = $boleto_pdf;
        }


        return $this->response->setOutput($this->load->view('extension/payment/finalizeBillet', $data));
    }

    /**
     * Exibe mensagem de sucesso no pagamento
     * @return array
     */
    private function finalizeOpenFinance()
    {
        $this->load->language('extension/payment/gerencianet');
        $this->document->addStyle('catalog/view/theme/default/stylesheet/payment/cartao/finalizeSuccess.css');
        $this->load->model('checkout/order');

        // Estilo personalizado
        $this->document->addStyle('catalog/view/theme/default/stylesheet/payment/style.css');

        $data = array();

        // Informações do Pedido
        $data['order_id'] = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($data['order_id']);
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');



        $payment = $this->generateOpenFinancePayment($order_info);


        if ($payment['code'] == 200) {
            $this->changeStatusOrder($order_info['order_id'], $this->config->get(Constants::orderStatus['PENDING']));
            $data = [
                'order_id' => $order_info['order_id'],
                'identificadorPagamento' => $payment['identificadorPagamento'],
                'status' => $this->config->get(Constants::orderStatus['PENDING'])
            ];
            $this->model_extension_payment_gerencianet->insert($data, 'gerencianet_of');
            $this->cart->clear();
            return $this->response->redirect($payment['redirectURI']);
        } else {
            $data['erros'] = $payment;
            return $this->response->setOutput($this->load->view('extension/payment/efi_bank_erros', $data));
        }
        
    }

    /**
     * Exibe mensagem de sucesso no pagamento
     * @return array
     */
    private function finalizeCartao()
    {
        $this->load->language('extension/payment/gerencianet');
        $this->document->addStyle('catalog/view/theme/default/stylesheet/payment/cartao/finalizeSuccess.css');
        $this->load->model('checkout/order');

        // Estilo personalizado
        $this->document->addStyle('catalog/view/theme/default/stylesheet/payment/style.css');

        $data = array();

        // Informações do Pedido
        $data['order_id'] = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($data['order_id']);
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        // Gera uma nova cobrança
        if (isset($this->session->data['shipping_method']['cost'])) {
            $frete = $this->totalValueWithDiscount($this->session->data['shipping_method']['cost'], $this->config->get("payment_gerencianet_boleto_desconto"));
        } else {
            $frete = 0;
        }
        $produtos = $this->model_checkout_order->getOrderProducts($data['order_id']);
        $order_info = $this->model_checkout_order->getOrder($data['order_id']);
        $response = $this->createChargeTwoSteps($order_info, $produtos, $frete, 2);

        // Limpa o carrinho

        if (isset($response['code']) && $response['code'] == 200) {
            $this->cart->clear();
            $data['status'] =  $response['code'] == 200;
        } else {
            $data['erros'] = $response;
        }
        return $this->response->setOutput($this->load->view('extension/payment/finalizecard', $data));
    }

    /**
     * Cria uma nova cobrança Pix
     * @return array $qrCode['image'] e $qrCode['qrcode']
     */
    private function createCharge($orderTotal, $order_id)
    {
        // Dependências de persistência personalizado
        $this->load->model('extension/payment/gerencianet');

        $dataConfig = $this->getConfigOptionsPix();
        $gatewayPix = new GatewayPix($dataConfig);

        // Busca no banco se já existe uma cobrança criada
        $dataDB = $this->model_extension_payment_gerencianet->find('order_id', $order_id, 'gerencianet');

        if (isset($dataDB['tx_id'])) {
            // Retorna apenas o QRcode
            $qrcode = $gatewayPix->generateQRCode($dataDB['loc_id']);
        } else {
            // Calcula o valor total com desconto
            $totalValueWithDiscount = $this->totalValueWithDiscount($orderTotal, $dataConfig['pixDiscount']);

            $body = [
                "calendario" => [
                    "expiracao" =>  $dataConfig['pixHours'] * 3600
                ],
                "valor" => [
                    "original" => $totalValueWithDiscount
                ],
                "chave" => $dataConfig['pixKey']
            ];
            // Gera uma nova cobrança
            $apiResponse = $gatewayPix->createCharge($body);

            if ($apiResponse['txid']) {
                // Id da location enviada pela API
                $locationId = $apiResponse['loc']['id'];
                $qrcode = $gatewayPix->generateQRCode($locationId);

                // Salva a cobrança no BD
                $data = [
                    'order_id' => $order_id,
                    'tx_id' => $apiResponse['txid'],
                    'loc_id' => $locationId,
                    'status' => $this->config->get(Constants::orderStatus['PENDING'])
                ];
                $this->model_extension_payment_gerencianet->insert($data, 'gerencianet');

                // Muda o status do pedido no OpenCart
                $this->changeStatusOrder($order_id, $this->config->get(Constants::orderStatus['PENDING']));
            }
        }

        return $qrcode;
    }
    /**
     * Cria uma nova cobrança (boleto ou cartao)
     * @return array $charge_id
     */
    private function createChargeTwoSteps($order_info, $order_products, $shipping, $method)
    {
        // Dependências de persistência personalizado
        $this->load->model('extension/payment/gerencianet');
        $order_id = $this->session->data['order_id'];
        $dataConfig = $this->getConfigOptionsCharge();
        $gatewayPix = new GatewayPix($dataConfig);
        if ($method == 1) {
            $order_info['diasVencimento'] = $this->config->get('payment_gerencianet_boleto_vencimento');
            $order_info['observacaoBoleto'] = $this->config->get('payment_gerencianet_boleto_observacoes');
            $order_info['multa'] = $this->config->get('payment_gerencianet_boleto_multa');
            $order_info['juros'] = $this->config->get('payment_gerencianet_boleto_juros');
            $order_info['desconto'] = $this->config->get('payment_gerencianet_boleto_desconto');
            $order_info['emailCobranca'] = $this->config->get('payment_gerencianet_boleto_email_cobranca');
        }

        $url =  str_replace('/catalog', '', $this->url->link('extension/payment/gerencianet/callback/charge'));
        $order_info['url_callback'] = $url;
        $res = $gatewayPix->createChargeTwoSteps($order_info, $order_products, $shipping, $this->request->post, $method);
        if (isset($res['code']) && $res['code'] == 200) {
            $this->changeStatusOrder($order_id, $this->config->get(Constants::orderStatus['PENDING']));
        }

        return $res;
    }

    /**
     * Retorna o valor total da cobrança com o desconto
     * @param float $orderTotal
     * @param string $pixDiscount
     * 
     * @return float
     */
    private function totalValueWithDiscount($orderTotal, $pixDiscount)
    {

        $percentageDiscount = floatval(preg_replace('/[^0-9.]/', '', str_replace(",", ".", $pixDiscount)));
        $totalWithDiscount = floatval($orderTotal - ($orderTotal * ($percentageDiscount / 100)));

        // Formata o numero em 2 casas decimais
        return number_format($totalWithDiscount, 2, '.', '');
    }

    /**
     * Retorna as configurações do usuário
     * @return array
     */
    private function getConfigOptionsPix()
    {
        $option = Constants::configOptionsPix;

        $dataPix = array();
        foreach ($option as $key => $value) {
            //recebendo as configurações salvas no Admin
            $dataPix[$key] =  $this->config->get($value);
        }

        return $dataPix;
    }
    private function getConfigOptionsOF()
    {
        $option = Constants::configOptionsOF;
        $dataOF = array();
        foreach ($option as $key => $value) {
            $dataOF[$key] =  $this->config->get($value);
        }
        return $dataOF;
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






    /**
     * Calcula o desconto do Pix
     */
    function descontoOpenFinance(&$data, $order_info)
    {
        // Valor do Desconto em %
        $data['discount_of'] = $this->config->get('payment_gerencianet_OF_discount');

        // Valor Total do Pedido com Desconto Aplicado
        $discount = $this->totalValueWithDiscount($order_info['total'], $data['discount_of']);
        $data['discount_total_of'] = number_format($discount, 2, ',', ' ');
    }
    /**
     * Calcula o desconto do Pix
     */
    function descontoPix(&$data, $order_info)
    {
        // Valor do Desconto em %
        $data['discount_pix'] = $this->config->get('payment_gerencianet_discount');

        // Valor Total do Pedido com Desconto Aplicado
        $discount = $this->totalValueWithDiscount($order_info['total'], $data['discount_pix']);
        $data['discount_total_pix'] = number_format($discount, 2, ',', ' ');
    }
    /**
     * Calcula o desconto do Boleto
     */
    function descontoBoleto(&$data, $order_info)
    {
        // Valor do Desconto em %
        $data['discount_billet'] = $this->config->get('payment_gerencianet_boleto_desconto');

        // Valor Total do Pedido com Desconto Aplicado
        $discount = $this->totalValueWithDiscount($order_info['total'], $data['discount_billet']);
        $data['discount_total_billet'] = number_format($discount, 2, ',', ' ');
    }
    public function callback()
    {
        header('HTTP/1.0 200 OK');
    }
    public function getOpenFinanceBankData($ofList)
    {
        $dadosBancosParticipantes = array();

        // Loop pelos participantes e extrair os dados
        foreach ($ofList as $participant) {
            $dadosBancosParticipantes[] = $this->extractParticipantData($participant);
        }
        return $dadosBancosParticipantes;
    }
    public function extractParticipantData($participant)
    {
        return array(
            'id' => $participant['identificador'],
            'name' => $participant['nome'],
            'img' => $participant['logo']
        );
    }
    private function generateOpenFinancePayment($order_info)
    {
       
        $this->load->model('extension/payment/gerencianet');

        $dataConfig = $this->getConfigOptionsOF();
        $gateway = new GatewayPix($dataConfig);
        $total = is_null($dataConfig['OFDiscount']) ?  strval(number_format($order_info['total'], 2)):  $this->totalValueWithDiscount($order_info['total'],$dataConfig['OFDiscount']);
        
        $body = [];
        $body['pagador'] = [
            'idParticipante' => $this->request->post['bancoOF'],
            'cpf' => str_replace(array('.', '-', '/'), "", $this->request->post['cpfOF']),
        ];

        if (isset($this->request->post['cnpjOF']) && $this->request->post['cnpjOF'] != '') {
            $body['pagador']['cnpj'] = str_replace(array('.', '-', '/'), "", $this->request->post['cnpjOF']);
        }
        $body['favorecido']['contaBanco'] = [
            'codigoBanco' => '09089356',
            'nome'        => $dataConfig['nome'],
            'documento'   => $dataConfig['documento'],
            'conta'       => $dataConfig['conta'],
            'tipoConta'   => $dataConfig['tipoConta'],
            'agencia'     => $dataConfig['agencia']

        ];
        $body['valor'] = $total ;
        $body['idProprio'] = strval($order_info['order_id']);
    
        $apiReturnInitPayment = $gateway->startOpenFinancePayment($body);

       return $apiReturnInitPayment;
    }
}
