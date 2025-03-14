Ext.define('Ufolep13Volley.view.profile.Grid', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.profilesgrid',
    title: 'Gestion des profils',
    store: {type: 'Profiles'},
    columns: {
        items: [
            {
                header: 'Nom',
                dataIndex: 'name',
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
                    text: 'Créer',
                    action: 'addProfile'
                },
                {
                    text: 'Editer',
                    action: 'editProfile'
                }
            ]
        },
    ]
});