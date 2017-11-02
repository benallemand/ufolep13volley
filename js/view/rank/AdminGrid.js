Ext.define('Ufolep13Volley.view.rank.AdminGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.rankgrid',
    title: 'Gestion des Classements',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'AdminRanks',
    columns: {
        items: [
            {
                header: 'Equipe',
                dataIndex: 'nom_equipe'
            },
            {
                header: 'Compétition',
                dataIndex: 'nom_competition'
            },
            {
                header: 'Division',
                dataIndex: 'division'
            },
            {
                header: 'Classement au début',
                dataIndex: 'rank_start'
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
                    text: 'Créer',
                    action: 'add'
                },
                {
                    text: 'Editer',
                    action: 'edit'
                },
                {
                    text: 'Supprimer',
                    action: 'delete'
                },
                {
                    text: 'Remise à zéro des points',
                    action: 'razPoints'
                }
            ]
        },
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        }
    ]
});