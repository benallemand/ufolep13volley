Ext.define('Ufolep13Volley.view.grid.BlacklistTeams', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.blacklistteams_grid',
    title: 'Equipes non autorisées à jouer le même soir',
    store: {type: 'BlacklistTeams'},
    columns: {
        items: [
            {
                header: 'Equipe 1',
                dataIndex: 'libelle_equipe_1',
                flex: 1
            },
            {
                header: 'Equipe 2',
                dataIndex: 'libelle_equipe_2',
                flex: 1
            }
        ]
    }
});