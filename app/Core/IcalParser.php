<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class IcalParser
{
    public static function parse(string $calendar): array
    {
        if (strlen($calendar) > 2097152) throw new RuntimeException('iCal dosyası 2 MB sınırını aşıyor.');
        $calendar = preg_replace("/\r\n[ \t]|\n[ \t]/", '', $calendar) ?? $calendar;
        $calendar = str_replace("\r", "\n", $calendar);
        $events = [];
        $current = null;
        foreach (explode("\n", $calendar) as $line) {
            $line = trim($line);
            if ($line === 'BEGIN:VEVENT') { $current = []; continue; }
            if ($line === 'END:VEVENT') {
                if (is_array($current)) {
                    $event = self::normaliseEvent($current);
                    if ($event !== null) $events[$event['uid']] = $event;
                }
                $current = null;
                if (count($events) > 500) throw new RuntimeException('Takvim tek senkronizasyonda 500 etkinlik sınırını aşıyor.');
                continue;
            }
            if (!is_array($current) || $line === '' || str_starts_with($line, 'BEGIN:') || str_starts_with($line, 'END:')) continue;
            $colon = strpos($line, ':');
            if ($colon === false) continue;
            $left = substr($line, 0, $colon);
            $value = substr($line, $colon + 1);
            $name = strtoupper(explode(';', $left, 2)[0]);
            if (in_array($name, ['UID','DTSTART','DTEND','STATUS','TRANSP','SUMMARY'], true)) {
                $current[$name] = ['left'=>$left,'value'=>$value];
            }
        }
        return array_values($events);
    }

    private static function normaliseEvent(array $properties): ?array
    {
        $uid = trim((string)($properties['UID']['value'] ?? ''));
        $start = $properties['DTSTART'] ?? null;
        $end = $properties['DTEND'] ?? null;
        if ($uid === '' || strlen($uid) > 255 || !is_array($start) || !is_array($end)) return null;
        $dateFrom = self::dateValue((string)$start['value'], (string)$start['left']);
        $dateTo = self::dateValue((string)$end['value'], (string)$end['left']);
        if ($dateFrom === null || $dateTo === null || $dateTo <= $dateFrom) return null;
        $days = (int)(new DateTimeImmutable($dateFrom))->diff(new DateTimeImmutable($dateTo))->days;
        if ($days < 1 || $days > 366) return null;
        $status = strtoupper(trim((string)($properties['STATUS']['value'] ?? 'CONFIRMED')));
        $transparency = strtoupper(trim((string)($properties['TRANSP']['value'] ?? 'OPAQUE')));
        $summary = self::unescapeText((string)($properties['SUMMARY']['value'] ?? ''));
        return [
            'uid'=>$uid,
            'date_from'=>$dateFrom,
            'date_to'=>$dateTo,
            'cancelled'=>$status === 'CANCELLED' || $transparency === 'TRANSPARENT',
            'summary'=>mb_substr($summary,0,190),
        ];
    }

    private static function dateValue(string $value, string $property): ?string
    {
        $value = trim($value);
        try {
            if (preg_match('/^\d{8}$/', $value)) {
                $date = DateTimeImmutable::createFromFormat('!Ymd', $value, new DateTimeZone('UTC'));
                return $date ? $date->format('Y-m-d') : null;
            }
            if (!preg_match('/^\d{8}T\d{6}Z?$/', $value)) return null;
            $tz = new DateTimeZone('UTC');
            if (preg_match('/(?:^|;)TZID=([^;:]+)/i', $property, $match) && !str_ends_with($value,'Z')) {
                $tz = new DateTimeZone(trim($match[1], '"'));
            }
            $format = str_ends_with($value,'Z') ? '!Ymd\THis\Z' : '!Ymd\THis';
            $date = DateTimeImmutable::createFromFormat($format, $value, $tz);
            return $date ? $date->format('Y-m-d') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function unescapeText(string $value): string
    {
        return str_replace(['\\n','\\N','\\,','\\;','\\\\'], ["\n","\n",',',';','\\'], $value);
    }
}
