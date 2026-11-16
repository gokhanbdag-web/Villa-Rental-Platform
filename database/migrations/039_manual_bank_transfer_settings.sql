-- Manual bank transfer configuration. Public account details are not secrets.
INSERT IGNORE INTO settings(setting_key,setting_value,autoload) VALUES
('payments.manual.enabled','0',1),
('payments.manual.bank_name','',1),
('payments.manual.account_holder','',1),
('payments.manual.iban','',1),
('payments.manual.instructions','',1),
('payments.manual.reference_prefix','VILLA',1);
