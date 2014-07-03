Ext.define('Ufolep13Volley.model.User', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        'login',
        'password',
        {
            name: 'id_team',
            type: 'int'
        },
        'team_name',
        'club_name',
        'profile',
        {
            name: 'id_profile',
            type: 'int'
        }
    ]
});
