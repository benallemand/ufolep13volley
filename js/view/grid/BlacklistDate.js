Ext.define('Ufolep13Volley.view.grid.BlacklistDate', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.blacklistdate_grid',
    title: 'Dates blacklistées (fériés)',
    store: {type: 'BlacklistDate'},
    columns: {
        items: [
            {
                header: 'Date blacklistée',
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'closed_date',
                flex: 1
            }
        ]
    }
});