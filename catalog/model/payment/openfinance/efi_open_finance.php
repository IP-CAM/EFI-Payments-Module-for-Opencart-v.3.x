<?php

namespace Opencart\Catalog\Model\Extension\Efi\Payment\OpenFinance;

require_once DIR_EXTENSION . 'efi/library/vendor/autoload.php';

use Opencart\Extension\Efi\Library\EfiConfigHelper;
use Opencart\Extension\Efi\Library\EfiShippingHelper;
use Efi\EfiPay;
use Exception;

class EfiOpenFinance extends \Opencart\System\Engine\Model
{
    /**
     * Gera um pagamento Pix via Open Finance.
     */
    public function generatePayment(
        string $customer_document_cpf,
        string $customer_document_cnpj,
        string $customer_bank,
        float $amount,
        string $order_id,
        array $settings,
        array $order_info
    ): array {
        try {
            // Configurações Efí
            $options = EfiConfigHelper::getEfiConfig($settings);
            $options["headers"] = array_merge(
                $options["headers"] ?? [],
                [
                    "x-idempotency-key" => bin2hex(random_bytes(18))
                ]
            );


            // Monta dados do pagador
            $pagador = [
                "idParticipante" => $customer_bank
            ];

            // CPF obrigatório
            $cpf = preg_replace('/\D/', '', $customer_document_cpf);
            if (strlen($cpf) === 11) {
                $pagador["cpf"] = $cpf;
            } else {
                throw new Exception("CPF inválido.");
            }

            // CNPJ opcional
            $cnpj = preg_replace('/\D/', '', $customer_document_cnpj);
            if (!empty($cnpj)) {
                if (strlen($cnpj) === 14) {
                    $pagador["cnpj"] = $cnpj;
                } else {
                    throw new Exception("CNPJ inválido.");
                }
            }

            // Favorecido
            $favorecido = [
                "chave" => $settings['payment_efi_open_finance_key'] ?? ''
            ];

            // Desconto, se houver
            $discount = $settings['payment_efi_open_finance_discount'] ?? '';
            $valorFinal = $this->aplicarDesconto($amount, $discount);

            // Aplica o frete (se houver)
            $shippings = EfiShippingHelper::getShippingsFromOrder($order_info, 'pix');
            $this->logError('SHIPPING: ' . json_encode($shippings));

            if (isset($shippings['value'])) {
                $valorFinal += (float) $shippings['value'];
            }

            $this->logError("VALOR FINAL COM FRETE: {$valorFinal}");

            // Corpo da requisição
            $body = [
                "pagador" => $pagador,
                "favorecido" => $favorecido,
                "pagamento" => [
                    "valor" => number_format($valorFinal, 2, '.', ''),
                    "infoPagador" => "Pagamento do Pedido #{$order_id}",
                    "idProprio" => "OC-Order-{$order_id}"
                ]
            ];

            // Envia para API
            $efiPay = new EfiPay($options);
            $response = $efiPay->ofStartPixPayment([], $body);

            if (empty($response['redirectURI']) || empty($response['identificadorPagamento'])) {
                throw new Exception('Resposta inesperada da API Efí.');
            }

            return [
                'success' => true,
                'redirect_url' => $response['redirectURI'],
                'identificadorPagamento' => $response['identificadorPagamento']
            ];
        } catch (Exception $e) {
            $this->logError("Erro na geração de pagamento Open Finance: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao iniciar pagamento via Open Finance. Consulte o log.'
            ];
        }
    }

    /**
     * Consulta o status de um pagamento.
     */
    public function getDetailPayment(string $identificadorPagamento, array $settings): array
    {
        try {
            $options = EfiConfigHelper::getEfiConfig($settings);
            $efiPay = new EfiPay($options);
            $response = $efiPay->ofListPixPayment(['identificador' => $identificadorPagamento]);

            $pagamento = $response['pagamentos'][0] ?? null;

            return [
                'status' => $pagamento['status'] ?? 'desconhecido'
            ];
        } catch (Exception $e) {
            $this->logError("Erro ao consultar status do pagamento Open Finance: " . $e->getMessage());
            return [
                'status' => 'erro',
                'mensagem' => $e->getMessage()
            ];
        }
    }

    /**
     * Aplica desconto no valor informado.
     * Se o desconto termina com %, aplica percentual. Senão, valor fixo.
     */
    private function aplicarDesconto(float $valorOriginal, string $desconto): float
    {
        $desconto = trim($desconto);

        if ($desconto === '' || $desconto === '0') {
            return $valorOriginal;
        }

        if (str_ends_with($desconto, '%')) {
            $percent = floatval(str_replace('%', '', $desconto));
            if ($percent > 0 && $percent < 100) {
                $valorFinal = $valorOriginal - ($valorOriginal * $percent / 100);
                return max($valorFinal, 0.01);
            }
        } else {
            $descontoValor = floatval(str_replace(',', '.', $desconto));
            if ($descontoValor > 0 && $descontoValor < $valorOriginal) {
                return $valorOriginal - $descontoValor;
            }
        }

        return $valorOriginal;
    }

    /**
     * Log de erro.
     */
    private function logError(string $message): void
    {
        $log = new \Opencart\System\Library\Log('efi_open_finance.log');
        $log->write($message);
    }
}
