Ext.define('Ufolep13Volley.model.News', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        {
            name: 'title',
            type: 'string'
        },
        {
            name: 'text',
            type: 'string'
        },
        {
            name: 'file_path',
            type: 'string'
        },
        {
            name: 'news_date',
            type: 'date',
            dateFormat: 'Y-m-d'
        },
        {
            name: 'is_disabled',
            type: 'int'
        }
    ]
});
