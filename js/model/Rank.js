Ext.define('Ufolep13Volley.model.Rank', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        {
            name: 'code_competition',
            type: 'string'
        },
        {
            name: 'nom_competition',
            type: 'string'
        },
        {
            name: 'division',
            type: 'string'
        },
        {
            name: 'id_equipe',
            type: 'int'
        },
        {
            name: 'nom_equipe',
            type: 'string'
        },
        {
            name: 'rank_start',
            type: 'int'
        },
        {
            name: 'will_register_again',
            type: 'bool',
        },
    ]
});
