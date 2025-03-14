Ext.define('Ufolep13Volley.view.club.Grid', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.clubsgrid',
    title: 'Gestion des Clubs',
    store: {type: 'Clubs'},
    columns: {
        items: [
            {
                header: 'Nom',
                dataIndex: 'nom',
                width: 300
            },
            {
                header: "Numéro d'affiliation",
                dataIndex: 'affiliation_number',
                width: 300
            },
            {
                dataIndex: 'nom_responsable',
                header: "Nom",
                flex: 1
            },
            {
                dataIndex: 'prenom_responsable',
                header: "Prénom",
                flex: 1
            },
            {
                dataIndex: 'tel1_responsable',
                header: "Tel 1",
                flex: 1
            },
            {
                dataIndex: 'tel2_responsable',
                header: "Tel 2",
                flex: 1
            },
            {
                dataIndex: 'email_responsable',
                header: "Email",
                flex: 1
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
        },
    ]
});