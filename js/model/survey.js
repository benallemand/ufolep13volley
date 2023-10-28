Ext.define('Ufolep13Volley.model.survey', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id_match',
            type: 'int'
        },
        {
            name: 'confrontation',
            convert: function (val, record) {
                return Ext.String.format("<h2>{0} vs {1}</h2>", record.get('equipe_dom'), record.get('equipe_ext'))

            }
        },
        {
            name: 'equipe_dom',
            type: 'string'
        },
        {
            name: 'equipe_ext',
            type: 'string'
        },
        {
            name: 'on_time',
            type: 'int',
        },
        {
            name: 'spirit',
            type: 'int',
        },
        {
            name: 'referee',
            type: 'int',
        },
        {
            name: 'catering',
            type: 'int',
        },
        {
            name: 'global',
            type: 'int',
        },
        {
            name: 'comment',
            type: 'string',
        },
    ]
});
