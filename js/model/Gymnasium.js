Ext.define('Ufolep13Volley.model.Gymnasium', {
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
        },
        {
            name: 'nb_terrain',
            type: 'int'
        }
    ]
});
