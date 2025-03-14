Ext.define('Ufolep13Volley.view.limitdate.Grid', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.limitdatesgrid',
    title: 'Gestion des Dates Limites',
    store: {type: 'LimitDates'},
    columns: {
        items: [
            {
                header: 'Compétition',
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
                    text: 'Créer une date limite',
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
        },
    ]
});