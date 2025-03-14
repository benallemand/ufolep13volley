Ext.define('Ufolep13Volley.view.gymnasium.Grid', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.gymnasiumsgrid',
    title: 'Gestion des Gymnases',
    store: {type: 'Gymnasiums'},
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
    ]
});