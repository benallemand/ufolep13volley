Ext.define('Ufolep13Volley.model.Competition', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id', type: 'int'},
        'code_competition',
        'libelle',
        'id_compet_maitre',
        {
            name: 'start_date',
            type: 'date',
            dateFormat: 'd/m/Y'
        },
        {
            name: 'is_home_and_away',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        }
    ]
}));
