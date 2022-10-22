Ext.define('Ufolep13Volley.model.Timeslot', {
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
        },
        {
            name: 'usage_priority',
            type: 'int'
        },
        'team_full_name',
        'gymnasium_full_name'
    ]
});
