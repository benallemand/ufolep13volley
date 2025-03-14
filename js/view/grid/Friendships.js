Ext.define('Ufolep13Volley.view.grid.Friendships', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.friendships_grid',
    title: 'Ententes entre clubs',
    store: {type: 'Friendships'},
    columns: {
        items: [
            {
                header: 'Club 1',
                dataIndex: 'nom_club_1',
                flex: 1
            },
            {
                header: 'Club 2',
                dataIndex: 'nom_club_2',
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