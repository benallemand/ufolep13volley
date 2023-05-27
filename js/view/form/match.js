Ext.define('Ufolep13Volley.view.form.match', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_match',
    layout: 'form',
    url: 'rest/action.php/matchmgr/save_match',
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
            region: 'north',
            width: 200,
            xtype: 'panel',
            scrollable: true,
            title: 'Signatures',
            layout: 'hbox',
            defaults: {
                flex: 1
            },
            items: [
                {
                    xtype: 'panel',
                    title: 'Fiches équipes',
                    layout: 'hbox',
                    defaults: {
                        flex: 1
                    },
                    items: [
                        {
                            xtype: 'checkbox',
                            submitValue: false,
                            readOnly: true,
                            name: 'is_sign_team_dom',
                            boxLabel: 'Domicile',
                        },
                        {
                            xtype: 'checkbox',
                            submitValue: false,
                            readOnly: true,
                            name: 'is_sign_team_ext',
                            boxLabel: 'Extérieur',
                        },
                    ]
                },
                {
                    xtype: 'panel',
                    title: 'Feuille de match',
                    layout: 'hbox',
                    defaults: {
                        flex: 1
                    },
                    items: [
                        {
                            xtype: 'checkbox',
                            submitValue: false,
                            readOnly: true,
                            name: 'is_sign_match_dom',
                            boxLabel: 'Domicile',
                        },
                        {
                            xtype: 'checkbox',
                            submitValue: false,
                            readOnly: true,
                            name: 'is_sign_match_ext',
                            boxLabel: 'Extérieur',
                        },
                    ]
                }
            ]
        },
        {
            xtype: 'panel',
            flex: 1,
            region: 'center',
            layout: 'form',
            scrollable: true,
            items: [
                {
                    height: 450,
                    xtype: 'panel',
                    title: 'Score',
                    layout: {
                        type: 'hbox',
                        align: 'stretch',
                    },
                    items: [
                        {
                            flex: 1,
                            margin: 5,
                            xtype: 'panel',
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
                                                checkbox.up('form').down('field[name=set_1_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_2_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_3_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_4_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_5_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=score_equipe_ext]').setValue(3);
                                                checkbox.up('form').down('field[name=set_1_ext]').setValue(25);
                                                checkbox.up('form').down('field[name=set_2_ext]').setValue(25);
                                                checkbox.up('form').down('field[name=set_3_ext]').setValue(25);
                                                checkbox.up('form').down('field[name=set_4_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_5_ext]').setValue(0);
                                            } else {
                                                checkbox.up('form').down('field[name=score_equipe_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_1_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_2_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_3_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_4_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_5_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=score_equipe_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_1_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_2_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_3_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_4_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_5_ext]').setValue(0);
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
                            margin: 5,
                            xtype: 'panel',
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
                                                checkbox.up('form').down('field[name=set_1_dom]').setValue(25);
                                                checkbox.up('form').down('field[name=set_2_dom]').setValue(25);
                                                checkbox.up('form').down('field[name=set_3_dom]').setValue(25);
                                                checkbox.up('form').down('field[name=set_4_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_5_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=score_equipe_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_1_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_2_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_3_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_4_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_5_ext]').setValue(0);
                                            } else {
                                                checkbox.up('form').down('field[name=score_equipe_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_1_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_2_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_3_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_4_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=set_5_dom]').setValue(0);
                                                checkbox.up('form').down('field[name=score_equipe_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_1_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_2_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_3_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_4_ext]').setValue(0);
                                                checkbox.up('form').down('field[name=set_5_ext]').setValue(0);
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
                                    fieldLabel: 'Set 1',
                                },
                                {
                                    xtype: 'numberfield',
                                    minValue: 0,
                                    name: 'set_2_ext',
                                    fieldLabel: 'Set 2',
                                },
                                {
                                    xtype: 'numberfield',
                                    minValue: 0,
                                    name: 'set_3_ext',
                                    fieldLabel: 'Set 3',
                                },
                                {
                                    xtype: 'numberfield',
                                    minValue: 0,
                                    name: 'set_4_ext',
                                    fieldLabel: 'Set 4',
                                },
                                {
                                    xtype: 'numberfield',
                                    minValue: 0,
                                    name: 'set_5_ext',
                                    fieldLabel: 'Set 5',
                                },
                            ]
                        },
                    ]
                },
                {
                    xtype: 'panel',
                    height: 180,
                    title: "Commentaire",
                    layout: 'form',
                    scrollable: true,
                    items: [
                        {
                            xtype: 'textarea',
                            name: 'note',
                            fieldLabel: 'Commentaire',
                            readOnly: !Ext.Array.contains(['ADMINISTRATEUR', 'RESPONSABLE_EQUIPE'], user_details.profile_name),
                        }
                    ]
                },
                {
                    xtype: 'panel',
                    height: 450,
                    title: "Feuilles de match (uniquement si non signées en ligne)",
                    scrollable: true,
                    collapsible: true,
                    collapsed: true,
                    layout: 'form',
                    items: [
                        {
                            xtype: 'displayfield',
                            value: "<p style='font-size: small'>Pour rappel, il est nécessaire de fournir:<br>" +
                                "- Les scans de la feuille de score (résultat et détail des sets)<br>" +
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
                        {
                            xtype: 'displayfield',
                            name: 'files_paths_html',
                            fieldLabel: "Déjà attaché(s)",
                        }
                    ]
                },
            ]
        },
    ],
    buttons: [
        {
            text: 'Enregistrer le score',
            formBind: true,
            disabled: true,
            action: 'save',
            iconCls: 'fa-solid fa-floppy-disk',
        },
        {
            action: 'sign_match_sheet',
            iconCls: 'fa-solid fa-signature',
            text: 'Signer la feuille de match'
        },
    ]
});