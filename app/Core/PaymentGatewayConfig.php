<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class PaymentGatewayConfig
{
    /**
     * Return decrypted credentials for trusted server-side gateway code only.
     * This method never enables payments or performs an outbound request.
     */
    public static function forProvider(string $provider): array
    {
        $definitions = [
            'iyzico' => [
                'environment' => 'payments.iyzico.environment',
                'credentials' => [
                    'api_key' => 'payments.iyzico.api_key',
                    'secret_key' => 'payments.iyzico.secret_key',
                ],
            ],
            'paytr' => [
                'environment' => 'payments.paytr.environment',
                'credentials' => [
                    'merchant_id' => 'payments.paytr.merchant_id',
                    'merchant_key' => 'payments.paytr.merchant_key',
                    'merchant_salt' => 'payments.paytr.merchant_salt',
                ],
            ],
        ];
        if (!isset($definitions[$provider])) {
            throw new \InvalidArgumentException('Desteklenmeyen ödeme sağlayıcısı.');
        }

        $keys = array_merge(
            [$definitions[$provider]['environment'], 'payments.' . $provider . '.enabled'],
            array_values($definitions[$provider]['credentials'])
        );
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT setting_key,setting_value FROM settings WHERE setting_key IN ($placeholders)"
        );
        $stmt->execute($keys);
        $stored = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $credentials = [];
        foreach ($definitions[$provider]['credentials'] as $name => $settingKey) {
            $value = (string)($stored[$settingKey] ?? '');
            if ($value !== '') {
                $credentials[$name] = PaymentSecretVault::decrypt($value);
            }
        }

        $enabledKey = 'payments.' . $provider . '.enabled';
        $enabled = (string)($stored[$enabledKey] ?? '0') === '1';

        $environment = (string)($stored[$definitions[$provider]['environment']] ?? 'sandbox');
        if (!in_array($environment, ['sandbox', 'production'], true)) {
            $environment = 'sandbox';
        }

        return [
            'provider' => $provider,
            'environment' => $environment,
            'configured' => count($credentials) === count($definitions[$provider]['credentials']),
            'enabled' => $enabled,
            'credentials' => $credentials,
        ];
    }
}
