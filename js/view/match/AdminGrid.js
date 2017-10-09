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
                dataIndex: 'code_match'
            },
            {
                header: 'Compétition',
                dataIndex: 'libelle_competition'
            },
            {
                header: 'Division',
                dataIndex: 'division'
            },
            {
                header: 'Journée',
                dataIndex: 'journee'
            },
            {
                header: 'Domicile',
                dataIndex: 'equipe_dom'
            },
            {
                header: 'Extérieur',
                dataIndex: 'equipe_ext'
            },
            {
                header: 'Date',
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'date_reception'
            },
            {
                header: 'Date originale',
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'date_original'
            },
            {
                header: 'Heure',
                dataIndex: 'heure_reception'
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