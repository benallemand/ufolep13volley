Ext.define('Ufolep13Volley.model.BlacklistDate', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        {
            name: 'closed_date',
            type: 'date',
            dateFormat: 'd/m/Y'
        }
    ]
});
