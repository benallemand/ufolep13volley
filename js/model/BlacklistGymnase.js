Ext.define('Ufolep13Volley.model.BlacklistGymnase', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        {
            name: 'id_gymnase',
            type: 'int'
        },
        {
            name: 'closed_date',
            type: 'date',
            dateFormat: 'd/m/Y'
        }
    ]
});
