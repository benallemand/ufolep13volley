Ext.define('Ufolep13Volley.view.match.AdminGrid', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.matchesgrid',
    title: 'Gestion des Matches',
    store: {type: 'AdminMatches'},
    columns: {
        items: [
            {
                header: 'Liens',
                xtype: 'actioncolumn',
                items: [
                    {
                        getTip: function () {
                            return "Feuille de match";
                        },
                        getClass: function (value, meta, record) {
                            return (
                                record.get('match_status') !== 'ARCHIVED'
                                && record.get('is_sign_match_dom')
                                && record.get('is_sign_match_ext')) ? 'fa fa-volleyball green' : 'fa fa-volleyball red';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                            window.open(`${location.origin}/match.php?id_match=${record.get('id_match')}`, '_blank');
                        },
                    },
                    {
                        getTip: function () {
                            return "Fiche équipes";
                        },
                        getClass: function (value, meta, record) {
                            return (
                                record.get('match_status') !== 'ARCHIVED'
                                && record.get('is_match_player_filled')
                                && record.get('is_sign_team_dom')
                                && record.get('is_sign_team_ext')) ? 'fa fa-user green' : 'fa fa-user red';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                            window.open(`${location.origin}/team_sheets.php?id_match=${record.get('id_match')}`, '_blank');
                        },
                    },
                    {
                        getTip: function () {
                            return "Sondages";
                        },
                        getClass: function (value, meta, record) {
                            return (
                                record.get('match_status') !== 'ARCHIVED'
                                && record.get('is_survey_filled_dom')
                                && record.get('is_survey_filled_ext')) ? 'fa fa-square-poll-vertical green' : 'fa fa-square-poll-vertical red';
                        },
                        handler: function (grid, rowIndex, colIndex, item, e, record) {
                            window.open(`${location.origin}/survey.php?id_match=${record.get('id_match')}`, '_blank');
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
                ]
            },
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
                    },
                    {
                        getTip: function (value, meta, record) {
                            return record.get('has_forbidden_player') ? 'Au moins 1 joueur n\'était pas valide !' : '';
                        },
                        getClass: function (value, meta, record) {
                            return record.get('has_forbidden_player') ? 'fa fa-exclamation-circle red' : 'x-hidden-display';
                        },
                    },
                    {
                        getTip: function (value, meta, record) {
                            return !Ext.isEmpty(record.get('count_status')) ? record.get('count_status') : '';
                        },
                        getClass: function (value, meta, record) {
                            return !Ext.isEmpty(record.get('count_status')) ? 'fa fa-exclamation-circle red' : 'x-hidden-display';
                        },
                    },
                ],
                width: 100,
            },
            {
                header: 'Code',
                dataIndex: 'code_match',
                width: 100,
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
                renderer: function (value, meta, record) {
                    return Ext.String.format("<a href='/pages/home.html#/divisions/{0}/{1}' target='_blank'>{2}</a>",
                        record.get('code_competition'),
                        value,
                        value,
                    );
                },
            },
            {
                header: 'Journée',
                dataIndex: 'numero_journee',
                width: 50,
            },
            {
                header: 'Domicile',
                dataIndex: 'equipe_dom',
                flex: 1,
                renderer: function (value, meta, record) {
                    return Ext.String.format("<a href='/new_site/#/phonebook/{0}' target='_blank'>{1}</a>",
                        record.get('id_equipe_dom'),
                        value,
                    );
                },
            },
            {
                header: 'Résultat',
                dataIndex: 'resultat',
                width: 200,
                renderer: function (value, meta, record) {
                    return Ext.String.format("<a href='/match.php?id_match={0}' target='_blank'>{1}</a>",
                        record.get('id_match'),
                        value,
                    );
                },
            },
            {
                header: 'Extérieur',
                dataIndex: 'equipe_ext',
                flex: 1,
                renderer: function (value, meta, record) {
                    return Ext.String.format("<a href='/new_site/#/phonebook/{0}' target='_blank'>{1}</a>",
                        record.get('id_equipe_ext'),
                        value,
                    );
                },
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
                width: 160,
                dataIndex: 'note',
            },
            {
                text: 'Signatures',
                columns: [
                    {
                        text: 'Feuilles',
                        columns: [
                            {
                                text: 'D',
                                width: 50,
                                dataIndex: 'is_sign_team_dom',
                                xtype: 'checkcolumn',
                                listeners: {
                                    beforecheckchange: function () {
                                        return false;
                                    }
                                }
                            },
                            {
                                text: 'E',
                                width: 50,
                                dataIndex: 'is_sign_team_ext',
                                xtype: 'checkcolumn',
                                listeners: {
                                    beforecheckchange: function () {
                                        return false;
                                    }
                                }
                            },
                        ]
                    },
                    {
                        text: 'Match',
                        columns: [
                            {
                                text: 'D',
                                width: 50,
                                dataIndex: 'is_sign_match_dom',
                                xtype: 'checkcolumn',
                                listeners: {
                                    beforecheckchange: function () {
                                        return false;
                                    }
                                }
                            },
                            {
                                text: 'E',
                                width: 50,
                                dataIndex: 'is_sign_match_ext',
                                xtype: 'checkcolumn',
                                listeners: {
                                    beforecheckchange: function () {
                                        return false;
                                    }
                                }
                            },
                        ]
                    },
                    {
                        text: 'Sondage',
                        columns: [
                            {
                                text: 'D',
                                width: 50,
                                dataIndex: 'is_survey_filled_dom',
                                xtype: 'checkcolumn',
                                listeners: {
                                    beforecheckchange: function () {
                                        return false;
                                    }
                                }
                            },
                            {
                                text: 'E',
                                width: 50,
                                dataIndex: 'is_survey_filled_ext',
                                xtype: 'checkcolumn',
                                listeners: {
                                    beforecheckchange: function () {
                                        return false;
                                    }
                                }
                            },
                        ]
                    },
                ]

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
                    xtype: 'segmentedbutton',
                    items: [
                        {
                            text: 'Prêt à valider',
                            iconCls: 'fa fa-check green',
                            handler: function (button) {
                                var store = button.up('grid').getStore();
                                store.clearFilter();
                                store.filter('is_validation_ready', true);
                                button.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
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
                                button.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
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
                                button.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
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
                                button.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
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
                                button.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
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
                                button.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
                            }
                        }
                    ]

                }
            ]
        }
    ]
});