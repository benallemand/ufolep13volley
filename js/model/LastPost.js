Ext.define('Ufolep13Volley.model.LastPost', Sencha.modelCompatibility({
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
}));
