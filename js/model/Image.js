Ext.define('Ufolep13Volley.model.Image', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
    fields: [
        'farm',
        'id',
        'isfamily',
        'isfriend',
        'ispublic',
        'owner',
        'secret',
        'server',
        'title',
        {
            name: 'src',
            type: 'string',
            convert: function (val, record) {
                return Ext.String.format("https://farm{0}.staticflickr.com/{1}/{2}_{3}.jpg", record.get('farm'), record.get('server'), record.get('id'), record.get('secret'));
            }
        }
    ]
}));