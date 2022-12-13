Ext.define('Ufolep13Volley.view.user.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.usersgrid',
    title: 'Gestion des utilisateurs',
    autoScroll: true,
    selType: 'checkboxmodel',
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
                header: 'Profil',
                dataIndex: 'profile',
                flex: 1
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
                    text: 'Associer à un profil',
                    action: 'showProfileSelect'
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
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        }
    ]
});