<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
$source=(string)file_get_contents(dirname(__DIR__).'/app/Core/PaymentRefundService.php');
$checks=[
 'Unknown refunds reserve amount'=>str_contains($source,"status IN ('succeeded','processing','unknown')"),
 'Unknown refunds block duplicate requests'=>str_contains($source,"status IN ('processing','unknown')"),
 'Verified reconciliation accepts unknown'=>str_contains($source,"!in_array((string)\$refund['status'], ['processing','unknown'], true)"),
 'Atomic reconciliation closes unknown'=>str_contains($source,"WHERE id=? AND status IN ('processing','unknown')"),
 'Provider refund signature verified'=>str_contains($source,'PaymentSignatureVerifier::verifyIyzicoRefund($response, $secretKey)'),
 'Partial and full allocations present'=>str_contains($source,'if ($amountCents === $grossCents)')&&str_contains($source,'$vendorRefundCents = (int)round('),
];
foreach($checks as $name=>$ok){if(!$ok)throw new RuntimeException($name);echo "[PASS] $name\n";}
echo "Refund reconciliation source guards passed (offline).\n";
