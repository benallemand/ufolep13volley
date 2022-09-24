Ext.define('Ufolep13Volley.model.BlacklistTeam', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        {
            name: 'id_team',
            type: 'int'
        },
        'libelle_equipe',
        {
            name: 'closed_date',
            type: 'date',
            dateFormat: 'd/m/Y'
        }
    ]
});
