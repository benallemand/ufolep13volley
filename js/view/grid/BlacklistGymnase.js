Ext.define('Ufolep13Volley.view.grid.BlacklistGymnase', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.blacklistgymnase_grid',
    title: 'Dates blacklistées (par gymnase)',
    store: {type: 'BlacklistGymnase'},
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