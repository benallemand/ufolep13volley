Ext.define('Ufolep13Volley.view.user.Grid', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.usersgrid',
    title: 'Gestion des utilisateurs',
    store: {type: 'Users'},
    columns: {
        items: [
            {
                header: 'Club',
                dataIndex: 'club_name',
                flex: 1
            },
            {
                header: 'Equipe',
                dataIndex: 'team_name',
                flex: 1
            },
            {
                header: 'Login',
                dataIndex: 'login',
                flex: 1
            },
            {
                header: 'Email',
                dataIndex: 'email',
                flex: 1
            },
            {
                header: 'Clubs gérés',
                dataIndex: 'managed_club_names',
                flex: 1
            },
            {
                xtype: 'checkcolumn',
                header: 'Admin',
                dataIndex: 'is_admin',
                width: 80
            }
        ]
    },
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Editer',
                    action: 'edit'
                },
                {
                    text: 'équipes liées...',
                    action: 'manage_user_teams',
                },
                {
                    text: 'clubs liés...',
                    action: 'manage_user_clubs',
                },
                {
                    text: 'Supprimer',
                    action: 'delete'
                },
                {
                    text: 'Reset mot de passe',
                    action: 'reset_password'
                }
            ]
        },
    ]
});