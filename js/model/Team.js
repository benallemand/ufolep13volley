Ext.define('Ufolep13Volley.model.Team', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id_equipe', type: 'int'},
        'parent_code_competition',
        'code_competition',
        'libelle_competition',
        'nom_equipe',
        'team_full_name',
        {name: 'id_club', type: 'int'},
        'club',
        'responsable',
        'telephone_1',
        'telephone_2',
        'email',
        'gymnasiums_list',
        'web_site',
        'path_photo',
        {name: 'is_active_team', type: 'bool'},
    ]
});
