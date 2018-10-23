Ext.define('Ufolep13Volley.view.grid.BlacklistGymnase', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.blacklistgymnase_grid',
    title: 'Dates blacklistées',
    autoScroll: true,
    store: 'BlacklistGymnase',
    selType: 'checkboxmodel',
    columns: {
        items: [
            {
                header: 'Gymnase',
                dataIndex: 'libelle_gymnase',
                flex: 1
            },
            {
                header: 'Date de fermeture',
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'closed_date',
                flex: 1
            }
        ]
    }
});