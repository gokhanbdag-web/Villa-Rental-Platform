<?php
declare(strict_types=1);

namespace App\Core;

final class VendorUpdateManifest
{
    public const URL = 'https://kodvadisi.com.tr/updates/villa-rental/manifest.json';

    public static function check(string $installedVersion): array
    {
        $result = ['connected' => false, 'available' => false, 'version' => '', 'notes' => '', 'error' => ''];
        $url = self::URL;
        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'kodvadisi.com.tr') {
            $result['error'] = 'Güncelleme kaynağı geçersiz.';
            return $result;
        }
        $body = false;
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl !== false) {
                curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10, CURLOPT_MAXREDIRS => 0,
                    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_USERAGENT => 'VillaRental-Updater/1.0']);
                $body = curl_exec($curl);
                $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                if ($status !== 200) $body = false;
            }
        } else {
            $context = stream_context_create(['http' => ['timeout' => 10, 'follow_location' => 0, 'ignore_errors' => false]]);
            $body = @file_get_contents($url, false, $context, 0, 65537);
        }
        if (!is_string($body) || strlen($body) > 65536) {
            $result['error'] = 'Güncelleme sunucusuna ulaşılamıyor.';
            return $result;
        }
        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['version']) || !is_string($data['version'])
            || !preg_match('/^\d+\.\d+\.\d+$/D', $data['version'])) {
            $result['error'] = 'Güncelleme bildirimi geçersiz.';
            return $result;
        }
        $result['connected'] = true;
        $result['version'] = $data['version'];
        $result['available'] = version_compare($data['version'], $installedVersion, '>');
        $result['notes'] = mb_substr(is_string($data['notes'] ?? null) ? $data['notes'] : '', 0, 3000);
        return $result;
    }
}
