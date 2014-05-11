Ext.define('Ufolep13Volley.model.Team', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id_equipe', type: 'int'},
        'code_competition',
        'nom_equipe',
        'team_full_name',
        {name: 'id_club', type: 'int'},
        'team_full_name'
    ]
});
