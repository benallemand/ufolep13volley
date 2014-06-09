Ext.define('Ufolep13Volley.model.LastPost', {
    extend: 'Ext.data.Model',
    fields: [
        'title',
        'creator',
        'category',
        {
            name: 'pubdate',
            type: 'date'
        },
        'description',
        'guid'
    ]
});
