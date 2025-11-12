Ext.define('Ufolep13Volley.model.email', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id', type: 'int'},
        {name: 'from_email', type: 'string'},
        {name: 'to_email', type: 'string'},
        {name: 'cc', type: 'string'},
        {name: 'bcc', type: 'string'},
        {name: 'subject', type: 'string'},
        {name: 'body', type: 'string'},
        {name: 'creation_date', type: 'date', dateFormat: 'd/m/Y H:i:s'},
        {name: 'sent_date', type: 'date', dateFormat: 'd/m/Y H:i:s'},
        {name: 'sending_status', type: 'string'},
    ]
});
