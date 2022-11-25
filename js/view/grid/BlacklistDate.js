Ext.define('Ufolep13Volley.view.grid.BlacklistDate', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.blacklistdate_grid',
    title: 'Dates blacklistées (fériés)',
    autoScroll: true,
    store: {type: 'BlacklistDate'},
    selType: 'checkboxmodel',
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