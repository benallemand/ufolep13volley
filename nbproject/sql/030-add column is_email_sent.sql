ALTER TABLE comptes_acces ADD COLUMN is_email_sent ENUM('O', 'N');
UPDATE comptes_acces SET is_email_sent = 'N';