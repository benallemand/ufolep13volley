Ext.define('Ufolep13Volley.view.match.AdminGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.matchesgrid',
    title: 'Gestion des Matches',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: {type: 'AdminMatches'},
    columns: {
        items: [
            {
                header: 'Statut',
                xtype: 'actioncolumn',
                items: [
                    {
                        getTip: function (value, meta, record) {
                            return record.get('match_status') === 'ARCHIVED' ? 'Match archivé' : '';
                        },
                        getClass: function (value, meta, record) {
                            return record.get('match_status') === 'ARCHIVED' ? 'fa fa-box-archive' : 'x-hidden-display';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            return record.get('match_status') === 'CONFIRMED' ? 'Match confirmé' : '';
                        },
                        getClass: function (value, meta, record) {
                            return record.get('match_status') === 'CONFIRMED' ? 'fa fa-circle green' : 'x-hidden-display';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            return record.get('match_status') === 'NOT_CONFIRMED' ? 'Match non confirmé' : '';
                        },
                        getClass: function (value, meta, record) {
                            return record.get('match_status') === 'NOT_CONFIRMED' ? 'fa fa-circle red' : 'x-hidden-display';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            return record.get('certif') ? 'Match validé' : '';
                        },
                        getClass: function (value, meta, record) {
                            return record.get('certif') ? 'fa fa-check green' : 'x-hidden-display';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            return record.get('is_match_player_filled') ? 'Présents renseignés' : '';
                        },
                        getClass: function (value, meta, record) {
                            return record.get('is_match_player_filled') ? 'fa fa-list green' : 'x-hidden-display';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                            grid.up('matchesgrid').down('toolbar > button[action=manage_match_players]').click();
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            return record.get('is_forfait') ? 'Equipe forfait' : '';
                        },
                        getClass: function (value, meta, record) {
                            return record.get('is_forfait') ? 'fa fa-f green' : 'x-hidden-display';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            return record.get('is_match_player_requested') ? 'Présents non renseignés !' : '';
                        },
                        getClass: function (value, meta, record) {
                            return record.get('is_match_player_requested') ? 'fa fa-exclamation-triangle orange' : 'x-hidden-display';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                            grid.getSelectionModel().select(record);
                            grid.up('matchesgrid').down('toolbar > button[action=manage_match_players]').click();
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            return record.get('has_forbidden_player') ? 'Au moins 1 joueur n\'était pas valide !' : '';
                        },
                        getClass: function (value, meta, record) {
                            return record.get('has_forbidden_player') ? 'fa fa-exclamation-circle red' : 'x-hidden-display';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                            grid.getSelectionModel().select(record);
                            grid.up('matchesgrid').down('toolbar > button[action=manage_match_players]').click();
                        },
                    },
                    {
                        getTip: function () {
                            return "Envoyer un mail aux responsables";
                        },
                        getClass: function (value, meta, record) {
                            return 'fa fa-envelope';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                            window.location.href = Ext.String.format("mailto:{0},{1}", record.get('email_dom'), record.get('email_ext'));
                        },
                    },
                ],
                width: 100,
            },
            {
                header: 'Code',
                dataIndex: 'code_match',
                width: 100,
                renderer: function (value, meta, record) {
                    return Ext.String.format("<a href='/match.php?id_match={0}' target='_blank'>{1}</a>",
                        record.get('id_match'),
                        value);
                }
            },
            {
                header: 'Fichiers',
                dataIndex: 'files_paths',
                width: 100,
                renderer: function (value) {
                    if (Ext.isEmpty(value)) {
                        return value;
                    }
                    value = value.split('|');
                    var result_string = '';
                    Ext.each(value, function (file_path) {
                        result_string = result_string +
                            Ext.String.format("<a href='/rest/action.php/files/download_match_file?file_path={0}' target='_blank'>{1}</a><br/>",
                                file_path,
                                file_path.replace(/^.*[\\\/]/, '').replace(/\.[^/.]+$/, ''));
                    });
                    return result_string;
                }
            },
            {
                header: 'Comp',
                dataIndex: 'code_competition',
                width: 50,
            },
            {
                header: 'Div',
                dataIndex: 'division',
                width: 50,
            },
            {
                header: 'Journée',
                dataIndex: 'numero_journee',
                width: 50,
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
                width: 120,
            },
            {
                header: 'Date originale',
                hidden: true,
                xtype: 'datecolumn',
                format: 'D d/m/Y',
                dataIndex: 'date_original',
                width: 120,
            },
            {
                header: 'Gymnase',
                hidden: true,
                dataIndex: 'gymnasium',
                width: 120,
            },
            {
                header: 'Heure',
                dataIndex: 'heure_reception',
                width: 80,
            },
            {
                header: 'Commentaires',
                dataIndex: 'note',
                flex: 1
            },
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
                },
                {
                    text: 'Certifier',
                    hidden: true,
                    action: 'certify_matchs'
                },
                {
                    text: 'Flip',
                    hidden: true,
                    action: 'flip_matchs'
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
                    xtype: 'segmentedbutton',
                    items: [
                        {
                            text: 'Prêt à valider',
                            iconCls: 'fa fa-check green',
                            handler: function (button) {
                                var store = button.up('grid').getStore();
                                store.clearFilter();
                                store.filter('is_validation_ready', true);
                                button.up('toolbar').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                            }
                        },
                        {
                            text: 'Présents à renseigner',
                            iconCls: 'fa fa-exclamation-triangle orange',
                            handler: function (button) {
                                var store = button.up('grid').getStore();
                                store.clearFilter();
                                store.filter({
                                    property: 'match_status',
                                    operator: 'in',
                                    value: ['CONFIRMED', 'NOT_CONFIRMED'],
                                });
                                store.filter('is_match_player_requested', true);
                                button.up('toolbar').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                            }
                        },
                        {
                            text: 'Joueurs non valides',
                            iconCls: 'fa fa-exclamation-circle red',
                            handler: function (button) {
                                var store = button.up('grid').getStore();
                                store.clearFilter();
                                store.filter({
                                    property: 'match_status',
                                    operator: 'in',
                                    value: ['CONFIRMED', 'NOT_CONFIRMED'],
                                });
                                store.filter('has_forbidden_player', true);
                                button.up('toolbar').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                            }
                        },
                        {
                            text: 'Non certifiés',
                            iconCls: 'fa fa-check red',
                            handler: function (button) {
                                var store = button.up('grid').getStore();
                                store.clearFilter();
                                store.filter({
                                    property: 'match_status',
                                    operator: 'in',
                                    value: ['CONFIRMED', 'NOT_CONFIRMED'],
                                });
                                store.filter('certif', false);
                                button.up('toolbar').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                            }
                        },
                        {
                            text: 'Tous (saison en cours)',
                            iconCls: 'fa fa-inbox',
                            handler: function (button) {
                                var store = button.up('grid').getStore();
                                store.clearFilter();
                                store.filter({
                                    property: 'match_status',
                                    operator: 'in',
                                    value: ['CONFIRMED', 'NOT_CONFIRMED'],
                                });
                                button.up('toolbar').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                            }
                        },
                        {
                            text: 'Archivés',
                            iconCls: 'fa fa-box-archive',
                            handler: function (button) {
                                var store = button.up('grid').getStore();
                                store.clearFilter();
                                store.filter({
                                    property: 'match_status',
                                    operator: 'in',
                                    value: ['ARCHIVED'],
                                });
                                button.up('toolbar').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                            }
                        }
                    ]

                }
            ]
        }
    ]
});