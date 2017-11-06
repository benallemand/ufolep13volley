Ext.define('Ufolep13Volley.model.Timeslot', Sencha.modelCompatibility({
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
            name: 'id_equipe',
            type: 'int'
        },
        'jour',
        'heure',
        {
            name: 'has_time_constraint',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        },
        'team_full_name',
        'gymnasium_full_name'
    ]
}));
