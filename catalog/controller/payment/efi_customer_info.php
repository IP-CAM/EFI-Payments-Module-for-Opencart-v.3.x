<?php

namespace Opencart\Catalog\Controller\Extension\Efi\Payment;


class EfiCustomerInfo extends \Opencart\System\Engine\Controller
{
    public function index(): void
    {
        $this->response->addHeader('Content-Type: application/json');

        if (!$this->customer->isLogged()) {
            $this->response->setOutput(json_encode([
                'success' => false,
                'message' => 'Usuário não logado'
            ]));
            return;
        }

        // Dados básicos do cliente
        $data = [
            'success'     => true,
            'customer_id' => $this->customer->getId(),
            'firstname'   => $this->customer->getFirstName(),
            'lastname'    => $this->customer->getLastName(),
            'email'       => $this->customer->getEmail(),
            'telephone'   => $this->customer->getTelephone(),
            'group_id'    => $this->customer->getGroupId()
        ];

        // Carrega o Model para endereço
        $this->load->model('account/address');

        // Tenta buscar o endereço de cobrança da sessão
        $address_id = $this->session->data['payment_address']['address_id'] ?? $this->customer->getAddressId();

        if ($address_id) {
            $address_info = $this->model_account_address->getAddress($address_id);

            if ($address_info) {
                $data['address'] = [
                    'address_1'   => $address_info['address_1'] ?? '',
                    'address_2'   => $address_info['address_2'] ?? '',
                    'city'        => $address_info['city'] ?? '',
                    'postcode'    => $address_info['postcode'] ?? '',
                    'zone'        => $address_info['zone'] ?? '',
                    'zone_id'     => $address_info['zone_id'] ?? '',
                    'country'     => $address_info['country'] ?? '',
                    'country_id'  => $address_info['country_id'] ?? '',
                ];
            }
        }

        $this->response->setOutput(json_encode($data));
    }
}
