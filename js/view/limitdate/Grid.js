Ext.define('Ufolep13Volley.view.limitdate.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.limitdatesgrid',
    title: 'Gestion des Dates Limites',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'LimitDates',
    columns: {
        items: [
            {
                header: 'Comp�tition',
                dataIndex: 'libelle_competition'
            },
            {
                header: 'Date Limite',
                dataIndex: 'date_limite'
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
                    text: 'Cr�er une date limite',
                    action: 'add'
                },
                {
                    text: 'Editer date limite',
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