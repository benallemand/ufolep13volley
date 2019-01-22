Ext.define('Ufolep13Volley.view.match.AdminGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.matchesgrid',
    title: 'Gestion des Matches',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'AdminMatches',
    columns: {
        items: [
            {
                header: 'Code',
                dataIndex: 'code_match',
                flex: 1
            },
            {
                header: 'Compétition',
                dataIndex: 'libelle_competition',
                flex: 1
            },
            {
                header: 'Division',
                dataIndex: 'division',
                flex: 1
            },
            {
                header: 'Journée',
                dataIndex: 'journee',
                flex: 1
            },
            {
                header: 'Domicile',
                dataIndex: 'equipe_dom',
                flex: 1
            },
            {
                header: 'Extérieur',
                dataIndex: 'equipe_ext',
                flex: 1
            },
            {
                header: 'Date',
                xtype: 'datecolumn',
                format: 'D d/m/Y',
                dataIndex: 'date_reception',
                flex: 1
            },
            {
                header: 'Date originale',
                xtype: 'datecolumn',
                format: 'D d/m/Y',
                dataIndex: 'date_original',
                flex: 1
            },
            {
                header: 'Heure',
                dataIndex: 'heure_reception',
                flex: 1
            },
            {
                header: 'Statut',
                dataIndex: 'match_status',
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
                    text: 'Créer un match',
                    action: 'add'
                },
                {
                    text: 'Editer match',
                    action: 'edit'
                },
                {
                    text: 'Supprimer',
                    action: 'delete'
                },
                {
                    text: 'Archiver',
                    action: 'archiveMatch'
                },
                {
                    text: 'Confirmer',
                    action: 'confirmMatch'
                },
                {
                    text: 'Dé-confirmer',
                    action: 'unconfirmMatch'
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