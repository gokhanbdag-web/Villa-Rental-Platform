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
