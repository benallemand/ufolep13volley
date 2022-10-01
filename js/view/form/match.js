Ext.define('Ufolep13Volley.view.form.match', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_match',
    layout: 'form',
    url: 'rest/action.php/update_match',
    trackResetOnLoad: true,
    defaults: {
        xtype: 'textfield',
        anchor: '95%',
        margin: '5 0 5 0',
    },
    autoScroll: true,
    layout: 'border',
    items: [
        {
            xtype: 'hidden',
            name: 'id_match'
        },
        {
            xtype: 'hidden',
            name: 'code_match'
        },
        {
            region: 'center',
            flex: 1,
            xtype: 'panel',
            layout: 'center',
            items: {
                xtype: 'displayfield',
                name: 'confrontation'
            }
        },
        {
            xtype: 'panel',
            flex: 4,
            region: 'south',
            layout: 'border',
            items: [
                {
                    region: 'center',
                    scrollable: true,
                    flex: 1,
                    xtype: 'panel',
                    layout: {
                        type: 'hbox',
                        align: 'stretch',
                    },
                    items: [
                        {
                            flex: 1,
                            xtype: 'panel',
                            scrollable: true,
                            title: 'Domicile',
                            layout: 'anchor',
                            items: [
                                {
                                    xtype: 'checkbox',
                                    name: 'forfait_dom',
                                    fieldLabel: 'forfait ?',
                                    listeners: {
                                        change: function (checkbox, new_val, old_val) {
                                            if (new_val === old_val) {
                                                return;
                                            }
                                            if (new_val) {
                                                checkbox.up('form').down('field[name=score_equipe_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=score_equipe_ext]').setValue(3);
                                            }
                                        }
                                    }
                                },
                                {
                                    xtype: 'numberfield',
                                    name: 'score_equipe_dom',
                                    minValue: 0,
                                    maxValue: 3,
                                    fieldLabel: 'Score',
                                },
                                {
                                    xtype: 'numberfield',
                                    name: 'set_1_dom',
                                    minValue: 0,
                                    fieldLabel: 'Set 1',
                                },
                                {
                                    xtype: 'numberfield',
                                    name: 'set_2_dom',
                                    minValue: 0,
                                    fieldLabel: 'Set 2',
                                },
                                {
                                    xtype: 'numberfield',
                                    name: 'set_3_dom',
                                    minValue: 0,
                                    fieldLabel: 'Set 3',
                                },
                                {
                                    xtype: 'numberfield',
                                    name: 'set_4_dom',
                                    minValue: 0,
                                    fieldLabel: 'Set 4',
                                },
                                {
                                    xtype: 'numberfield',
                                    name: 'set_5_dom',
                                    minValue: 0,
                                    fieldLabel: 'Set 5',
                                },
                            ]
                        },
                        {
                            flex: 1,
                            xtype: 'panel',
                            scrollable: true,
                            title: 'Extérieur',
                            layout: 'anchor',
                            items: [
                                {
                                    xtype: 'checkbox',
                                    name: 'forfait_ext',
                                    fieldLabel: 'forfait ?',
                                    listeners: {
                                        change: function (checkbox, new_val, old_val) {
                                            if (new_val === old_val) {
                                                return;
                                            }
                                            if (new_val) {
                                                checkbox.up('form').down('field[name=score_equipe_dom]').setValue(3);
                                                checkbox.up('form').down('field[name=score_equipe_ext]').setValue(0);
                                            }
                                        }
                                    }
                                },
                                {
                                    xtype: 'numberfield',
                                    minValue: 0,
                                    name: 'score_equipe_ext',
                                    maxValue: 3,
                                    fieldLabel: 'Score',
                                },
                                {
                                    xtype: 'numberfield',
                                    minValue: 0,
                                    name: 'set_1_ext',
                                },
                                {
                                    xtype: 'numberfield',
                                    minValue: 0,
                                    name: 'set_2_ext',
                                },
                                {
                                    xtype: 'numberfield',
                                    minValue: 0,
                                    name: 'set_3_ext',
                                },
                                {
                                    xtype: 'numberfield',
                                    minValue: 0,
                                    name: 'set_4_ext',
                                },
                                {
                                    xtype: 'numberfield',
                                    minValue: 0,
                                    name: 'set_5_ext',
                                },
                            ]
                        },
                    ]
                },
                {
                    region: 'east',
                    flex: 1,
                    xtype: 'panel',
                    title: "Feuilles de match",
                    layout: 'form',
                    scrollable: true,
                    items: [
                        {
                            xtype: 'displayfield',
                            value: "<p style='font-size: small'>Pour rappel, il est nécessaire de fournir:<br>" +
                                "- Les scans de la feuille de score recto verso(résultat et détail des sets)<br>" +
                                "- Les 2 fiches équipes<br>" +
                                "Formats acceptés: ZIP, PDF, JPG (taille raisonnable, merci de ne pas dépasser 3 Mo)<br>" +
                                "Les champs suivants sont optionnels (uniquement si plus d'1 fichier doit être joint à ce match)</p>"
                        },
                        {
                            xtype: 'filefield',
                            name: 'file1',
                            fieldLabel: 'Feuille 1/4',
                        },
                        {
                            xtype: 'filefield',
                            name: 'file2',
                            fieldLabel: 'Feuille 2/4',
                        },
                        {
                            xtype: 'filefield',
                            name: 'file3',
                            fieldLabel: 'Feuille 3/4',
                        },
                        {
                            xtype: 'filefield',
                            name: 'file4',
                            fieldLabel: 'Feuille 4/4',
                        },
                    ]
                }
            ]
        }
    ],
    buttons: [
        {
            text: 'Sauver',
            action: 'save',
            formBind: true,
            disabled: true
        },
        {
            text: 'Annuler',
            action: 'cancel',
        }
    ]
});