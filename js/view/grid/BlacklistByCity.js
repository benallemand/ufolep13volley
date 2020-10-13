Ext.define('Ufolep13Volley.view.grid.BlacklistByCity', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.blacklist_by_city_grid',
    title: 'Dates interdites par ville (spécial COVID)',
    autoScroll: true,
    store: 'BlacklistByCity',
    selType: 'checkboxmodel',
    columns: {
        items: [
            {
                header: 'Ville',
                dataIndex: 'city',
                flex: 1
            },
            {
                header: 'Du',
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'from_date',
                flex: 1
            },
            {
                header: 'Au',
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'to_date',
                flex: 1
            }
        ]
    },
    tbar: [
        'ACTIONS',
        {
            xtype: 'tbseparator'
        },
        {
            text: 'Ajouter',
            action: 'add'
        },
        {
            text: 'Modifier',
            action: 'edit'
        },
        {
            text: 'Supprimer',
            action: 'delete'
        }
    ]
});