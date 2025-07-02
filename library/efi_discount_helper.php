<?php

namespace Opencart\Extension\Efi\Library;

class EfiDiscountHelper
{
    /**
     * Retorna todos os dados prontos para a tabela de desconto da view.
     *
     * @param float  $total         Valor total original.
     * @param string $discountKey   Campo de desconto nas configs.
     * @param array  $settings      Configurações completas do Opencart.
     * @return array
     */
    public static function getDiscountTableData(float $total, string $discountKey, array $settings): array
    {
        $discountConfig = trim($settings[$discountKey] ?? '');
        $valorDesconto = 0.0;

        // Descobre tipo do desconto
        if ($discountConfig !== '') {
            if (strpos($discountConfig, '%') !== false) {
                $percent = (float) str_replace('%', '', $discountConfig);
                $valorDesconto = round(($total * $percent) / 100, 2);
                $msgDesconto = "Desconto ({$percent}%)";
            } else {
                $valorDesconto = (float) str_replace(',', '.', $discountConfig);
                $msgDesconto = "Desconto (R$ " . number_format($valorDesconto, 2, ',', '.') . ")";
            }
        } else {
            $msgDesconto = "Desconto";
        }

        // Nunca deixa desconto maior que total
        if ($valorDesconto > $total) $valorDesconto = $total;

        $totalComDesconto = round($total - $valorDesconto, 2);

        return [
            'total'                    => 'R$ ' . number_format($total, 2, ',', '.'),
            'msg_desconto'             => $msgDesconto,
            'value_desconto'           => 'R$ ' . number_format($valorDesconto, 2, ',', '.'),
            'total_value_with_discount' => 'R$ ' . number_format($totalComDesconto, 2, ',', '.'),
        ];
    }
}
