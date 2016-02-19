Ext.define('Ufolep13Volley.view.day.AdminGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.daysgrid',
    title: 'Gestion des Journées',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'AdminDays',
    columns: {
        items: [
            {
                header: 'Compétition',
                dataIndex: 'libelle_competition'
            },
            {
                header: 'Numéro',
                dataIndex: 'numero'
            },
            {
                header: 'Nommage',
                dataIndex: 'nommage'
            },
            {
                header: 'Libellé',
                dataIndex: 'libelle'
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
                    text: 'Créer une journée',
                    action: 'add'
                },
                {
                    text: 'Editer journée',
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