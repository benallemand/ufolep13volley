UPDATE emails
SET sending_status = 'TO_DO'
WHERE sending_status = 'ERROR'