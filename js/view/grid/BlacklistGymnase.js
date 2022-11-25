Ext.define('Ufolep13Volley.view.grid.BlacklistGymnase', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.blacklistgymnase_grid',
    title: 'Dates blacklistées (par gymnase)',
    autoScroll: true,
    store: {type: 'BlacklistGymnase'},
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