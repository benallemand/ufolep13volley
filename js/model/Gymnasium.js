Ext.define('Ufolep13Volley.model.Gymnasium', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
    fields: [
        'full_name',
        'nom',
        'adresse',
        'code_postal',
        'ville',
        'gps',
        {
            name: 'id',
            type: 'int'
        }
    ]
}));
