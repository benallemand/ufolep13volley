Ext.define('Ufolep13Volley.view.grid.Competitions', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.competitions_grid',
    title: 'Compétitions',
    autoScroll: true,
    store: 'Competitions',
    selType: 'checkboxmodel',
    columns: {
        items: [
            {
                header: 'Code compétition',
                dataIndex: 'code_competition',
                flex: 1
            },
            {
                header: 'Libellé',
                dataIndex: 'libelle',
                flex: 1
            },
            {
                header: 'Code compétition maître',
                dataIndex: 'id_compet_maitre',
                flex: 1
            },
            {
                header: 'Date de début',
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'start_date',
                flex: 1
            }
        ]
    }
});