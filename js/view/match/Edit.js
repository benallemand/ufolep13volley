Ext.define('Ufolep13Volley.view.match.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.matchedit',
    title: "Modification du match",
    height: 500,
    width: 700,
    modal: true,
    layout: 'fit',
    autoShow: true,
    items: {
        xtype: 'form',
        trackResetOnLoad: true,
        layout: 'anchor',
        defaults: {
            anchor: '90%',
            margins: 10
        },
        url: '/rest/action.php/matchmgr/saveMatch',
        viewModel: true,
        autoScroll: true,
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id_match',
                name: 'id_match'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Code',
                name: 'code_match',
                allowBlank: false
            },
            {
                xtype: 'combo',
                fieldLabel: 'Competition parente',
                reference: 'parent_competition',
                publishes: 'value',
                name: 'parent_code_competition',
                displayField: 'libelle',
                valueField: 'code_competition',
                store: {
                    type: 'ParentCompetitions'
                },
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: 'Competition',
                reference: 'competition',
                publishes: 'value',
                name: 'code_competition',
                displayField: 'libelle',
                valueField: 'code_competition',
                store: {
                    type: 'Competitions'
                },
                queryMode: 'local',
                allowBlank: false,
                listeners: {
                    focus: function (combo) {
                        var parent_code_competition = combo.up('form').down('combo[name=parent_code_competition]').getValue();
                        combo.getStore().filter([{
                            property: 'id_compet_maitre',
                            value: parent_code_competition,
                            exactMatch: true
                        }]);
                    }
                },
                forceSelection: true
            },
            {
                xtype: 'textfield',
                reference: 'division',
                publishes: 'value',
                fieldLabel: 'Division',
                name: 'division',
                allowBlank: false,
            },
            {
                xtype: 'combo',
                fieldLabel: 'Domicile',
                name: 'id_equipe_dom',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: {type: 'RankTeams'},
                queryMode: 'local',
                allowBlank: false,
                listeners: {
                    focus: function (combo) {
                        var code_competition = combo.up('form').down('combo[name=code_competition]').getValue();
                        var division = combo.up('form').down('textfield[name=division]').getValue();
                        combo.getStore().filter([{
                            property: 'code_competition',
                            value: code_competition,
                            exactMatch: true
                        },
                            {
                                property: 'division',
                                value: division,
                                exactMatch: true
                            }]);
                    }
                },
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: 'Extérieur',
                name: 'id_equipe_ext',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: {type: 'RankTeams'},
                queryMode: 'local',
                allowBlank: false,
                listeners: {
                    focus: function (combo) {
                        var code_competition = combo.up('form').down('combo[name=code_competition]').getValue();
                        var division = combo.up('form').down('textfield[name=division]').getValue();
                        combo.getStore().filter([{
                            property: 'code_competition',
                            value: code_competition,
                            exactMatch: true
                        },
                            {
                                property: 'division',
                                value: division,
                                exactMatch: true
                            }]);
                    }
                },
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: 'Gymnase',
                name: 'id_gymnasium',
                displayField: 'full_name',
                valueField: 'id',
                store: {type: 'Gymnasiums'},
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: 'Journée',
                name: 'id_journee',
                displayField: 'display_combo',
                valueField: 'id',
                store: {
                    type: 'AdminDays'
                },
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true,
                listeners: {
                    focus: function (combo) {
                        var code_competition = combo.up('form').down('combo[name=code_competition]').getValue();
                        combo.getStore().filter({
                            property: 'code_competition',
                            value: code_competition,
                            exactMatch: true
                        });
                    }
                }
            },
            {
                xtype: 'datefield',
                fieldLabel: 'Date',
                name: 'date_reception',
                allowBlank: false,
                format: 'd/m/Y',
                startDay: 1
            },
            {
                name: 'certif',
                xtype: 'checkboxfield',
                fieldLabel: 'Certifié ?',
                boxLabel: 'Oui',
                msgTarget: 'under',
                uncheckedValue: 'off'
            },
            {
                name: 'is_sign_team_dom',
                xtype: 'checkboxfield',
                fieldLabel: 'Fiches équipes signées (dom) ?',
                boxLabel: 'Oui',
                msgTarget: 'under',
                uncheckedValue: 'off'
            },
            {
                name: 'is_sign_team_ext',
                xtype: 'checkboxfield',
                fieldLabel: 'Fiches équipes signées (ext) ?',
                boxLabel: 'Oui',
                msgTarget: 'under',
                uncheckedValue: 'off'
            },
            {
                name: 'is_sign_match_dom',
                xtype: 'checkboxfield',
                fieldLabel: 'Feuille de match signée (dom) ?',
                boxLabel: 'Oui',
                msgTarget: 'under',
                uncheckedValue: 'off'
            },
            {
                name: 'is_sign_match_ext',
                xtype: 'checkboxfield',
                fieldLabel: 'Feuille de match signée (ext) ?',
                boxLabel: 'Oui',
                msgTarget: 'under',
                uncheckedValue: 'off'
            },
            {
                name: 'referee',
                xtype: 'displayfield',
                fieldLabel: 'Arbitrage'
            },
            {
                name: 'note',
                xtype: 'textarea',
                fieldLabel: 'Commentaire'
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                action: 'cancel',
            },
            {
                text: 'Sauver',
                formBind: true,
                disabled: true,
                action: 'save'
            }
        ]
    }
});