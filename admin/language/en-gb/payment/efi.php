<?php
// Heading
$_['heading_title']      = 'Efí Bank';

// Text
$_['text_extension']     = 'Extensões';
$_['text_success']       = 'Configurações alteradas com sucesso!';
$_['text_edit']          = 'Configurações do plugin';
$_['text_required']      = 'Configurações gerais';
$_['text_pix']           = 'Pix';
$_['text_billet']        = 'Boleto';
$_['text_credit_card']   = 'Cartão de Crédito';
$_['text_open_finance']  = 'Open Finance';
$_['text_status']        = 'Configuração de status';

// Entry Requireds
$_['entry_required_client_id_production']     = 'Client_Id Produção';
$_['entry_required_client_secret_production'] = 'Client_Secret Produção';
$_['entry_required_client_id_sandbox']        = 'Client_Id Desenvolvimento';
$_['entry_required_client_secret_sandbox']    = 'Client_Secret Desenvolvimento';
$_['entry_required_account_code']             = 'Identificador da conta';
$_['entry_required_enviroment']               = 'Ativar ambiente de teste';
$_['entry_required_status']                   = 'Ativar Plugin';
$_['entry_required_sort_order']               = 'Ordem de Exibição';
$_['entry_required_status_order_status']      = 'Status do pedido ao finalizar o pagamento';

// Tooltips Requireds
$_['tooltip_required_client_id_production']     = 'Para mais informações, acesse: https://dev.efipay.com.br/docs/api-cobrancas/credenciais#criar-uma-aplica%C3%A7%C3%A3o-ou-configurar-uma-j%C3%A1-existente';
$_['tooltip_required_client_secret_production'] = $_['tooltip_required_client_id_production'];
$_['tooltip_required_client_id_sandbox']        = $_['tooltip_required_client_id_production'];
$_['tooltip_required_client_secret_sandbox']    = $_['tooltip_required_client_id_production'];
$_['tooltip_required_account_code']             = $_['tooltip_required_client_id_production'];
$_['tooltip_required_sort_order']               = 'Ordem de exibição no checkout.';
$_['tooltip_required_status_order_status']      = 'Defina qual status o pedido deve assumir quando o pagamento for confirmado.';

// Entry Pix
$_['entry_pix_key']         = 'Chave Pix';
$_['entry_pix_expire_at']   = 'Tempo de expiração da cobrança';
$_['entry_pix_discount']    = 'Desconto';
$_['entry_pix_certificate'] = 'Certificado';
$_['entry_pix_mtls']        = 'Validar mTLS';
$_['entry_pix_status']      = 'Ativar';

// Tooltips Pix
$_['tooltip_pix_key']         = 'Informe a chave Pix cadastrada no aplicativo da Efí.';
$_['tooltip_pix_expire_at']   = 'Defina por quanto tempo (em horas) a cobrança ficará disponível para pagamento.';
$_['tooltip_pix_discount']    = 'Informe um valor de desconto. Sem o símbolo de %, o valor será tratado como um desconto fixo em moeda.';
$_['tooltip_pix_certificate'] = 'Certificado gerado em sua conta Efí. Saiba mais em: https://dev.efipay.com.br/docs/api-pix/credenciais#gerando-um-certificado-p12';
$_['tooltip_pix_mtls']        = 'Para detalhes sobre mTLS e sua obrigatoriedade, veja: https://dev.efipay.com.br/docs/api-pix/webhooks#entendendo-o-padr%C3%A3o-mtls';

// Entry Billet
$_['entry_billet_expire_at'] = 'Dias para vencimento do boleto';
$_['entry_billet_discount']  = 'Desconto';
$_['entry_billet_email']     = 'Enviar e-mail para o cliente final';
$_['entry_billet_fine']      = 'Configuração de multa';
$_['entry_billet_interest']  = 'Configuração de juros';
$_['entry_billet_message']   = 'Mensagem no boleto';
$_['entry_billet_status']    = 'Ativar';

// Tooltips Billet
$_['tooltip_billet_expire_at'] = 'Quantidade de dias para o vencimento após emissão do boleto.';
$_['tooltip_billet_discount']  = 'Informe um valor de desconto. Sem o símbolo de %, o valor será tratado como um desconto fixo em moeda.';
$_['tooltip_billet_email']     = 'Ative essa opção se deseja que a Efí envie o boleto por e-mail ao cliente.';
$_['tooltip_billet_fine']      = 'Multa após o vencimento. Exemplo: 200 representa 2%. Mínimo de 1 e máximo de 1000.';
$_['tooltip_billet_interest']  = 'Juros após vencimento. Exemplo: 33 representa 0,033% por dia.';
$_['tooltip_billet_message']   = 'Mensagem opcional que será impressa no boleto do cliente.';

// Entry Credit Card
$_['entry_credit_card_status'] = 'Ativar';

// Entry Open Finance
$_['entry_open_finance_key']         = 'Chave Pix para recebimento';
$_['entry_open_finance_discount']    = 'Desconto';
$_['entry_open_finance_certificate'] = 'Certificado';
$_['entry_open_finance_status']      = 'Ativar';

// Tooltips Open Finance
$_['tooltip_open_finance_key']         = 'Informe a chave Pix da conta que receberá os pagamentos.';
$_['tooltip_open_finance_discount']    = 'Informe um valor de desconto. Sem o símbolo de %, o valor será tratado como um desconto fixo em moeda.';
$_['tooltip_open_finance_certificate'] = $_['tooltip_pix_certificate'];

// Entry Status
$_['entry_status_order_status'] = 'Status do pedido ao finalizar o pagamento';

// Error
$_['error_permission'] = 'Atenção: Você não tem permissão para alterar esse plugin!';
