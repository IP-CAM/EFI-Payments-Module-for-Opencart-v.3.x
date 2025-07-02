<?php

namespace Opencart\Extension\Efi\Library;

class EfiShippingHelper
{
    /**
     * Retorna o array de shipping formatado a partir do $order_info.
     *
     * @param array $order_info Informações do pedido
     * @param string $type Tipo de chamada (ex: 'charge', 'subscription', etc.)
     * @return array
     */
    public static function getShippingsFromOrder(array $order_info, string $type = 'charge'): array
    {
        $shipping_value = 0.00;

        if (isset($order_info['shipping_cost']) && $order_info['shipping_cost'] > 0) {
            $shipping_value = (float) $order_info['shipping_cost'];
        } elseif (isset($order_info['shipping']) && $order_info['shipping'] > 0) {
            $shipping_value = (float) $order_info['shipping'];
        } elseif (isset($order_info['shipping_method']['cost'])) {
            $shipping_value = (float) $order_info['shipping_method']['cost'];
        }

        self::logShippingInfo($shipping_value, $type);

        return self::formatShippings($shipping_value, $type);
    }

    /**
     * Formata o valor de frete de acordo com o tipo de uso.
     *
     * @param float $shipping Valor do frete em reais
     * @param string $type Tipo de chamada
     * @return array
     */
    public static function formatShippings(float $shipping, string $type = 'charge'): array
    {
        if ($shipping <= 0) {
            return [];
        }

        if ($type === 'charge') {
            return [
                [
                    'name'  => 'frete',
                    'value' => intval($shipping * 100)
                ]
            ];
        }

        return [
            'value' => $shipping
        ];
    }

    /**
     * Salva log com os dados de frete e tipo.
     *
     * @param float $shipping
     * @param string $type
     * @return void
     */
    private static function logShippingInfo(float $shipping, string $type): void
    {
        $log = new \Opencart\System\Library\Log('efi_shippings.log');
        $log->write("Tipo: {$type} | Valor do frete: {$shipping}");
    }
}
