<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class CalendarFeedFetcher
{
    public static function validateUrl(string $url): array
    {
        $url = trim($url);
        if (strlen($url) > 2048) throw new RuntimeException('iCal adresi çok uzun.');
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && (int)$parts['port'] !== 443)) {
            throw new RuntimeException('Takvim adresi yalnızca HTTPS üzerinden, standart 443 portunda ve kullanıcı/parola içermeden kullanılabilir.');
        }
        $host = strtolower(rtrim((string)$parts['host'], '.'));
        if ($host === '' || $host === 'localhost' || str_ends_with($host,'.localhost') || str_ends_with($host,'.local')) {
            throw new RuntimeException('Yerel ağ veya localhost adresleri takvim kaynağı olarak kullanılamaz.');
        }
        if (filter_var($host,FILTER_VALIDATE_IP)) {
            $ips = [$host];
        } else {
            $records = dns_get_record($host,DNS_A);
            $ips = [];
            foreach (($records ?: []) as $record) if (!empty($record['ip'])) $ips[] = (string)$record['ip'];
        }
        if (!$ips) throw new RuntimeException('Takvim sunucusunun herkese açık IPv4 adresi çözümlenemedi.');
        foreach ($ips as $ip) {
            if (filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new RuntimeException('Özel, ayrılmış veya yerel IP adreslerine takvim isteği gönderilemez.');
            }
        }
        return ['url'=>$url,'host'=>$host,'ip'=>$ips[0]];
    }

    public static function fetch(string $url): string
    {
        $target = self::validateUrl($url);
        if (!function_exists('curl_init')) throw new RuntimeException('Takvim senkronizasyonu için PHP cURL eklentisi gereklidir.');
        $body = '';
        $handle = curl_init($target['url']);
        if ($handle === false) throw new RuntimeException('Takvim bağlantısı başlatılamadı.');
        curl_setopt_array($handle,[
            CURLOPT_RETURNTRANSFER=>false,
            CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_MAXREDIRS=>0,
            CURLOPT_CONNECTTIMEOUT=>5,
            CURLOPT_TIMEOUT=>15,
            CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2,
            CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,
            CURLOPT_RESOLVE=>[$target['host'].':443:'.$target['ip']],
            CURLOPT_USERAGENT=>'VillaCMS Calendar Sync/1.0',
            CURLOPT_WRITEFUNCTION=>static function($curl,string $chunk) use (&$body): int {
                if (strlen($body)+strlen($chunk)>2097152) return 0;
                $body.=$chunk;
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($handle);
        $status = (int)curl_getinfo($handle,CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if ($ok === false) throw new RuntimeException('Takvim kaynağı alınamadı: '.($error !== '' ? $error : 'bağlantı hatası'));
        if ($status < 200 || $status >= 300) throw new RuntimeException('Takvim sunucusu HTTP '.$status.' yanıtı verdi.');
        if (!str_contains($body,'BEGIN:VCALENDAR')) throw new RuntimeException('Kaynak geçerli bir iCalendar dosyası döndürmedi.');
        return $body;
    }
}
