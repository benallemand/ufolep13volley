Ext.define('Ufolep13Volley.model.LimitDate', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id_date',
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
            name: 'date_limite',
            type: 'string'
        }
    ]
}));
