Ext.define('Ufolep13Volley.view.club.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.clubsgrid',
    title: 'Gestion des Clubs',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'Clubs',
    columns: {
        items: [
            {
                header: 'Nom',
                dataIndex: 'nom',
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
                    text: 'Créer un club',
                    action: 'add'
                },
                {
                    text: 'Editer club',
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