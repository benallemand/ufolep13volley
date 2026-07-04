Ext.define('Ufolep13Volley.model.User', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id',
            type: 'int'
        },
        'login',
        'password_hash',
        'email',
        {
            name: 'id_team',
            type: 'int'
        },
        'team_name',
        'club_name',
        {
            name: 'is_admin',
            type: 'boolean'
        }
    ]
});
