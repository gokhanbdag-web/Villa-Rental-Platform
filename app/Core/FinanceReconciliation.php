<?php
declare(strict_types=1);
namespace App\Core;
use PDO;

/** Read-only finance audit. All amounts are compared in integer minor units. */
final class FinanceReconciliation
{
    public static function scan(PDO $pdo, int $limit=500): array
    {
        if($limit<1||$limit>5000)throw new \InvalidArgumentException('Geçersiz tarama sınırı.');
        $query=$pdo->prepare("SELECT bf.booking_id,bf.currency,bf.gross_amount,bf.platform_fee,bf.vendor_net_amount,bf.payout_status,
          COALESCE((SELECT SUM(vpa.amount) FROM vendor_payout_allocations vpa JOIN vendor_payouts vp ON vp.id=vpa.payout_id WHERE vpa.booking_id=bf.booking_id AND vp.status IN ('requested','processing','paid')),0) AS allocated,
          COALESCE((SELECT SUM(vpa.amount) FROM vendor_payout_allocations vpa JOIN vendor_payouts vp ON vp.id=vpa.payout_id WHERE vpa.booking_id=bf.booking_id AND vp.status='paid'),0) AS paid_allocated,
          (SELECT COUNT(*) FROM manual_bank_refunds mr WHERE mr.booking_id=bf.booking_id AND mr.status IN ('pending','processing')) AS unresolved_bank_refunds
          FROM booking_financials bf ORDER BY bf.booking_id DESC LIMIT ?");
        $query->bindValue(1,$limit,PDO::PARAM_INT);$query->execute();
        $issues=[];$checked=0;
        foreach($query->fetchAll(PDO::FETCH_ASSOC) as $row){
            $checked++;$id=(int)$row['booking_id'];
            $cents=static fn($value):int=>(int)round((float)$value*100);
            $gross=$cents($row['gross_amount']);$fee=$cents($row['platform_fee']);$vendor=$cents($row['vendor_net_amount']);
            $allocated=$cents($row['allocated']);$paid=$cents($row['paid_allocated']);
            $flags=[];
            if($gross<0||$fee<0||$vendor<0||$gross!==$fee+$vendor)$flags[]='financial_split_mismatch';
            if($allocated>$vendor)$flags[]='payout_overallocated';
            if($paid>$vendor)$flags[]='paid_payout_exceeds_vendor_net';
            if((int)$row['unresolved_bank_refunds']>0&&$allocated>0)$flags[]='refund_payout_conflict';
            if($allocated===0&&$row['payout_status']==='processing')$flags[]='stale_payout_status';
            if($paid===0&&$row['payout_status']==='paid')$flags[]='paid_status_without_allocation';
            foreach($flags as $flag)$issues[]=['booking_id'=>$id,'currency'=>(string)$row['currency'],'code'=>$flag];
        }
        return ['checked'=>$checked,'issue_count'=>count($issues),'issues'=>$issues,'scanned_at'=>gmdate('c'),'limit'=>$limit];
    }
}
