Ext.define('Ufolep13Volley.model.Day', Sencha.modelCompatibility({
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
            name: 'libelle_competition',
            type: 'string'
        },
        {
            name: 'numero',
            type: 'int'
        },
        {
            name: 'nommage',
            type: 'string'
        },
        {
            name: 'libelle',
            type: 'string'
        }
    ]
}));
