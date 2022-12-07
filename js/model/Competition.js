Ext.define('Ufolep13Volley.model.Competition', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id', type: 'int'},
        'code_competition',
        'libelle',
        'id_compet_maitre', // code_parent_competition
        {
            name: 'start_date',
            type: 'date',
            dateFormat: 'd/m/Y'
        },
        {
            name: 'is_home_and_away',
            type: 'bool',
        }
    ]
});
