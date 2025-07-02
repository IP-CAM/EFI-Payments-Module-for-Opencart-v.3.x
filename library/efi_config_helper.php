<?php

namespace Opencart\Extension\Efi\Library;

/**
 * Classe auxiliar para lidar com as configurações da API Efi.
 */
class EfiConfigHelper
{
    /**
     * Retorna as configurações formatadas para a API Efi.
     *
     * @param array $settings Configurações obtidas do banco de dados.
     * @return array Configuração formatada para a API do Efi.
     */
    public static function getEfiConfig(array $settings): array
    {
        return [
            "clientId"       => ($settings['payment_efi_enviroment']) ? $settings['payment_efi_client_id_sandbox'] : $settings['payment_efi_client_id_production'],
            "clientSecret"   => ($settings['payment_efi_enviroment']) ? $settings['payment_efi_client_secret_sandbox'] : $settings['payment_efi_client_secret_production'],
            "certificate"    => $settings['payment_efi_pix_certificate'] ?? '',
            "sandbox"        => (bool) $settings['payment_efi_enviroment'],
            "debug"          => false,
            "timeout"        => 60,
            "headers"       => [
                "efi-opencart-version"  => "2.0"
            ]
        ];
    }
}
