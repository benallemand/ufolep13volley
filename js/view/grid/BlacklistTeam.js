Ext.define('Ufolep13Volley.view.grid.BlacklistTeam', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.blacklistteam_grid',
    title: 'Dates blacklistées (par équipe)',
    autoScroll: true,
    store: {type: 'BlacklistTeam'},
    selType: 'checkboxmodel',
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