Ext.define('Ufolep13Volley.view.grid.BlacklistTeam', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.blacklistteam_grid',
    title: 'Dates blacklistées (par équipe)',
    store: {type: 'BlacklistTeam'},
    columns: {
        items: [
            {
                header: 'Equipe',
                dataIndex: 'libelle_equipe',
                flex: 1
            },
            {
                header: 'Date interdite',
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'closed_date',
                flex: 1
            }
        ]
    }
});