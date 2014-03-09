Ext.onReady(function() {
    Ext.QuickTips.init();
    var storeMyMatches = Ext.create('Ext.data.Store', {
        groupField: 'libelle_competition',
        fields: [
            {
                name: 'id_match',
                type: 'int'
            },
            {
                name: 'code_match',
                type: 'string'
            },
            {
                name: 'code_competition',
                type: 'string'
            },
            {
                name: 'libelle_competition',
                type: 'string'
            },
            {
                name: 'division',
                type: 'int'
            },
            {
                name: 'journee',
                type: 'string'
            },
            {
                name: 'id_equipe_dom',
                type: 'string'
            },
            {
                name: 'id_equipe_ext',
                type: 'string'
            },
            {
                name: 'equipe_dom',
                type: 'string'
            },
            {
                name: 'equipe_ext',
                type: 'string'
            },
            {
                name: 'score_equipe_dom',
                type: 'int'
            },
            {
                name: 'score_equipe_ext',
                type: 'int'
            },
            {
                name: 'set_1_dom',
                type: 'int'
            },
            {
                name: 'set_1_ext',
                type: 'int'
            },
            {
                name: 'set_2_dom',
                type: 'int'
            },
            {
                name: 'set_2_ext',
                type: 'int'
            },
            {
                name: 'set_3_dom',
                type: 'int'
            },
            {
                name: 'set_3_ext',
                type: 'int'
            },
            {
                name: 'set_4_dom',
                type: 'int'
            },
            {
                name: 'set_4_ext',
                type: 'int'
            },
            {
                name: 'set_5_dom',
                type: 'int'
            },
            {
                name: 'set_5_ext',
                type: 'int'
            },
            {
                name: 'heure_reception',
                type: 'string'
            },
            {
                name: 'date_reception',
                type: 'date',
                dateFormat: 'Y-m-d'
            },
            {
                name: 'gagnea5_dom',
                type: 'bool'
            },
            {
                name: 'gagnea5_ext',
                type: 'bool'
            },
            {
                name: 'forfait_dom',
                type: 'bool'
            },
            {
                name: 'forfait_ext',
                type: 'bool'
            },
            {
                name: 'certif',
                type: 'bool'
            },
            {
                name: 'report',
                type: 'bool'
            },
            {
                name: 'retard',
                type: 'int'
            }

        ],
        proxy: {
            type: 'ajax',
            url: 'ajax/getMesMatches.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    });
    Ext.create('Ext.panel.Panel', {
        renderTo: Ext.get('liste_matches_equipe'),
        width: 980,
        height: 400,
        layout: 'fit',
        items: {
            xtype: 'grid',
            store: storeMyMatches,
            autoScroll: true,
            features: [
                {
                    ftype: 'grouping',
                    groupHeaderTpl: '{name}'
                }
            ],
            columns: {
                items: [
                    {
                        header: 'Code',
                        dataIndex: 'code_match',
                        renderer: function(value, metaData, record) {
                            if (record.get('retard') === 1) {
                                metaData.tdAttr = 'style="background-color:VioletRed;color:black;" data-qtip="Match non renseigné de + de 10 jours!"';
                            }
                            if (record.get('retard') === 2) {
                                metaData.tdAttr = 'style="background-color:Red;color:black;" data-qtip="Match non renseigné de + de 15 jours!"';
                            }
                            return value;
                        }
                    },
                    {
                        header: 'Heure',
                        dataIndex: 'heure_reception'
                    },
                    {
                        header: 'Date',
                        dataIndex: 'date_reception',
                        renderer: function(value, metaData, record) {
                            if (record.get('report') === true) {
                                metaData.tdAttr = 'style="background-color:Gold;color:black;" data-qtip="Match reporté"';
                            }
                            return Ext.Date.format(value, 'd/m/Y');
                        }
                    },
                    {
                        header: 'Rencontres',
                        columns: [
                            {
                                header: '',
                                dataIndex: 'equipe_dom',
                                renderer: function(value, metaData, record) {
                                    if (record.get('score_equipe_dom') === 3) {
                                        metaData.tdAttr = 'style="background-color:GreenYellow;color:black;"';
                                    }
                                    return value;
                                }
                            },
                            {
                                header: '',
                                dataIndex: 'score_equipe_dom',
                                flex: null,
                                width: 50,
                                renderer: function(val, meta, rec) {
                                    if ((rec.get('score_equipe_dom') === 3) || (rec.get('score_equipe_ext') === 3)) {
                                        return rec.get('score_equipe_dom') + '/' + rec.get('score_equipe_ext');
                                    }
                                }
                            },
                            {
                                header: '',
                                dataIndex: 'equipe_ext',
                                renderer: function(value, metaData, record) {
                                    if (record.get('score_equipe_ext') === 3) {
                                        metaData.tdAttr = 'style="background-color:GreenYellow;color:black;"';
                                    }
                                    return value;
                                }
                            }
                        ]
                    },
                    {
                        header: 'Détails de sets',
                        dataIndex: 'set_1_dom',
                        flex: 2,
                        renderer: function(val, meta, rec) {
                            var detailsMatch = '';
                            if ((rec.get('set_1_dom') !== 0) || (rec.get('set_1_ext') !== 0)) {
                                detailsMatch = detailsMatch + rec.get('set_1_dom') + '/' + rec.get('set_1_ext') + ' ';
                            }
                            if ((rec.get('set_2_dom') !== 0) || (rec.get('set_2_ext') !== 0)) {
                                detailsMatch = detailsMatch + rec.get('set_2_dom') + '/' + rec.get('set_2_ext') + ' ';
                            }
                            if ((rec.get('set_3_dom') !== 0) || (rec.get('set_3_ext') !== 0)) {
                                detailsMatch = detailsMatch + rec.get('set_3_dom') + '/' + rec.get('set_3_ext') + ' ';
                            }
                            if ((rec.get('set_4_dom') !== 0) || (rec.get('set_4_ext') !== 0)) {
                                detailsMatch = detailsMatch + rec.get('set_4_dom') + '/' + rec.get('set_4_ext') + ' ';
                            }
                            if ((rec.get('set_5_dom') !== 0) || (rec.get('set_5_ext') !== 0)) {
                                detailsMatch = detailsMatch + rec.get('set_5_dom') + '/' + rec.get('set_5_ext') + ' ';
                            }
                            return detailsMatch;
                        }
                    },
                    {
                        header: '',
                        xtype: 'actioncolumn',
                        items: [
                            {
                                icon: 'images/certif.gif',
                                tooltip: 'Feuille de match reçue et certifiée',
                                getClass: function(value, meta, rec) {
                                    if (rec.get('certif') === false) {
                                        return "x-hide-display";
                                    }
                                }
                            },
                            {
                                icon: 'images/modif.gif',
                                tooltip: 'Modifier le score',
                                handler: function(grid, rowIndex) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    afficheFormulaire = function() {
                                        Ext.create('Ext.window.Window', {
                                            title: 'Modifier un match',
                                            height: 600,
                                            width: 700,
                                            modal: true,
                                            layout: 'fit',
                                            items: {
                                                xtype: 'form',
                                                layout: 'anchor',
                                                url: 'ajax/modifierMonMatch.php',
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
                                                        readOnly: true
                                                    },
                                                    {
                                                        xtype: 'textfield',
                                                        fieldLabel: 'Heure',
                                                        name: 'heure_reception',
                                                        readOnly: true
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
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        boxLabel: 'Match gagné à 5',
                                                                        name: 'gagnea5_dom'
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
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        boxLabel: 'Match gagné à 5',
                                                                        name: 'gagnea5_ext'
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
                                                                        storeMyMatches.load();
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
                                }
                            }
                        ]
                    }
                ],
                defaults: {
                    flex: 1
                }
            }
        }
    });
    Ext.Ajax.request({
        url: 'ajax/getSessionRights.php',
        success: function(response) {
            var responseJson = Ext.decode(response.responseText);
            if (responseJson.message === 'admin') {
                var adminColumns = Ext.ComponentQuery.query('actioncolumn[text=Administration]');
                Ext.each(adminColumns, function(adminColumn) {
                    adminColumn.show();
                });
            }
        }
    });
});