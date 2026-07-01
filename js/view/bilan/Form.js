Ext.define('Ufolep13Volley.view.bilan.Form', {
    extend: 'Ext.form.Panel',
    alias: 'widget.bilanform',
    title: 'Bilan annuel',
    closable: true,
    bodyPadding: 15,
    autoScroll: true,
    scrollable: true,
    defaults: {
        anchor: '100%'
    },
    initComponent: function () {
        var now = new Date();
        var y = now.getFullYear();
        var m = now.getMonth() + 1;
        // annee de fin de la derniere saison terminee (une saison finit en juin)
        var y2 = (m >= 7) ? y : y - 1;
        var defaultSaison = (y2 - 1) + '-' + y2;

        this.items = [
            {
                xtype: 'fieldset',
                title: 'Saison',
                items: [
                    {
                        xtype: 'fieldcontainer',
                        layout: 'hbox',
                        items: [
                            {
                                xtype: 'textfield',
                                name: 'saison',
                                fieldLabel: 'Saison',
                                labelWidth: 60,
                                width: 220,
                                value: defaultSaison,
                                regex: /^\d{4}-\d{4}$/,
                                regexText: "Format attendu : AAAA-AAAA (ex : 2025-2026)",
                                allowBlank: false
                            },
                            {
                                xtype: 'button',
                                text: 'Charger les chiffres',
                                glyph: 'xf019@FontAwesome',
                                margin: '0 0 0 10',
                                action: 'loadBilanData'
                            }
                        ]
                    }
                ]
            },
            {
                xtype: 'fieldset',
                title: 'Chiffres extraits de la base (lecture seule)',
                items: [
                    {
                        xtype: 'displayfield',
                        name: 'apercu',
                        hideLabel: true,
                        value: '<em>Cliquez sur « Charger les chiffres » pour extraire les données de la saison.</em>'
                    }
                ]
            },
            {
                xtype: 'fieldset',
                title: 'Commentaires à compléter',
                defaults: {
                    anchor: '100%',
                    labelAlign: 'top'
                },
                items: [
                    {
                        xtype: 'textfield',
                        name: 'responsable',
                        fieldLabel: 'Responsable de la commission',
                        labelAlign: 'left',
                        labelWidth: 200,
                        width: 500
                    },
                    {
                        xtype: 'textarea',
                        name: 'types_public',
                        fieldLabel: 'Types de public participant',
                        height: 60,
                        value: "Adultes féminins et masculins\n"
                            + "Jeunes (16 ans + avec accord parental géré par le club d'affiliation)"
                    },
                    {
                        xtype: 'textarea',
                        name: 'formations',
                        fieldLabel: 'Formations effectuées',
                        height: 45,
                        value: 'Néant'
                    },
                    {
                        xtype: 'textarea',
                        name: 'reunions',
                        fieldLabel: 'Réunions statutaires / participation',
                        height: 60
                    },
                    {
                        xtype: 'textarea',
                        name: 'impression_generale',
                        fieldLabel: 'Impression générale sur la saison / besoins',
                        height: 100
                    },
                    {
                        xtype: 'textarea',
                        name: 'coupe_nationale',
                        fieldLabel: 'Coupe nationale',
                        height: 100
                    },
                    {
                        xtype: 'textarea',
                        name: 'axes_amelioration',
                        fieldLabel: "Axes d'amélioration",
                        height: 100
                    }
                ]
            }
        ];

        this.dockedItems = [
            {
                xtype: 'toolbar',
                dock: 'bottom',
                items: [
                    '->',
                    {
                        xtype: 'button',
                        text: 'Télécharger le PDF',
                        glyph: 'xf1c1@FontAwesome',
                        scale: 'medium',
                        action: 'downloadBilanPdf'
                    }
                ]
            }
        ];

        this.callParent(arguments);
    }
});
