SELECT id,
       from_email,
       to_email,
       cc,
       bcc,
       subject,
       body,
       creation_date,
       sent_date
FROM emails
WHERE sending_status = 'ERROR'
ORDER BY creation_date DESC