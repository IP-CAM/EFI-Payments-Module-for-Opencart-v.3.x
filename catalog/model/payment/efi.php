<?php

namespace Opencart\Catalog\Model\Extension\Efi\Payment;

class Efi extends \Opencart\System\Engine\Model
{
	/**
	 * Obtém os métodos de pagamento disponíveis
	 *
	 * @param array<string, mixed> $address Dados do endereço do cliente
	 * @return array<string, mixed>
	 */
	public function getMethods(array $address = []): array
	{
		$this->load->language('extension/efi/payment/efi');

		$option_data = [];

		// Adiciona métodos de pagamento dinamicamente
		$this->addPaymentOption($option_data, 'payment_efi_pix_status', 'efi_pix', 'text_title_pix');
		$this->addPaymentOption($option_data, 'payment_efi_card_status', 'efi_card', 'text_title_card');
		$this->addPaymentOption($option_data, 'payment_efi_billet_status', 'efi_billet', 'text_title_billet');
		$this->addPaymentOption($option_data, 'payment_efi_open_finance_status', 'efi_open_finance', 'text_title_open_finance');

		if (empty($option_data)) {
			return [];
		}

		return [
			'code'       => 'efi',
			'name'       => $this->language->get('text_title'),
			'option'     => $option_data,
			'sort_order' => $this->config->get('payment_efi_sort_order')
		];
	}

	/**
	 * Adiciona dinamicamente uma opção de método de pagamento se estiver ativo.
	 *
	 * @param array<string, mixed> &$options     Referência ao array de opções
	 * @param string               $config_key   Chave da configuração de status (ex: payment_efi_pix_status)
	 * @param string               $code_suffix  Sufixo para o código do método (ex: efi_pix)
	 * @param string               $language_key Chave do arquivo de linguagem (ex: text_title_pix)
	 * @return void
	 */
	private function addPaymentOption(array &$options, string $config_key, string $code_suffix, string $language_key): void
	{
		if ($this->config->get($config_key)) {
			$options[$code_suffix] = [
				'code' => 'efi.' . $code_suffix,
				'name' => $this->language->get($language_key)
			];
		}
	}
}
