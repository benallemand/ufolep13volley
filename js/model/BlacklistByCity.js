Ext.define('Ufolep13Volley.model.BlacklistByCity', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        'city',
        {
            name: 'from_date',
            type: 'date',
            dateFormat: 'd/m/Y'
        },
        {
            name: 'to_date',
            type: 'date',
            dateFormat: 'd/m/Y'
        }
    ]
});
