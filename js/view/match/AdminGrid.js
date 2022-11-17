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
                            if (record.get('match_status') === 'ARCHIVED') {
                                return '';
                            }
                            if (!record.get('certif')) {
                                return '';
                            }
                            return "Match validé";

                        },
                        getClass: function (value, meta, record) {
                            if (record.get('match_status') === 'ARCHIVED') {
                                return 'x-hidden-display';
                            }
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
                            if (record.get('match_status') === 'ARCHIVED') {
                                return '';
                            }
                            if (!record.get('is_match_player_filled')) {
                                return '';
                            }
                            return "Présents renseignés";

                        },
                        getClass: function (value, meta, record) {
                            if (record.get('match_status') === 'ARCHIVED') {
                                return 'x-hidden-display';
                            }
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
                            if (record.get('match_status') === 'ARCHIVED') {
                                return '';
                            }
                            if (!record.get('is_forfait')) {
                                return '';
                            }
                            return "Equipe forfait";

                        },
                        getClass: function (value, meta, record) {
                            if (record.get('match_status') === 'ARCHIVED') {
                                return 'x-hidden-display';
                            }
                            if (!record.get('is_forfait')) {
                                return 'x-hidden-display';
                            }
                            return 'fa fa-f green';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            if (record.get('match_status') === 'ARCHIVED') {
                                return '';
                            }
                            if (!record.get('is_match_player_requested')) {
                                return '';
                            }
                            return "Présents non renseignés !";

                        },
                        getClass: function (value, meta, record) {
                            if (record.get('match_status') === 'ARCHIVED') {
                                return 'x-hidden-display';
                            }
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
                            if (record.get('match_status') === 'ARCHIVED') {
                                return '';
                            }
                            if (!record.get('has_forbidden_player')) {
                                return '';
                            }
                            return "Au moins 1 joueur n'était pas valide !";

                        },
                        getClass: function (value, meta, record) {
                            if (record.get('match_status') === 'ARCHIVED') {
                                return 'x-hidden-display';
                            }
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
                flex: 1,
                renderer: function (value, meta, record) {
                    if (!record.get('sheet_received')) {
                        return value;
                    }
                    return Ext.String.format("<a href='/rest/action.php/matchmgr/download?id={0}' target='_blank'>{1}</a>",
                        record.get('id_match'),
                        value);
                }
            },
            {
                header: 'Fichiers',
                dataIndex: 'files_paths',
                flex: 1,
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
                header: 'Gymnase',
                dataIndex: 'gymnasium',
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
                },
                {
                    text: 'Certifier',
                    hidden: true,
                    action: 'certify_matchs'
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