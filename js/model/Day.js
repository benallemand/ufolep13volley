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
        },
        {
            name: 'start_date',
            type: 'date',
            dateFormat: 'd/m/Y'
        },
        {
            name: 'display_combo',
            type: 'string',
            convert: function (val, rec) {
                return rec.get('libelle_competition') + ' - ' + rec.get('nommage') + ' (' + rec.get('libelle') + ')';
            }
        }
    ]
}));
