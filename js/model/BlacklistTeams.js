Ext.define('Ufolep13Volley.model.BlacklistTeams', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        {
            name: 'id_team_1',
            type: 'int'
        },
        'libelle_equipe_1',
        {
            name: 'id_team_2',
            type: 'int'
        },
        'libelle_equipe_2'
    ]
}));
