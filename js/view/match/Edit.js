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
                store: 'ParentCompetitions',
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
                store: 'Competitions',
                queryMode: 'local',
                allowBlank: false,
                bind: {
                    visible: '{parent_competition.value}',
                    filters: {
                        property: 'id_compet_maitre',
                        value: '{parent_competition.value}',
                        exactMatch: true
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
                bind: {
                    visible: '{competition.value}'
                }
            },
            {
                xtype: 'combo',
                fieldLabel: 'Domicile',
                name: 'id_equipe_dom',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'RankTeams',
                queryMode: 'local',
                allowBlank: false,
                bind: {
                    visible: '{division.value}',
                    filters: [
                        {
                            property: 'code_competition',
                            value: '{competition.value}',
                            exactMatch: true
                        },
                        {
                            property: 'division',
                            value: '{division.value}',
                            exactMatch: true
                        }
                    ]
                },
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: 'Extérieur',
                name: 'id_equipe_ext',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'RankTeams',
                queryMode: 'local',
                allowBlank: false,
                bind: {
                    visible: '{division.value}',
                    filters: [
                        {
                            property: 'code_competition',
                            value: '{competition.value}',
                            exactMatch: true
                        },
                        {
                            property: 'division',
                            value: '{division.value}',
                            exactMatch: true
                        }
                    ]
                },
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: 'Journée',
                name: 'id_journee',
                displayField: 'display_combo',
                valueField: 'id',
                store: 'AdminDays',
                queryMode: 'local',
                allowBlank: false,
                bind: {
                    visible: '{competition.value}',
                    filters: {
                        property: 'code_competition',
                        value: '{competition.value}',
                        exactMatch: true
                    }
                },
                forceSelection: true
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
                name: 'sheet_received',
                xtype: 'checkboxfield',
                fieldLabel: 'Feuille de match reçue ?',
                boxLabel: 'Oui',
                msgTarget: 'under',
                uncheckedValue: 'off'
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