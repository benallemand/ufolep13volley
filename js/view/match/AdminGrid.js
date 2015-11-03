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
                header: 'Comp�tition',
                dataIndex: 'libelle_competition'
            },
            {
                header: 'Division',
                dataIndex: 'division'
            },
            {
                header: 'Journ�e',
                dataIndex: 'journee'
            },
            {
                header: 'Domicile',
                dataIndex: 'equipe_dom'
            },
            {
                header: 'Ext�rieur',
                dataIndex: 'equipe_ext'
            },
            {
                header: 'Date',
                dataIndex: 'date_reception'
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
                    text: 'Cr�er un match',
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
        }
    ]
});