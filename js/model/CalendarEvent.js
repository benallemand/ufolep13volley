Ext.define('Ufolep13Volley.model.CalendarEvent', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        {
            name: 'season',
            type: 'string'
        },
        {
            name: 'label',
            type: 'string'
        },
        {
            name: 'date_start',
            type: 'date',
            dateFormat: 'Y-m-d H:i:s'
        },
        {
            name: 'date_end',
            type: 'date',
            dateFormat: 'Y-m-d H:i:s',
            allowNull: true
        }
    ]
});
