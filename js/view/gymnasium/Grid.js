Ext.define('Ufolep13Volley.view.gymnasium.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gymnasiumsgrid',
    title: 'Gestion des Gymnases',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'Gymnasiums',
    columns: {
        items: [
            {
                header: 'Nom',
                dataIndex: 'nom',
                width: 300
            },
            {
                header: 'Adresse',
                dataIndex: 'adresse',
                width: 300
            },
            {
                header: 'Ville',
                dataIndex: 'ville',
                width: 300
            },
            {
                header: 'Terrains',
                dataIndex: 'nb_terrain',
                width: 300
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
                    text: 'Créer un gymnase',
                    action: 'add'
                },
                {
                    text: 'Editer gymnase',
                    action: 'edit'
                },
                {
                    text: 'Supprimer',
                    action: 'delete'
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