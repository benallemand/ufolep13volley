Ext.define('Ufolep13Volley.model.Competition', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id', type: 'int'},
        'code_competition',
        'libelle',
        {name: 'id_compet_maitre', type: 'int'}
    ]
}));
