Ext.define('Ufolep13Volley.view.profile.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.profilesgrid',
    title: 'Gestion des profils',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'Profiles',
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
                {
                    text: 'Créer',
                    action: 'addProfile'
                },
                {
                    text: 'Editer',
                    action: 'editProfile'
                }
            ]
        }
    ]
});