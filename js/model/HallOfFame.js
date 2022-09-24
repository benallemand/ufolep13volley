Ext.define('Ufolep13Volley.model.HallOfFame', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        'title',
        'team_name',
        'period',
        'league'
    ]
});
