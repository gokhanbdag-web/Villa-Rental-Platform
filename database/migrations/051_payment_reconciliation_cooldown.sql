-- Throttle provider lookups for stale iyzico sessions.
ALTER TABLE payment_transactions
    ADD COLUMN reconciliation_checked_at DATETIME NULL AFTER expires_at,
    ADD INDEX idx_payment_reconciliation_due (provider,status,expires_at,reconciliation_checked_at);
