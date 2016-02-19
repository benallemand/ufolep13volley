Ext.define('Ufolep13Volley.view.day.AdminGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.daysgrid',
    title: 'Gestion des Journ�es',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'AdminDays',
    columns: {
        items: [
            {
                header: 'Comp�tition',
                dataIndex: 'libelle_competition'
            },
            {
                header: 'Num�ro',
                dataIndex: 'numero'
            },
            {
                header: 'Nommage',
                dataIndex: 'nommage'
            },
            {
                header: 'Libell�',
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
                    text: 'Cr�er une journ�e',
                    action: 'add'
                },
                {
                    text: 'Editer journ�e',
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