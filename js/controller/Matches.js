Ext.define('Ufolep13Volley.controller.Matches', {
    extend: 'Ext.app.Controller',
    stores: ['Matches'],
    models: ['Match'],
    views: [],
    refs: [],
    init: function() {
        this.control(
                {
                    'grid[title=Matches]': {
                        itemcertifybuttonclick: this.certifyMatch,
                        itemeditbuttonclick: this.editMatch,
                        itemdeletebuttonclick: this.deleteMatch
                    }
                });
    },
    certifyMatch: function(grid, rowIndex) {
        var me = this;
        var rec = grid.getStore().getAt(rowIndex);
        Ext.Msg.show({
            title: 'Certification',
            msg: 'Certifier le match ' + rec.get('code_match') + ' ?',
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function(btn) {
                if (btn === 'ok') {
                    Ext.Ajax.request({
                        url: 'ajax/certifierMatch.php',
                        params: {
                            code_match: rec.get('code_match')
                        },
                        success: function(response) {
                            var responseJson = Ext.decode(response.responseText);
                            Ext.Msg.alert('Info', responseJson.message);
                            me.getMatchesStore().load();
                        }
                    });
                }
            }
        });
    },
    editMatch: function(grid, rowIndex) {
        var me = this;
        var rec = grid.getStore().getAt(rowIndex);
        var afficheFormulaire = function() {
            Ext.create('Ext.window.Window', {
                title: 'Modifier un match',
                height: 600,
                width: 700,
                modal: true,
                layout: 'fit',
                items: {
                    xtype: 'form',
                    layout: 'anchor',
                    url: 'ajax/modifierMatch.php',
                    defaults: {
                        anchor: '100%',
                        margins: 10
                    },
                    items: [
                        {
                            xtype: 'textfield',
                            fieldLabel: 'Code Match',
                            name: 'code_match',
                            readOnly: true
                        },
                        {
                            xtype: 'hidden',
                            fieldLabel: 'Competition',
                            name: 'code_competition'
                        },
                        {
                            xtype: 'hidden',
                            fieldLabel: 'Division',
                            name: 'division'
                        },
                        {
                            xtype: 'hidden',
                            fieldLabel: 'Id Equipe Domicile',
                            name: 'id_equipe_dom'
                        },
                        {
                            xtype: 'hidden',
                            fieldLabel: 'Id Equipe Exterieur',
                            name: 'id_equipe_ext'
                        },
                        {
                            xtype: 'datefield',
                            hidden: true,
                            fieldLabel: 'Date',
                            name: 'date_originale',
                            format: 'd/m/Y',
                            value: rec.get('date_reception')
                        },
                        {
                            xtype: 'datefield',
                            fieldLabel: 'Date',
                            name: 'date_reception',
                            format: 'd/m/Y',
                            startDay: 1
                        },
                        {
                            xtype: 'container',
                            layout: 'hbox',
                            items: [
                                {
                                    xtype: 'container',
                                    flex: 1,
                                    layout: 'anchor',
                                    defaults: {
                                        anchor: '90%'
                                    },
                                    items: [
                                        {
                                            xtype: 'displayfield',
                                            fieldLabel: 'Equipe Domicile',
                                            name: 'equipe_dom'
                                        },
                                        {
                                            xtype: 'button',
                                            margin: 10,
                                            text: 'Equipe ' + rec.get('equipe_ext') + ' forfait (pensez à sauver)',
                                            handler: function() {
                                                this.up('form').getForm().setValues([
                                                    {
                                                        id: 'score_equipe_dom',
                                                        value: 3
                                                    },
                                                    {
                                                        id: 'score_equipe_ext',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_1_dom',
                                                        value: 25
                                                    },
                                                    {
                                                        id: 'set_1_ext',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_2_dom',
                                                        value: 25
                                                    },
                                                    {
                                                        id: 'set_2_ext',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_3_dom',
                                                        value: 25
                                                    },
                                                    {
                                                        id: 'set_3_ext',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_4_dom',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_4_ext',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_5_dom',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_5_ext',
                                                        value: 0
                                                    }
                                                ]);
                                            }
                                        },
                                        {
                                            xtype: 'numberfield',
                                            fieldLabel: 'Sets Domicile',
                                            name: 'score_equipe_dom',
                                            minValue: 0,
                                            maxValue: 3
                                        },
                                        {
                                            xtype: 'numberfield',
                                            fieldLabel: 'Set 1',
                                            name: 'set_1_dom',
                                            minValue: 0
                                        },
                                        {
                                            xtype: 'numberfield',
                                            fieldLabel: 'Set 2',
                                            name: 'set_2_dom',
                                            minValue: 0
                                        },
                                        {
                                            xtype: 'numberfield',
                                            fieldLabel: 'Set 3',
                                            name: 'set_3_dom',
                                            minValue: 0
                                        },
                                        {
                                            xtype: 'numberfield',
                                            fieldLabel: 'Set 4',
                                            name: 'set_4_dom',
                                            minValue: 0
                                        },
                                        {
                                            xtype: 'numberfield',
                                            fieldLabel: 'Set 5',
                                            name: 'set_5_dom',
                                            minValue: 0
                                        }
                                    ]
                                },
                                {
                                    xtype: 'container',
                                    flex: 1,
                                    layout: 'anchor',
                                    defaults: {
                                        anchor: '90%'
                                    },
                                    items: [
                                        {
                                            xtype: 'displayfield',
                                            fieldLabel: 'Equipe Exterieur',
                                            name: 'equipe_ext'
                                        },
                                        {
                                            xtype: 'button',
                                            margin: 10,
                                            text: 'Equipe ' + rec.get('equipe_dom') + ' forfait (pensez à sauver)',
                                            handler: function() {
                                                this.up('form').getForm().setValues([
                                                    {
                                                        id: 'score_equipe_dom',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'score_equipe_ext',
                                                        value: 3
                                                    },
                                                    {
                                                        id: 'set_1_dom',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_1_ext',
                                                        value: 25
                                                    },
                                                    {
                                                        id: 'set_2_dom',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_2_ext',
                                                        value: 25
                                                    },
                                                    {
                                                        id: 'set_3_dom',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_3_ext',
                                                        value: 25
                                                    },
                                                    {
                                                        id: 'set_4_dom',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_4_ext',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_5_dom',
                                                        value: 0
                                                    },
                                                    {
                                                        id: 'set_5_ext',
                                                        value: 0
                                                    }
                                                ]);
                                            }
                                        },
                                        {
                                            xtype: 'numberfield',
                                            flex: 1,
                                            fieldLabel: 'Sets Exterieur',
                                            name: 'score_equipe_ext',
                                            minValue: 0,
                                            maxValue: 3
                                        },
                                        {
                                            xtype: 'numberfield',
                                            hideLabel: true,
                                            name: 'set_1_ext',
                                            minValue: 0
                                        },
                                        {
                                            xtype: 'numberfield',
                                            hideLabel: true,
                                            name: 'set_2_ext',
                                            minValue: 0
                                        },
                                        {
                                            xtype: 'numberfield',
                                            hideLabel: true,
                                            name: 'set_3_ext',
                                            minValue: 0
                                        },
                                        {
                                            xtype: 'numberfield',
                                            hideLabel: true,
                                            name: 'set_4_ext',
                                            minValue: 0
                                        },
                                        {
                                            xtype: 'numberfield',
                                            hideLabel: true,
                                            name: 'set_5_ext',
                                            minValue: 0
                                        }
                                    ]
                                }
                            ]
                        }
                    ],
                    buttons: [
                        {
                            text: 'Annuler',
                            handler: function() {
                                this.up('window').close();
                            }
                        },
                        {
                            text: 'Sauver',
                            formBind: true,
                            disabled: true,
                            handler: function() {
                                var button = this;
                                var form = button.up('form').getForm();
                                if (form.isValid()) {
                                    form.submit({
                                        success: function(form, action) {
                                            Ext.Msg.alert('Modification OK', action.result.message);
                                            button.up('window').close();
                                            me.getMatchesStore().load();
                                        },
                                        failure: function(form, action) {
                                            Ext.Msg.alert('Erreur', action.result.message);
                                        }
                                    });
                                }
                            }
                        }
                    ]
                }
            }).show();
            Ext.ComponentQuery.query('window[title=Modifier un match] > form')[0].getForm().loadRecord(rec);
        };
        afficheFormulaire();

    },
    deleteMatch: function(grid, rowIndex) {
        var me = this;
        var rec = grid.getStore().getAt(rowIndex);
        Ext.Msg.show({
            title: 'Suppression',
            msg: 'Cette opération entrainera irrémédiablement la suppression de ce match ! Êtes-vous sur de vouloir continuer ?',
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function(btn) {
                if (btn === 'ok') {
                    Ext.Ajax.request({
                        url: 'ajax/supprimerMatch.php',
                        params: {
                            code_match: rec.get('code_match')
                        },
                        success: function(response) {
                            var responseJson = Ext.decode(response.responseText);
                            Ext.Msg.alert('Info', responseJson.message);
                            me.getMatchesStore().load();
                        }
                    });
                }
            }
        });
    }
});