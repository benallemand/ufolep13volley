Ext.define('Ufolep13Volley.view.team.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.teamsgrid',
    title: 'Gestion des Equipe',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'Teams',
    columns: {
        items: [
            {
                header: 'Equipe',
                dataIndex: 'nom_equipe',
                width: 300
            },
            {
                header: 'Club',
                dataIndex: 'club',
                width: 300
            },
            {
                header: 'Comp�tition',
                dataIndex: 'libelle_competition',
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
                    text: 'Cr�er une �quipe',
                    action: 'add'
                },
                {
                    text: 'Editer �quipe',
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