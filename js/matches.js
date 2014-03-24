Ext.onReady(function() {
    Ext.Date.dayNames = [
        "Dimanche",
        "Lundi",
        "Mardi",
        "Mercredi",
        "Jeudi",
        "Vendredi",
        "Samedi"
    ];
    Ext.QuickTips.init();
    var storeMatches = Ext.create('Ext.data.Store', {
        groupField: 'journee',
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
            url: 'ajax/getMatches.php',
            extraParams: {
                competition: competition,
                division: division
            },
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    });
    Ext.create('Ext.grid.Panel', {
        renderTo: Ext.get('matches'),
        title: 'Matches',
        store: storeMatches,
        width: 1000,
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
                    flex: 1,
                    dataIndex: 'code_match',
                    renderer: function(value, metaData, record) {
                        if (record.get('retard') === 1) {
                            metaData.tdAttr = 'style="background-color:VioletRed;color:black;" data-qtip="Match non renseign� de + de 10 jours!"';
                        }
                        if (record.get('retard') === 2) {
                            metaData.tdAttr = 'style="background-color:Red;color:black;" data-qtip="Match non renseign� de + de 15 jours!"';
                        }
                        return value;
                    }
                },
                {
                    header: 'Date',
                    flex: 3,
                    dataIndex: 'date_reception',
                    renderer: function(value, metaData, record) {
                        if (record.get('report') === true) {
                            metaData.tdAttr = 'style="background-color:Gold;color:black;" data-qtip="Match report�"';
                        }
                        return Ext.Date.format(value, 'l d/m/Y') + ' ' + record.get('heure_reception');
                    }
                },
                {
                    header: 'Equipe Domicile',
                    flex: 2,
                    dataIndex: 'equipe_dom',
                    renderer: function(value, metaData, record) {
                        if (record.get('score_equipe_dom') === 3) {
                            metaData.tdAttr = 'style="background-color:GreenYellow;color:black;"';
                        }
                        return value;
                    }
                },
                {
                    header: 'Score',
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
                    header: 'Equipe Ext�rieur',
                    flex: 2,
                    dataIndex: 'equipe_ext',
                    renderer: function(value, metaData, record) {
                        if (record.get('score_equipe_ext') === 3) {
                            metaData.tdAttr = 'style="background-color:GreenYellow;color:black;"';
                        }
                        return value;
                    }
                },
                {
                    header: 'Sets',
                    dataIndex: 'set_1_dom',
                    flex: 5,
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
                    header: 'Administration',
                    xtype: 'actioncolumn',
                    hideable: false,
                    hidden: true,
                    items: [
                        {
                            icon: 'images/certified.png',
                            tooltip: 'Certifier avoir re�u la feuille de ce match',
                            getClass: function(value, meta, rec) {
                                if (rec.get('certif') === true) {
                                    return "x-hide-display";
                                }
                            },
                            handler: function(grid, rowIndex) {
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
                                                    storeMatches.load();
                                                }
                                            });
                                        }
                                    }
                                });
                            }
                        },
                        {
                            icon: 'images/modif.gif',
                            tooltip: 'Modifier le score du match',
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
                                                    format: 'd/m/Y'
                                                },
                                                {
                                                    xtype: 'textfield',
                                                    fieldLabel: 'Heure',
                                                    name: 'heure_reception'
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
                                                                    text: 'Equipe ' + rec.get('equipe_ext') + ' forfait (pensez � sauver)',
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
                                                                    boxLabel: 'Match gagn� � 5',
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
                                                                    text: 'Equipe ' + rec.get('equipe_dom') + ' forfait (pensez � sauver)',
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
                                                                    boxLabel: 'Match gagn� � 5',
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
                                                                    storeMatches.load();
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
                        },
                        {
                            icon: 'images/delete.gif',
                            tooltip: 'Supprimer ce match',
                            handler: function(grid, rowIndex) {
                                var rec = grid.getStore().getAt(rowIndex);
                                Ext.Msg.show({
                                    title: 'Suppression',
                                    msg: 'Cette op�ration entrainera irr�m�diablement la suppression de ce match ! �tes-vous sur de vouloir continuer ?',
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
                                                    storeMatches.load();
                                                }
                                            });
                                        }
                                    }
                                });
                            }
                        }
                    ]
                }
            ],
            defaults: {
                flex: 1
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