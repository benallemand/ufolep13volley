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
                header: 'Compétition',
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
                    text: 'Créer une équipe',
                    action: 'add'
                },
                {
                    text: 'Editer équipe',
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