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
                dataIndex: 'nom'
            },
            {
                header: 'Adresse',
                dataIndex: 'adresse'
            },
            {
                header: 'Ville',
                dataIndex: 'ville'
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
        }
    ]
});