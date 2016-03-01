Ext.define('Ufolep13Volley.view.activity.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.activitygrid',
    title: 'Activité',
    autoScroll: true,
    store: 'Activity',
    columns: {
        items: [
            {
                xtype: 'datecolumn',
                header: 'Date',
                dataIndex: 'date',
                format: 'd/m/Y H:i:s',
                width: 150
            },
            {
                header: 'Equipe',
                dataIndex: 'nom_equipe',
                width: 180
            },
            {
                header: 'Competition',
                dataIndex: 'competition',
                width: 170
            },
            {
                header: 'Description',
                dataIndex: 'description',
                width: 550
            },
            {
                header: 'Utilisateur',
                dataIndex: 'utilisateur',
                width: 140
            },
            {
                header: 'Email',
                dataIndex: 'email_utilisateur',
                width: 200
            }
        ]
    }
});