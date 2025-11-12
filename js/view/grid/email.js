Ext.define('Ufolep13Volley.view.grid.email', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.grid_email',
    title: 'Emails',
    store:
        {
            type: 'email',
        },
    selModel: 'rowmodel',
    columns: [
        {header: 'id', dataIndex: 'id',},
        {header: 'From', dataIndex: 'from_email',},
        {header: 'To', dataIndex: 'to_email',},
        {header: 'Cc', dataIndex: 'cc',},
        {header: 'Bcc', dataIndex: 'bcc',},
        {header: 'Sujet', dataIndex: 'subject',},
        {header: 'Detail', dataIndex: 'body', flex: 1,},
        {
            header: 'Créé le', dataIndex: 'creation_date', xtype: 'datecolumn',
            format: 'd/m/Y H:i:s',
        },
        {
            header: 'Envoyé le', dataIndex: 'sent_date', xtype: 'datecolumn',
            format: 'd/m/Y H:i:s',
        },
        {header: 'Status', dataIndex: 'sending_status',},
    ],
});