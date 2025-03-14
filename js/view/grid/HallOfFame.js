Ext.define('Ufolep13Volley.view.grid.HallOfFame', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.hall_of_fame_grid',
    title: 'Palmarès',
    store: {type: 'HallOfFame'},
    features: [
        {
            ftype: 'grouping',
        }
    ],
    columns: {
        items: [
            {
                header: 'Titre',
                dataIndex: 'title',
                flex: 1
            },
            {
                header: 'Equipe',
                dataIndex: 'team_name',
                flex: 1
            },
            {
                header: 'Année',
                dataIndex: 'period',
                flex: 1
            },
            {
                header: 'Catégorie',
                dataIndex: 'league',
                flex: 1
            }
        ]
    }
});