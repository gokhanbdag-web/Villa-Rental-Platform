<?php
declare(strict_types=1);

$financialSummary = isset($financialSummary) && is_array($financialSummary) ? $financialSummary : [];
$payoutSummary = isset($payoutSummary) && is_array($payoutSummary) ? $payoutSummary : [];
$recentFinancials = isset($recentFinancials) && is_array($recentFinancials) ? $recentFinancials : [];
$recentTransactions = isset($recentTransactions) && is_array($recentTransactions) ? $recentTransactions : [];
$recentRefunds = isset($recentRefunds) && is_array($recentRefunds) ? $recentRefunds : [];
$money = static fn($amount, string $currency): string => number_format((float)$amount, 2, ',', '.') . ' ' . e($currency);
$payoutLabels = ['unpaid'=>'Awaiting payment','requested'=>'Payment requested','processing'=>'Processing','paid'=>'Paid','failed'=>'Failed','cancelled'=>'Cancelled'];
$paymentLabels = ['pending'=>'Pending','paid'=>'Paid','failed'=>'Failed','cancelled'=>'Cancelled','refunded'=>'Refunded'];
$refundLabels = ['processing'=>'Processing','succeeded'=>'Successful','failed'=>'Failed','unknown'=>'Outcome uncertain'];
?>
<!doctype html>
<html lang="<?= e(\App\Core\Translator::locale()) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(villa_ui('Finance and payments')) ?></title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f5f7f6;color:#26352d;font-family:system-ui,-apple-system,sans-serif}
.wrap{max-width:1200px;margin:28px auto;padding:0 18px}.heading{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap}
h1{font-size:27px;margin:0 0 6px}.muted{color:#718077;font-size:13px;line-height:1.6;margin:0}
.notice{margin:18px 0;padding:13px 15px;border:1px solid #f0dfb6;background:#fff9e9;color:#76591f;border-radius:11px;font-size:13px}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin:18px 0}
.card,.panel{background:#fff;border:1px solid #e5ece7;border-radius:13px;padding:17px;min-width:0}
.card small{display:block;color:#718077;font-size:12px;font-weight:700}.card strong{display:block;font-size:22px;margin-top:9px;overflow-wrap:anywhere}
.panel{margin:14px 0}.panel h2{font-size:16px;margin:0 0 13px}.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:680px}th,td{text-align:left;padding:11px 9px;border-bottom:1px solid #edf1ee;font-size:12px;white-space:nowrap}
th{color:#829087;font-size:10px;text-transform:uppercase;letter-spacing:.5px}td strong{display:block;color:#34443a}td small{display:block;color:#89958e;margin-top:3px}
.pill{display:inline-block;padding:5px 8px;border-radius:20px;background:#f0f3f1;color:#58665e;font-size:10px;font-weight:800}
.empty{padding:20px;text-align:center;color:#829087;font-size:13px}
@media(max-width:760px){.grid{grid-template-columns:1fr}.wrap{margin:18px auto;padding:0 12px}.card{padding:14px}.heading h1{font-size:23px}}
</style>
</head>
<body><main class="wrap">
<header class="heading"><div><h1><?= e(villa_ui('Finance and payments')) ?></h1><p class="muted"><?= e(villa_ui('View booking revenue, commissions, host balances and payment activity.')) ?></p></div><a href="/admin/payment-tests" style="display:inline-block;padding:11px 15px;background:#176b50;color:#fff;border-radius:9px;text-decoration:none;font-weight:700;font-size:13px"><?= e(villa_ui('Payment test center')) ?></a></header>
<?php if (!$paymentsEnabled || $paymentProvider === 'none'): ?>
<div class="notice"><strong><?= e(villa_ui('Online payments are disabled.')) ?></strong> <?= e(villa_ui('This screen lists booking transactions and payment activity. Card collection is disabled because no provider is enabled.')) ?></div>
<?php endif; ?>
<section class="grid">
<?php if (!$financialSummary): ?><article class="card"><small><?= e(villa_ui('Financial records')) ?></small><strong><?= e(villa_ui('No records yet')) ?></strong></article><?php endif; ?>
<?php foreach ($financialSummary as $row): ?>
<article class="card"><small><?= e($row['currency']) ?> <?= e(villa_ui('· Gross booking amount')) ?></small><strong><?= $money($row['gross_amount'],$row['currency']) ?></strong><p class="muted"><?= (int)$row['booking_count'] ?> <?= e(villa_ui('bookings')) ?></p></article>
<article class="card"><small><?= e($row['currency']) ?> <?= e(villa_ui('· Platform commission')) ?></small><strong><?= $money($row['platform_fee'],$row['currency']) ?></strong></article>
<article class="card"><small><?= e($row['currency']) ?> <?= e(villa_ui('· Net host earnings')) ?></small><strong><?= $money($row['vendor_net_amount'],$row['currency']) ?></strong></article>
<?php endforeach; ?>
</section>
<section class="panel"><h2><?= e(villa_ui('Host payout requests')) ?></h2>
<?php if (!empty($adminPayoutMessage)): ?><div class="notice" role="status"><?= e($adminPayoutMessage) ?></div><?php endif; ?>
<?php if (!$recentPayouts): ?><div class="empty"><?= e(villa_ui('No payout requests yet.')) ?></div><?php else: ?><div class="table-wrap"><table><thead><tr><th><?= e(villa_ui('Request')) ?></th><th><?= e(villa_ui('Host')) ?></th><th><?= e(villa_ui('Bank account')) ?></th><th><?= e(villa_ui('Amount')) ?></th><th><?= e(villa_ui('Status')) ?></th><th><?= e(villa_ui('Bank reference')) ?></th><th><?= e(villa_ui('Action')) ?></th></tr></thead><tbody>
<?php foreach ($recentPayouts as $payout): ?><tr><td><strong>#<?= (int)$payout['id'] ?></strong><small><?= e(date('d.m.Y H:i',strtotime($payout['requested_at']))) ?></small></td><td><strong><?= e($payout['business_name']) ?></strong><small><?= e($payout['vendor_email']) ?></small></td><td><strong><?= e($payout['payout_bank_name']??'—') ?></strong><small><?= e($payout['payout_account_holder']??'') ?></small><small><?= e($payout['payout_iban']??'IBAN eksik') ?></small></td><td><?= $money($payout['amount'],$payout['currency']) ?></td><td><span class="pill"><?= e($payoutLabels[$payout['status']]??$payout['status']) ?></span></td><td><?= e($payout['payout_reference']??'—') ?></td><td>
<?php if (in_array($payout['status'],['requested','processing'],true)): ?><form method="post" action="/admin/finance/payout/update" style="display:grid;gap:6px;min-width:190px"><?= csrf_field() ?><input type="hidden" name="payout_id" value="<?= (int)$payout['id'] ?>"><select name="status" required><option value="" selected disabled><?= e(villa_ui('Select a new status')) ?></option><option value="processing" <?= $payout['status']==='processing'?'disabled':'' ?>><?= e(villa_ui('Processing')) ?></option><option value="paid"><?= e(villa_ui('Paid')) ?></option><option value="failed"><?= e(villa_ui('Failed')) ?></option><option value="cancelled"><?= e(villa_ui('Cancel booking')) ?></option></select><input name="payout_reference" maxlength="190" placeholder="Banka işlem referansı (ödendiğinde zorunlu)"><input name="notes" maxlength="2000" placeholder="Yönetici notu"><button type="submit" style="border:0;border-radius:7px;background:#176b50;color:white;padding:9px;font-weight:700"><?= e(villa_ui('Save status')) ?></button></form><?php else: ?><span class="muted"><?= e(villa_ui('Completed')) ?></span><?php endif; ?>
</td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<?php if (!$payoutSummary): ?><div class="empty"><?= e(villa_ui('No payment requests yet.')) ?></div><?php else: ?><div class="table-wrap"><table><thead><tr><th><?= e(villa_ui('Currency')) ?></th><th><?= e(villa_ui('Status')) ?></th><th><?= e(villa_ui('Number of requests')) ?></th><th><?= e(villa_ui('Total amount')) ?></th></tr></thead><tbody>
<?php foreach ($payoutSummary as $row): ?><tr><td><?= e($row['currency']) ?></td><td><span class="pill"><?= e($payoutLabels[$row['status']]??$row['status']) ?></span></td><td><?= (int)$row['payout_count'] ?></td><td><strong><?= $money($row['payout_amount'],$row['currency']) ?></strong></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<section class="panel"><h2><?= e(villa_ui('Recent booking transactions')) ?></h2>
<?php if (!$recentFinancials): ?><div class="empty"><?= e(villa_ui('No transactions yet.')) ?></div><?php else: ?><div class="table-wrap"><table><thead><tr><th><?= e(villa_ui('Booking')) ?></th><th><?= e(villa_ui('Property')) ?></th><th><?= e(villa_ui('Gross')) ?></th><th><?= e(villa_ui('Commission')) ?></th><th><?= e(villa_ui('Host net')) ?></th><th><?= e(villa_ui('Payment status')) ?></th></tr></thead><tbody>
<?php foreach ($recentFinancials as $row): ?><tr><td><strong><?= e($row['booking_code']) ?></strong><small><?= e(date('d.m.Y',strtotime($row['created_at']))) ?></small></td><td><?= e($row['property_title']) ?></td><td><?= $money($row['gross_amount'],$row['currency']) ?></td><td><?= $money($row['platform_fee'],$row['currency']) ?></td><td><?= $money($row['vendor_net_amount'],$row['currency']) ?></td><td><span class="pill"><?= e($payoutLabels[$row['payout_status']]??$row['payout_status']) ?></span></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<section class="panel"><h2><?= e(villa_ui('Payment history and refunds')) ?></h2><p><a href="/admin/calendar-sync"><?= e(villa_ui('Calendar synchronization and cron settings →')) ?></a></p>
<?php if (!empty($adminRefundMessage)): ?><div class="notice" role="status"><?= e($adminRefundMessage) ?></div><?php endif; ?>
<p class="muted"><?= e(villa_ui('The iyzico refund form sends a real refund request. Verify the request first. Check uncertain refunds in the provider dashboard before retrying.')) ?></p>
<?php if (!$recentTransactions): ?><div class="empty"><?= e(villa_ui('No online transactions yet.')) ?></div><?php else: ?><div class="table-wrap"><table><thead><tr><th><?= e(villa_ui('Date')) ?></th><th><?= e(villa_ui('Booking')) ?></th><th><?= e(villa_ui('Provider')) ?></th><th><?= e(villa_ui('Amount')) ?></th><th><?= e(villa_ui('Status')) ?></th><th><?= e(villa_ui('Reference')) ?></th><th><?= e(villa_ui('Refund action')) ?></th></tr></thead><tbody>
<?php foreach ($recentTransactions as $row): $refundRemaining=max(0,(float)$row['amount']-(float)($row['refunded_amount']??0)); ?><tr><td><?= e(date('d.m.Y H:i',strtotime($row['created_at']))) ?></td><td><strong><?= e($row['booking_code']) ?></strong><small><?= e($row['property_title']) ?></small></td><td><?= e($row['provider']) ?></td><td><?= $money($row['amount'],$row['currency']) ?><small><?= e(villa_ui('Reserved/refunded amount:')) ?> <?= $money($row['refunded_amount']??0,$row['currency']) ?></small></td><td><span class="pill"><?= e($paymentLabels[$row['status']]??$row['status']) ?></span></td><td><?= e($row['provider_reference']??'—') ?></td><td>
<?php if ($row['provider']==='iyzico' && $row['status']==='paid' && $refundRemaining>0 && (int)($row['unresolved_refunds']??0)===0): ?><form method="post" action="/admin/finance/refund/iyzico" style="display:grid;gap:6px;min-width:230px"><?= csrf_field() ?><input type="hidden" name="payment_transaction_id" value="<?= (int)$row['id'] ?>"><label><?= e(villa_ui('Refund amount')) ?><input type="number" name="amount" min="0.01" max="<?= number_format($refundRemaining,2,'.','') ?>" step="0.01" value="<?= number_format($refundRemaining,2,'.','') ?>" required style="display:block;width:100%;padding:8px;border:1px solid #dfe6e0;border-radius:7px"></label><input name="reason" maxlength="500" placeholder="<?= e(villa_ui('Refund reason (optional)')) ?>"><label style="white-space:normal"><input type="checkbox" name="confirm_real_refund" value="1" required> <?= e(villa_ui('I confirm that this sends a real iyzico refund.')) ?></label><button type="submit" style="border:0;border-radius:7px;background:#9c3e36;color:white;padding:9px;font-weight:700"><?= e(villa_ui('Send iyzico refund')) ?></button></form>
<?php elseif ((int)($row['unresolved_refunds']??0)>0): ?><span class="muted"><?= e(villa_ui('Processing or unknown result. Check the provider dashboard before retrying.')) ?></span><?php elseif ($row['provider']!=='iyzico'): ?><span class="muted"><?= e(villa_ui('Automatic refunds are unavailable for this provider.')) ?></span><?php else: ?><span class="muted"><?= e(villa_ui('No refundable balance.')) ?></span><?php endif; ?>
</td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<section class="panel"><h2><?= e(villa_ui('Refund history')) ?></h2><?php if(!$recentRefunds): ?><div class="empty"><?= e(villa_ui('No refunds yet.')) ?></div><?php else: ?><div class="table-wrap"><table><thead><tr><th><?= e(villa_ui('Date')) ?></th><th><?= e(villa_ui('Booking')) ?></th><th><?= e(villa_ui('Amount')) ?></th><th><?= e(villa_ui('Status')) ?></th><th><?= e(villa_ui('Provider reference')) ?></th><th><?= e(villa_ui('Note / error')) ?></th></tr></thead><tbody><?php foreach($recentRefunds as $refund): ?><tr><td><?= e(date('d.m.Y H:i',strtotime($refund['created_at']))) ?></td><td><strong><?= e($refund['booking_code']) ?></strong><small><?= e($refund['property_title']) ?></small></td><td><?= $money($refund['amount'],$refund['currency']) ?></td><td><span class="pill"><?= e($refundLabels[$refund['status']]??$refund['status']) ?></span></td><td><?= e($refund['provider_reference']??$refund['conversation_id']) ?></td><td><?= e($refund['error_message']??$refund['reason']??'—') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
</main></body></html>
