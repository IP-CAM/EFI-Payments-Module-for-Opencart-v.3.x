<?php

namespace Opencart\Catalog\Controller\Extension\Efi\Payment;

use Opencart\Extension\Efi\Library\EfiDiscountHelper;

class Efi extends \Opencart\System\Engine\Controller
{
	public function index(): string
	{
		try {
			$this->load->language('extension/efi/payment/efi');

			// Recursos comuns
			$this->document->addStyle('extension/efi/catalog/view/stylesheet/fontawesome/css/all.min.css');
			$this->document->addStyle('extension/efi/catalog/view/stylesheet/common/color-brand.css');
			$this->document->addStyle('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');
			$this->document->addScript('extension/efi/catalog/view/javascript/validation/commonValidations.js');
			$this->document->addScript('extension/efi/catalog/view/javascript/libs/imask.min.js');
			$this->document->addScript('extension/efi/catalog/view/javascript/common/masks.js');

			$data['language'] = $this->config->get('config_language');
			$data['payment_efi_status'] = $this->config->get('payment_efi_status');
			$data['img_logo_url'] = $this->getImagePath('efi_logo.png');
			$total = $this->cart->getTotal();

			// Carrega sempre o array completo das configs para passar ao helper
			$this->load->model('setting/setting');
			$settings = $this->model_setting_setting->getSetting('payment_efi');

			$methodCode = $this->session->data['payment_method']['code'] ?? '';

			// Inicialização padrão (cartão ou fallback)
			$data['total'] = 'R$ ' . number_format($total, 2, ',', '.');
			$data['msg_desconto'] = '';
			$data['value_desconto'] = 'R$ 0,00';
			$data['total_value_with_discount'] = 'R$ ' . number_format($total, 2, ',', '.');

			switch ($methodCode) {
				case 'efi.efi_pix':
					$this->loadPixResources($data);
					$data = array_merge($data, EfiDiscountHelper::getDiscountTableData($total, 'payment_efi_pix_discount', $settings));
					break;

				case 'efi.efi_billet':
					$this->loadBilletResources($data);
					$data = array_merge($data, EfiDiscountHelper::getDiscountTableData($total, 'payment_efi_billet_discount', $settings));
					break;

				case 'efi.efi_open_finance':
					$this->loadOpenFinanceResources($data);
					$data = array_merge($data, EfiDiscountHelper::getDiscountTableData($total, 'payment_efi_open_finance_discount', $settings));
					break;

				case 'efi.efi_card':
					$this->loadCardResources($data);
					// Cartão não tem desconto, mantém padrão
					break;

				default:
					$this->logError("Método de pagamento não reconhecido: " . $methodCode);
					return '';
			}

			$data['styles'] = $this->document->getStyles();
			$data['scripts'] = $this->document->getScripts();

			return $this->load->view('extension/efi/payment/efi', $data);
		} catch (\Exception $e) {
			$this->logError($e->getMessage());
			return '';
		}
	}

	private function getImagePath(string $imgName): string
	{
		return 'extension/efi/catalog/view/image/' . $imgName;
	}

	private function logError(string $message): void
	{
		$log = new \Opencart\System\Library\Log('efi.log');
		$log->write($message);
	}

	private function loadPixResources(array &$data): void
	{
		$this->load->model('extension/efi/payment/pix/efi_pix_inputs');
		$data['inputs'] = $this->model_extension_efi_payment_pix_efi_pix_inputs->getEntryFormatted($this->language);
		$data['btn_confirm_text'] = $this->language->get('btn_confirm_text_pix');
		$data['btn_confirm_icon'] = '<i class="fa-brands me-1 fa-pix"></i>';
		$data['efi_payment_id_form'] = 'efi-pix-form';
		$data['efi_payment_description'] = $this->language->get('text_description_pix');
		$data['command_init_form_payment'] = 'new PixFormHandler();';
		$this->document->addScript('extension/efi/catalog/view/javascript/payments/pix/pixFormHandler.js');
	}

	private function loadBilletResources(array &$data): void
	{
		$this->load->model('extension/efi/payment/billet/efi_billet_inputs');
		$data['inputs'] = $this->model_extension_efi_payment_billet_efi_billet_inputs->getEntryFormatted($this->language);
		$data['btn_confirm_text'] = $this->language->get('btn_confirm_text_billet');
		$data['btn_confirm_icon'] = '<i class="fa-solid me-1 fa-money-check-dollar"></i>';
		$data['efi_payment_id_form'] = 'efi-billet-form';
		$data['efi_payment_description'] = $this->language->get('text_description_billet');
		$data['command_init_form_payment'] = 'new BilletFormHandler();';
		$this->document->addScript('extension/efi/catalog/view/javascript/payments/billet/billetFormHandler.js');
	}

	private function loadCardResources(array &$data): void
	{
		$this->load->model('extension/efi/payment/card/efi_card_inputs');
		$data['inputs'] = $this->model_extension_efi_payment_card_efi_card_inputs->getEntryFormatted($this->language);
		$data['btn_confirm_text'] = $this->language->get('btn_confirm_text_card');
		$data['btn_confirm_icon'] = '<i class="fa-solid me-1 fa-credit-card"></i>';
		$data['efi_payment_id_form'] = 'efi-card-form';
		$data['efi_payment_description'] = $this->language->get('text_description_card');
		$data['account_coude'] = $this->config->get('payment_efi_account_code');
		$data['envoriment'] = $this->config->get('payment_efi_enviroment') ? 'sandbox' : 'production';
		$data['total'] = $this->cart->getTotal();
		$data['command_init_form_payment'] = $this->load->view('extension/efi/payment/efi_script_card', $data);
		$this->document->addScript('https://cdn.jsdelivr.net/npm/payment-token-efi/dist/payment-token-efi-umd.min.js');
		$this->document->addScript('extension/efi/catalog/view/javascript/payments/card/CardInstallments.js');
		$this->document->addScript('extension/efi/catalog/view/javascript/payments/card/CardPaymentToken.js');
		$this->document->addScript('extension/efi/catalog/view/javascript/payments/card/cardFormHandler.js');
	}

	private function loadOpenFinanceResources(array &$data): void
	{
		$this->load->model('setting/setting');
		$settings = $this->model_setting_setting->getSetting('payment_efi');
		$this->load->model('extension/efi/payment/openfinance/efi_open_finance_inputs');
		$data['inputs'] = $this->model_extension_efi_payment_openfinance_efi_open_finance_inputs->getEntryFormatted($this->language, $settings);
		$data['btn_confirm_text'] = $this->language->get('btn_confirm_text_open_finance');
		$data['btn_confirm_icon'] = '<i class="fa-solid me-1 fa-arrow-right"></i>';
		$data['efi_payment_id_form'] = 'efi-open-finance-form';
		$data['efi_payment_description'] = $this->language->get('text_description_open_finance');
		$data['command_init_form_payment'] = 'new OpenFinanceFormHandler();';
		$this->document->addScript('extension/efi/catalog/view/javascript/payments/openFinance/openFinanceFormHandler.js');
	}
}
