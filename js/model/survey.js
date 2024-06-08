Ext.define('Ufolep13Volley.model.survey', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id_match',
            type: 'int'
        },
        {
            name: 'code_match',
            type: 'string'
        },
        {
            name: 'confrontation',
            convert: function (val, record) {
                return Ext.String.format("<h2>{0} vs {1}</h2>", record.get('equipe_dom'), record.get('equipe_ext'));
            }
        },
        {
            name: 'match',
            convert: function (val, record) {
                return Ext.String.format("{0} ({1} vs {2})", record.get('code_match'), record.get('equipe_dom'), record.get('equipe_ext'));
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
        {
            name: 'surveyed',
            type: 'string',
        },
        {
            name: 'surveyed_club',
            type: 'string',
        },
        {
            name: 'surveyor',
            type: 'string',
        },
    ]
});
