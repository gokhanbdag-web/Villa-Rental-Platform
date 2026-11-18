<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class IcalParser
{
    private const WEEKDAYS = ['MO'=>1,'TU'=>2,'WE'=>3,'TH'=>4,'FR'=>5,'SA'=>6,'SU'=>7];

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
                    if ($event !== null) {
                        foreach (self::expandEvent($event) as $occurrence) {
                            $events[$occurrence['uid']] = $occurrence;
                            if (count($events) > 500) throw new RuntimeException('Takvim tek senkronizasyonda 500 etkinlik sınırını aşıyor.');
                        }
                    }
                }
                $current = null;
                continue;
            }
            if (!is_array($current) || $line === '' || str_starts_with($line, 'BEGIN:') || str_starts_with($line, 'END:')) continue;
            $colon = strpos($line, ':');
            if ($colon === false) continue;
            $left = substr($line, 0, $colon);
            $value = substr($line, $colon + 1);
            $name = strtoupper(explode(';', $left, 2)[0]);
            if (in_array($name, ['UID','DTSTART','DTEND','STATUS','TRANSP','SUMMARY','RRULE','EXDATE'], true)) {
                $property = ['left'=>$left,'value'=>$value];
                if ($name === 'EXDATE') $current[$name][] = $property;
                else $current[$name] = $property;
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
        $exdates = [];
        foreach (($properties['EXDATE'] ?? []) as $exdateProperty) {
            foreach (explode(',', (string)$exdateProperty['value']) as $value) {
                $excluded = self::dateValue($value, (string)$exdateProperty['left']);
                if ($excluded !== null) $exdates[$excluded] = true;
            }
        }
        return [
            'uid'=>$uid,
            'date_from'=>$dateFrom,
            'date_to'=>$dateTo,
            'cancelled'=>$status === 'CANCELLED' || $transparency === 'TRANSPARENT',
            'summary'=>mb_substr($summary,0,190),
            'rrule'=>trim((string)($properties['RRULE']['value'] ?? '')),
            'exdates'=>$exdates,
        ];
    }

    /**
     * Expand a deliberately bounded RFC 5545 subset used by common booking feeds.
     * DAILY and WEEKLY are supported; unsupported rules fail closed rather than
     * silently importing only the first occurrence and leaving dates bookable.
     */
    private static function expandEvent(array $event): array
    {
        $ruleText = (string)$event['rrule'];
        unset($event['rrule']);
        $excluded = $event['exdates'] ?? [];
        unset($event['exdates']);
        if ($ruleText === '') return [$event];

        $rule = [];
        foreach (explode(';', strtoupper($ruleText)) as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) !== 2 || $pair[0] === '' || isset($rule[$pair[0]])) {
                throw new RuntimeException('iCal RRULE biçimi geçersiz.');
            }
            $rule[$pair[0]] = $pair[1];
        }
        $allowed = ['FREQ','INTERVAL','COUNT','UNTIL','BYDAY'];
        if (array_diff(array_keys($rule), $allowed)) throw new RuntimeException('iCal RRULE içinde desteklenmeyen bir kural alanı var.');
        $frequency = $rule['FREQ'] ?? '';
        if (!in_array($frequency, ['DAILY','WEEKLY'], true)) throw new RuntimeException('iCal yinelenen etkinliklerde yalnızca DAILY ve WEEKLY desteklenir.');
        $interval = isset($rule['INTERVAL']) && ctype_digit($rule['INTERVAL']) ? (int)$rule['INTERVAL'] : 1;
        if ($interval < 1 || $interval > 30) throw new RuntimeException('iCal RRULE INTERVAL değeri 1 ile 30 arasında olmalıdır.');
        $count = null;
        if (isset($rule['COUNT'])) {
            if (!ctype_digit($rule['COUNT']) || (int)$rule['COUNT'] < 1 || (int)$rule['COUNT'] > 500) {
                throw new RuntimeException('iCal RRULE COUNT değeri 1 ile 500 arasında olmalıdır.');
            }
            $count = (int)$rule['COUNT'];
        }
        $until = null;
        if (isset($rule['UNTIL'])) {
            $until = self::dateValue($rule['UNTIL'], 'DTSTART');
            if ($until === null) throw new RuntimeException('iCal RRULE UNTIL tarihi geçersiz.');
        }
        $byDays = [];
        if (isset($rule['BYDAY'])) {
            foreach (explode(',', $rule['BYDAY']) as $day) {
                if (!isset(self::WEEKDAYS[$day])) throw new RuntimeException('iCal RRULE BYDAY yalnızca MO,TU,WE,TH,FR,SA,SU değerlerini destekler.');
                $byDays[self::WEEKDAYS[$day]] = true;
            }
            if ($frequency === 'WEEKLY' && !$byDays) throw new RuntimeException('iCal RRULE BYDAY listesi boş olamaz.');
        }

        $start = new DateTimeImmutable($event['date_from'], new DateTimeZone('UTC'));
        $end = new DateTimeImmutable($event['date_to'], new DateTimeZone('UTC'));
        $duration = (int)$start->diff($end)->days;
        $hardLimit = $start->modify('+730 days');
        $last = $until !== null ? min($hardLimit, new DateTimeImmutable($until, new DateTimeZone('UTC'))) : $hardLimit;
        $occurrences = [];
        $matched = 0;
        for ($cursor = $start; $cursor <= $last; $cursor = $cursor->modify('+1 day')) {
            $daysFromStart = (int)$start->diff($cursor)->days;
            $matches = false;
            if ($daysFromStart === 0) {
                $matches = true;
            } elseif ($frequency === 'DAILY') {
                $matches = $daysFromStart % $interval === 0
                    && (!$byDays || isset($byDays[(int)$cursor->format('N')]));
            } else {
                $startWeek = $start->modify('monday this week');
                $cursorWeek = $cursor->modify('monday this week');
                $weeks = intdiv((int)$startWeek->diff($cursorWeek)->days, 7);
                $matches = $weeks % $interval === 0
                    && isset(($byDays ?: [(int)$start->format('N')=>true])[(int)$cursor->format('N')]);
            }
            if (!$matches) continue;
            $matched++;
            if ($count !== null && $matched > $count) break;
            $date = $cursor->format('Y-m-d');
            if (isset($excluded[$date])) continue;
            $copy = $event;
            $copy['uid'] = substr((string)$event['uid'], 0, 220) . '#' . $cursor->format('Ymd') . '#' . substr(hash('sha256', (string)$event['uid']), 0, 12);
            $copy['date_from'] = $date;
            $copy['date_to'] = $cursor->modify('+' . $duration . ' days')->format('Y-m-d');
            $occurrences[] = $copy;
            if (count($occurrences) > 500) throw new RuntimeException('Yinelenen iCal etkinlikleri 500 kayıt sınırını aşıyor.');
        }
        return $occurrences;
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
