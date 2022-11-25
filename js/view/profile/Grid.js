Ext.define('Ufolep13Volley.view.profile.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.profilesgrid',
    title: 'Gestion des profils',
    autoScroll: true,
    selType: 'checkboxmodel',
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