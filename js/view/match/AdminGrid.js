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
                header: 'Statut',
                xtype: 'actioncolumn',
                items: [
                    {
                        getTip: function (value, meta, record) {
                            if (!record.get('certif')) {
                                return '';
                            }
                            return "Match validé";

                        },
                        getClass: function (value, meta, record) {
                            if (!record.get('certif')) {
                                return 'x-hidden-display';
                            }
                            return 'fa fa-check green';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            if (!record.get('is_match_player_filled')) {
                                return '';
                            }
                            return "Présents renseignés";

                        },
                        getClass: function (value, meta, record) {
                            if (!record.get('is_match_player_filled')) {
                                return 'x-hidden-display';
                            }
                            return 'fa fa-list green';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            if (!record.get('is_match_player_requested')) {
                                return '';
                            }
                            return "Présents non renseignés !";

                        },
                        getClass: function (value, meta, record) {
                            if (!record.get('is_match_player_requested')) {
                                return 'x-hidden-display';
                            }
                            return 'fa fa-exclamation-triangle orange';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                            grid.getSelectionModel().select(record);
                            grid.up('matchesgrid').down('toolbar > button[action=manage_match_players]').click();
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            if (!record.get('has_forbidden_player')) {
                                return '';
                            }
                            return "Au moins 1 joueur n'était pas valide !";

                        },
                        getClass: function (value, meta, record) {
                            if (!record.get('has_forbidden_player')) {
                                return 'x-hidden-display';
                            }
                            return 'fa fa-exclamation-circle red';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                            grid.getSelectionModel().select(record);
                            grid.up('matchesgrid').down('toolbar > button[action=manage_match_players]').click();
                        },
                    }
                ],
                flex: 1
            },
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
                },
                {
                    text: 'Gérer les présents',
                    hidden: true,
                    action: 'manage_match_players'
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
                },
                {
                    text: 'Présents à renseigner',
                    enableToggle: true,
                    handler: function (button) {
                        var store = button.up('grid').getStore();
                        if (button.pressed) {
                            store.filter('is_match_player_requested', true);
                        } else {
                            store.clearFilter();
                        }
                        button.up('toolbar').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                    }
                }
            ]
        }
    ]
});