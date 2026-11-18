<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\CalendarFeedFetcher;
use App\Core\IcalParser;

function icalAssert(bool $condition,string $message):void
{
    if(!$condition) throw new RuntimeException($message);
}

$ics="BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:airbnb-reservation-001\r\nDTSTART;VALUE=DATE:20261201\r\nDTEND;VALUE=DATE:20261204\r\nSUMMARY:Guest\\, stay\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nUID:cancelled-reservation-002\r\nDTSTART;TZID=Europe/Istanbul:20261205T150000\r\nDTEND;TZID=Europe/Istanbul:20261207T100000\r\nSTATUS:CANCELLED\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
$events=IcalParser::parse($ics);
icalAssert(count($events)===2,'iCalendar VEVENT parsing did not return both events.');
icalAssert($events[0]['uid']==='airbnb-reservation-001'&&$events[0]['date_from']==='2026-12-01'&&$events[0]['date_to']==='2026-12-04','All-day iCalendar date or exclusive DTEND parsing failed.');
icalAssert($events[0]['summary']==='Guest, stay','iCalendar text escaping was not decoded.');
icalAssert($events[1]['cancelled']===true&&$events[1]['date_from']==='2026-12-05','Cancelled TZID event parsing failed.');

$recurring="BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:weekly-villa\r\nDTSTART;VALUE=DATE:20261207\r\nDTEND;VALUE=DATE:20261208\r\nRRULE:FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=5\r\nEXDATE;VALUE=DATE:20261209\r\nSUMMARY:Weekly stay\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nUID:daily-villa\r\nDTSTART;VALUE=DATE:20261201\r\nDTEND;VALUE=DATE:20261203\r\nRRULE:FREQ=DAILY;INTERVAL=2;COUNT=3\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
$expanded=IcalParser::parse($recurring);
icalAssert(count($expanded)===7,'Bounded DAILY/WEEKLY recurrence expansion or EXDATE filtering failed.');
$weekly=array_values(array_filter($expanded,static fn(array $event):bool=>str_starts_with($event['uid'],'weekly-villa#')));
$weeklyDates=array_column($weekly,'date_from');
icalAssert($weeklyDates===['2026-12-07','2026-12-11','2026-12-14','2026-12-16'],'WEEKLY BYDAY/COUNT/EXDATE expansion returned incorrect dates.');
$daily=array_values(array_filter($expanded,static fn(array $event):bool=>str_starts_with($event['uid'],'daily-villa#')));
icalAssert(array_column($daily,'date_from')===['2026-12-01','2026-12-03','2026-12-05'],'DAILY INTERVAL/COUNT expansion returned incorrect dates.');
$unsupported=false;
try{IcalParser::parse("BEGIN:VCALENDAR\nBEGIN:VEVENT\nUID:monthly\nDTSTART;VALUE=DATE:20261201\nDTEND;VALUE=DATE:20261202\nRRULE:FREQ=MONTHLY;COUNT=2\nEND:VEVENT\nEND:VCALENDAR");}catch(RuntimeException){$unsupported=true;}
icalAssert($unsupported,'Unsupported recurrence rules must fail closed instead of silently importing one date.');

$rejectedUrls=[
    'http://example.com/calendar.ics',
    'https://127.0.0.1/calendar.ics',
    'https://10.0.0.8/calendar.ics',
    'https://localhost/calendar.ics',
    'https://user:password@example.com/calendar.ics',
    'https://example.com:8443/calendar.ics',
    'https://example.com./calendar.ics',
    'https://internal.example.local/calendar.ics',
];
foreach($rejectedUrls as $url){
    $rejected=false;
    try{CalendarFeedFetcher::validateUrl($url);}catch(RuntimeException){$rejected=true;}
    icalAssert($rejected,'Unsafe iCal URL was accepted: '.$url);
}
echo "iCalendar parser and SSRF validation smoke tests passed. No network requests were made.\n";
