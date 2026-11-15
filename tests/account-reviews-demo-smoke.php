<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$routes=file_get_contents($root.'/routes/web.php');
$shell=file_get_contents($root.'/themes/default/partials/account-shell.php');
$accountTheme=file_get_contents($root.'/themes/default/partials/account-theme.php');
$reviewsPage=file_get_contents($root.'/themes/default/templates/pages/account/reviews.php');
$bookingsPage=file_get_contents($root.'/themes/default/templates/pages/account/bookings.php');
$notificationsPage=file_get_contents($root.'/themes/default/templates/pages/account/notifications.php');
$propertyPage=file_get_contents($root.'/themes/default/templates/pages/property.php');
$migration=file_get_contents($root.'/database/migrations/038_demo_villa_reviews.sql');

$checks=[
 'authenticated customer reviews route exists before review form route'=>strpos($routes,"$"."router->get('/account/reviews',")!==false && strpos($routes,"$"."router->get('/account/reviews',")<strpos($routes,"$"."router->get('/account/reviews/{bookingId}'"),
 'my reviews query is scoped to the signed-in customer and excludes demo content'=>str_contains($routes,'r.guest_user_id=?')&&str_contains($routes,'COALESCE(r.is_demo,0)=0'),
 'my reviews page renders rating, moderation status and property link'=>str_contains($reviewsPage,'/villa/')&&str_contains($reviewsPage,'Onay bekliyor')&&str_contains($reviewsPage,'Yayında')&&str_contains($reviewsPage,'/10'),
 'my reviews is present in account navigation'=>str_contains($shell,"['/account/reviews','Yorumlarım']"),
 'mobile account shell prevents horizontal viewport overflow'=>str_contains($accountTheme,'overflow-x:clip')&&str_contains($accountTheme,'.item>*{min-width:0;max-width:100%}')&&str_contains($accountTheme,'overflow-wrap:anywhere'),
 'booking and notification account pages use shared responsive theme'=>str_contains($bookingsPage,'account-theme.php')&&str_contains($notificationsPage,'account-theme.php'),
 'demo reviews are not attached to fabricated bookings'=>str_contains($migration,'MODIFY booking_id BIGINT UNSIGNED NULL')&&str_contains($migration,'SELECT')&&str_contains($migration,'NULL,'),
 'demo reviews are explicitly identified and excluded from customer-owned reviews'=>str_contains($migration,'is_demo')&&str_contains($migration,'demo-reviewer-')&&str_contains($migration,'inactive'),
 'five demo reviews are generated for each published property'=>str_contains($migration,'SELECT 1 AS slot_no UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5')&&str_contains($migration,"WHERE p.status='published'"),
 'public property page labels demo reviews and does not call all ratings verified'=>str_contains($propertyPage,'Demo yorum · Gerçek müşteri deneyimi değildir')&&!str_contains($propertyPage,'doğrulanmış değerlendirme'),
 'property average and individual review scores use prominent high-contrast rating badges'=>str_contains($propertyPage,'class="rating-score"')&&str_contains($propertyPage,'class="rating-count"')&&str_contains($propertyPage,'class="review-rating-score"')&&str_contains($propertyPage,'#fff4d5')&&str_contains($propertyPage,'font-size:clamp(20px,2vw,25px)'),
];

$failed=[];
foreach($checks as $label=>$passed){echo ($passed?'[PASS] ':'[FAIL] ').$label.PHP_EOL;if(!$passed)$failed[]=$label;}
if($failed){fwrite(STDERR,count($failed).' account/review smoke check(s) failed.'.PHP_EOL);exit(1);}
echo 'All account and demo review smoke checks passed.'.PHP_EOL;
