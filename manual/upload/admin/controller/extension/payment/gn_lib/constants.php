<?php

/**
 * Lista de constants da extensão
 */
abstract class Constants {

    /**
     * Lista de campos do admin, separados por tabs
     */
    public const adminFields = array(
        'general' => array(
            'payment_gerencianet_prod_client_id' => array('required' => true),
            'payment_gerencianet_prod_client_secret' => array('required' => true),
            'payment_gerencianet_dev_client_id' => array('required' => true),
            'payment_gerencianet_dev_client_secret' => array('required' => true),
            'payment_gerencianet_payee_id' => array('required' => true),
            'payment_gerencianet_certificate' => array('required' => false, 'tooltip' => 'certificate_info','type'=>'file', 'obrigatorio'=> false),
            'payment_gerencianet_transparent' => array('required' => false, 'type' => 'checkbox','tooltip'=> 'transparent'),
            'payment_gerencianet_sandbox' => array('required' => false, 'type' => 'checkbox'),
            'payment_gerencianet_debug' => array('required' => false, 'type' => 'checkbox'),
            'payment_gerencianet_status' => array('required' => false, 'type' => 'checkbox', 'tooltip' => 'status_info')
        ),
        'pix' => array(
            'payment_gerencianet_pix_key' => array('required' => false,'tooltip' => 'pix_key_info', 'obrigatorio'=> true),
            'payment_gerencianet_discount' => array('required' => false, 'tooltip' => 'discount_info', 'obrigatorio'=> false),
            'payment_gerencianet_due_date' => array('required' => false, 'obrigatorio'=> true),
            'payment_gerencianet_mtls' => array('required' => false, 'type' => 'checkbox', 'tooltip' => 'mtls_info', 'obrigatorio'=> false),
            'payment_gerencianet_pix_active' => array('required' => false, 'type' => 'checkbox', 'tooltip' => 'pix_active_info', 'obrigatorio'=> false)
        ),
        'boleto' => array(
            'payment_gerencianet_boleto_vencimento' => array('required' => false, 'tooltip' => 'boleto_vencimento_info', 'obrigatorio'=> true),
            'payment_gerencianet_boleto_desconto' => array('required' => false, 'tooltip' =>'boleto_desconto_info', 'obrigatorio'=> false),
            'payment_gerencianet_boleto_multa' => array('required' => false, 'tooltip' =>'boleto_multa_info', 'obrigatorio'=> false),
            'payment_gerencianet_boleto_juros' => array('required' => false, 'tooltip' =>'boleto_juros_info', 'obrigatorio'=> false),
            'payment_gerencianet_boleto_observacoes' => array('required' => false, 'tooltip' =>'boleto_observacoes_info', 'obrigatorio'=> false),
            'payment_gerencianet_boleto_email_cobranca' => array('required' => false,'type' => 'checkbox', 'tooltip' =>'boleto_email_cobranca_info', 'obrigatorio'=> false),
            'payment_gerencianet_boleto_active' => array('required' => false,'type' => 'checkbox', 'tooltip' =>'boleto_active_info', 'obrigatorio'=> false)
        ),
        'OF' => array(
            'payment_gerencianet_OF_nome' => array('required' => false, 'tooltip' => 'OF_nome_info', 'obrigatorio'=> true),
            'payment_gerencianet_OF_documento' => array('required' => false, 'tooltip' => 'OF_documento_info', 'obrigatorio'=> true),
            'payment_gerencianet_OF_agecia' => array('required' => false, 'tooltip' => 'OF_agecia_info', 'obrigatorio'=> true),
            'payment_gerencianet_OF_conta' => array('required' => false, 'tooltip' => 'OF_conta_info', 'obrigatorio'=> true),
            'payment_gerencianet_OF_tipo_conta' => array('required' => false, 'tooltip' => 'OF_tipo_conta_info', 'obrigatorio'=> true,'type'=>'select', 'enum'=>[
                'CACC'=>'Conta Corrente',
                'SLRY'=>'Conta Salário',
                'SVGS'=>'Conta Poupança',
                'TRAN'=>'Conta  de Transações'
                ]),
            'payment_gerencianet_OF_discount' => array('required' => false, 'tooltip' => 'OF_discount_info', 'obrigatorio'=> false),
            'payment_gerencianet_OF_active' => array('required' => false,'type' => 'checkbox', 'tooltip' =>'OF_active_info', 'obrigatorio'=> false)

        ),
        'cartao' => array(
            'payment_gerencianet_cartao_active' => array('required' => false,'type' => 'checkbox', 'tooltip' =>'cartao_active_info')
        ),
        'order' => array(
            'payment_gerencianet_status_new' => array('required' => false, 'type' => 'select'),
            'payment_gerencianet_status_paid' => array('required' => false, 'type' => 'select'),
            'payment_gerencianet_status_refunded' => array('required' => false, 'type' => 'select')
        )
    );


    /**
     * Informações sobre o plugin
     */
    const aboutInfo = [
        'version' => '4.1',
        'website' => 'https://sejaefi.com.br',
        'documentation' => 'https://dev.sejaefi.com.br/docs',
        'support' => 'https://sejaefi.com.br/central-de-ajuda/',
        'logoUrl' => 'view/image/payment/efi-horizontal-colorido.svg'
    ];

    /**
     * Padrão de Formatação do array recebido na Classe do SDK da Gerencianet
     */
    public const configOptionsPix = [
        'clientIdProd'      => 'payment_gerencianet_prod_client_id',
        'clientSecretProd'  => 'payment_gerencianet_prod_client_secret',
        'clientIdDev'       => 'payment_gerencianet_dev_client_id',
        'clientSecretDev'   => 'payment_gerencianet_dev_client_secret',
        'sandbox'           => 'payment_gerencianet_sandbox',
        'debug'             => 'payment_gerencianet_debug',
        'pixKey'            => 'payment_gerencianet_pix_key',
        'pixDiscount'       => 'payment_gerencianet_discount',
        'pixHours'          => 'payment_gerencianet_due_date',
        'mtls'              => 'payment_gerencianet_mtls',
        'payeeId'           => 'payment_gerencianet_payee_id'
    ];
    /**
     * Padrão de Formatação do array recebido na Classe do SDK da Gerencianet
     */
    public const configOptionsOF = [
        'clientIdProd'      => 'payment_gerencianet_prod_client_id',
        'clientSecretProd'  => 'payment_gerencianet_prod_client_secret',
        'clientIdDev'       => 'payment_gerencianet_dev_client_id',
        'clientSecretDev'   => 'payment_gerencianet_dev_client_secret',
        'sandbox'           => 'payment_gerencianet_sandbox',
        'debug'             => 'payment_gerencianet_debug',
        'mtls'              => 'payment_gerencianet_mtls',

    ];
}