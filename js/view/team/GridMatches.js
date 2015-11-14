Ext.define('Ufolep13Volley.view.team.GridMatches', {
    extend: 'Ext.grid.Panel',
    title: 'Matches',
    alias: 'widget.gridTeamMatches',
    store: 'MyMatches',
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
                width: 100,
                dataIndex: 'code_match',
                renderer: function (value, metaData, record) {
                    if (record.get('retard') === 1) {
                        metaData.tdAttr = 'data-qtip="Match non renseigné de + de 10 jours!"';
                        return '<span style="background-color:VioletRed;color:black">' + value + '</span>';
                    }
                    if (record.get('retard') === 2) {
                        metaData.tdAttr = 'data-qtip="Match non renseigné de + de 15 jours!"';
                        return '<span style="background-color:Red;color:black">' + value + '</span>';
                    }
                    return value;
                }
            },
            {
                header: 'Heure',
                width: 100,
                dataIndex: 'heure_reception'
            },
            {
                header: 'Date',
                width: 100,
                dataIndex: 'date_reception',
                renderer: function (value, metaData, record) {
                    if (record.get('report') === true) {
                        metaData.tdAttr = 'data-qtip="Match reporté"';
                        return '<span style="background-color:Gold;color:black">' + Ext.Date.format(value, 'l d/m/Y') + ' ' + record.get('heure_reception') + '</span>';
                    }
                    return Ext.Date.format(value, 'd/m/Y');
                }
            },
            {
                header: 'Rencontres',
                columns: [
                    {
                        header: '',
                        width: 200,
                        dataIndex: 'equipe_dom',
                        renderer: function (value, metaData, record) {
                            if (record.get('score_equipe_dom') > record.get('score_equipe_ext')) {
                                return '<span style="background-color:GreenYellow;color:black">' + value + '</span>';
                            }
                            return '<a href="teamSheetPdf.php?id=' + record.get('id_equipe_dom') + '" target="blank">' + value + '</a>';
                        }
                    },
                    {
                        header: '',
                        dataIndex: 'score_equipe_dom',
                        width: 50,
                        renderer: function (val, meta, rec) {
                            if ((rec.get('score_equipe_dom') === 3) || (rec.get('score_equipe_ext') === 3)) {
                                return rec.get('score_equipe_dom') + '/' + rec.get('score_equipe_ext');
                            }
                        }
                    },
                    {
                        header: '',
                        width: 200,
                        dataIndex: 'equipe_ext',
                        renderer: function (value, metaData, record) {
                            if (record.get('score_equipe_ext') > record.get('score_equipe_dom')) {
                                return '<span style="background-color:GreenYellow;color:black">' + value + '</span>';
                            }
                            return '<a href="teamSheetPdf.php?id=' + record.get('id_equipe_ext') + '" target="blank">' + value + '</a>';
                        }
                    }
                ]
            },
            {
                header: 'Détails de sets',
                dataIndex: 'set_1_dom',
                width: 300,
                renderer: function (val, meta, rec) {
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
                width: 100,
                xtype: 'actioncolumn',
                items: [
                    {
                        icon: 'images/certif.gif',
                        tooltip: 'Feuille de match reçue et certifiée',
                        getClass: function (value, meta, rec) {
                            if (rec.get('certif') === false) {
                                return "x-hidden-display";
                            }
                        }
                    },
                    {
                        icon: 'images/modif.gif',
                        tooltip: 'Modifier le score',
                        getClass: function (value, meta, rec) {
                            if (rec.get('certif') === true) {
                                return "x-hidden-display";
                            }
                        },
                        handler: function (grid, rowIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            var afficheFormulaire = function () {
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
                                        dockedItems: [
                                            {
                                                xtype: 'toolbar',
                                                dock: 'top',
                                                items: [
                                                    'Raccourcis : ',
                                                    {
                                                        xtype: 'button',
                                                        margin: 10,
                                                        text: 'Equipe ' + rec.get('equipe_ext') + ' forfait (pensez à sauver)',
                                                        handler: function () {
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
                                                        xtype: 'button',
                                                        margin: 10,
                                                        text: 'Equipe ' + rec.get('equipe_dom') + ' forfait (pensez à sauver)',
                                                        handler: function () {
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
                                                    }
                                                ]
                                            }
                                        ],
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
                                                                xtype: 'numberfield',
                                                                fieldLabel: 'Sets Domicile',
                                                                name: 'score_equipe_dom',
                                                                minValue: 0,
                                                                maxValue: 3,
                                                                tabIndex: 1
                                                            },
                                                            {
                                                                xtype: 'numberfield',
                                                                fieldLabel: 'Set 1',
                                                                name: 'set_1_dom',
                                                                minValue: 0,
                                                                tabIndex: 3
                                                            },
                                                            {
                                                                xtype: 'numberfield',
                                                                fieldLabel: 'Set 2',
                                                                name: 'set_2_dom',
                                                                minValue: 0,
                                                                tabIndex: 5
                                                            },
                                                            {
                                                                xtype: 'numberfield',
                                                                fieldLabel: 'Set 3',
                                                                name: 'set_3_dom',
                                                                minValue: 0,
                                                                tabIndex: 7
                                                            },
                                                            {
                                                                xtype: 'numberfield',
                                                                fieldLabel: 'Set 4',
                                                                name: 'set_4_dom',
                                                                minValue: 0,
                                                                tabIndex: 9
                                                            },
                                                            {
                                                                xtype: 'numberfield',
                                                                fieldLabel: 'Set 5',
                                                                name: 'set_5_dom',
                                                                minValue: 0,
                                                                tabIndex: 11
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
                                                                xtype: 'numberfield',
                                                                flex: 1,
                                                                fieldLabel: 'Sets Exterieur',
                                                                name: 'score_equipe_ext',
                                                                minValue: 0,
                                                                maxValue: 3,
                                                                tabIndex: 2
                                                            },
                                                            {
                                                                xtype: 'numberfield',
                                                                hideLabel: true,
                                                                name: 'set_1_ext',
                                                                minValue: 0,
                                                                tabIndex: 4
                                                            },
                                                            {
                                                                xtype: 'numberfield',
                                                                hideLabel: true,
                                                                name: 'set_2_ext',
                                                                minValue: 0,
                                                                tabIndex: 6
                                                            },
                                                            {
                                                                xtype: 'numberfield',
                                                                hideLabel: true,
                                                                name: 'set_3_ext',
                                                                minValue: 0,
                                                                tabIndex: 8
                                                            },
                                                            {
                                                                xtype: 'numberfield',
                                                                hideLabel: true,
                                                                name: 'set_4_ext',
                                                                minValue: 0,
                                                                tabIndex: 10
                                                            },
                                                            {
                                                                xtype: 'numberfield',
                                                                hideLabel: true,
                                                                name: 'set_5_ext',
                                                                minValue: 0,
                                                                tabIndex: 12
                                                            }
                                                        ]
                                                    }
                                                ]
                                            }
                                        ],
                                        buttons: [
                                            {
                                                text: 'Annuler',
                                                handler: function () {
                                                    this.up('window').close();
                                                }
                                            },
                                            {
                                                text: 'Sauver',
                                                formBind: true,
                                                disabled: true,
                                                handler: function () {
                                                    var button = this;
                                                    var form = button.up('form').getForm();
                                                    if (form.isValid()) {
                                                        form.submit({
                                                            success: function (form, action) {
                                                                Ext.Msg.alert('Modification OK', action.result.message);
                                                                button.up('window').close();
                                                                Ext.ComponentQuery.query('grid[title=Matches]')[0].getStore().load();
                                                            },
                                                            failure: function (form, action) {
                                                                Ext.Msg.alert('Erreur', action.result.message);
                                                            }
                                                        });
                                                    }
                                                }
                                            }
                                        ]
                                    }
                                }).show();
                                var form = Ext.ComponentQuery.query('window[title=Modifier un match] > form')[0];
                                form.getForm().loadRecord(rec);
                                form.down('textfield[name=score_equipe_dom]').focus(true, 10);
                            };
                            afficheFormulaire();
                        }
                    },
                    {
                        icon: 'images/email-icon.png',
                        tooltip: 'Envoi par email de la feuille de match',
                        getClass: function (value, meta, rec) {
                            if (rec.get('certif') === true) {
                                return "x-hidden-display";
                            }
                        },
                        handler: function (grid, rowIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            var codeCompetition = rec.get('code_competition');
                            var division = rec.get('division');
                            var email = '';
                            switch (codeCompetition) {
                                case 'f':
                                    email = 'd' + division + 'f-4x4-ufolep13-volley@googlegroups.com';
                                    break;
                                case 'm':
                                    email = 'd' + division + 'm-6x6-ufolep13-volley@googlegroups.com';
                                    break;
                                case 'c':
                                    if (division <= 3) {
                                        email = 'p1a3-isoardi-ufolep13-volley@googlegroups.com';
                                    }
                                    else if (division <= 6) {
                                        email = 'p4a6-isoardi-ufolep13-volley@googlegroups.com';
                                    }
                                    else if (division <= 9) {
                                        email = 'p7a9-isoardi-ufolep13-volley@googlegroups.com';
                                    }
                                    else if (division <= 11) {
                                        email = 'p10et11-isoardi-ufolep13-volley@googlegroups.com';
                                    }
                                    break;
                                case 'cf':
                                    email = 'isoardi-ufolep13-volley@googlegroups.com';
                                    break;
                                case 'kh':
                                    if (division <= 3) {
                                        email = 'p1a3-khanna-ufolep13-volley@googlegroups.com';
                                    }
                                    else if (division <= 6) {
                                        email = 'p4a6-khanna-ufolep13-volley@googlegroups.com';
                                    }
                                    break;
                                case 'kf':
                                    email = 'khanna-ufolep13-volley@googlegroups.com';
                                    break;
                                default:
                                    break;
                            }
                            var link = "mailto:" + email + "?" + Ext.Object.toQueryString({
                                subject: "Match " + rec.get('code_match') + " : " + rec.get('equipe_dom') + " contre " + rec.get('equipe_ext'),
                                body: "Bonjour,\n\
Veuillez trouver ci-joint les fiches équipes ainsi que la feuille de match.\n\
Bien cordialement"
                            });
                            window.open(link, '_blank');
                        }
                    }
                ]
            }
        ]
    }
});