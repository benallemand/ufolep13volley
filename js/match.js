Ext.application({
    requires: ['Ext.container.Viewport'],
    controllers: ['match'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function () {
        var navigate = function (panel, direction) {
            var layout = panel.getLayout();
            layout[direction]();
            Ext.getCmp('move-prev').setDisabled(!layout.getPrev());
            Ext.getCmp('move-next').setDisabled(!layout.getNext());
        };

        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                layout: 'card',
                tbar: [
                    {
                        xtype: 'button',
                        scale: 'medium',
                        margin: 5,
                        text: "Fermer",
                        iconCls: 'fa-solid fa-close',
                        handler: function () {
                            history.back();
                        }
                    },
                    {
                        id: 'move-prev',
                        text: 'Précédent',
                        iconCls: 'fa-solid fa-arrow-left',
                        handler: function (btn) {
                            navigate(btn.up("panel"), "prev");
                        },
                        disabled: true
                    },
                    '->',
                    {
                        xtype: 'displayfield',
                        name: 'confrontation-tbar'
                    },
                    '->', // greedy spacer so that the buttons are aligned to each side
                    {
                        id: 'move-next',
                        text: 'Suivant',
                        iconCls: 'fa-solid fa-arrow-right',
                        handler: function (btn) {
                            navigate(btn.up("panel"), "next");
                        }
                    },
                ],
                items: [
                    {
                        layout: 'fit',
                        title: 'Fiche équipe',
                        items: [
                            {
                                layout: 'anchor',
                                scrollable: true,
                                items: [
                                    {
                                        xtype: 'form_match_players',
                                        height: 150,
                                    },
                                    {
                                        title: 'Présents',
                                        flex: 1,
                                        items: {
                                            xtype: 'view_match_players',
                                        }
                                    },
                                    {
                                        layout: 'center',
                                        items: [
                                            {
                                                xtype: 'button',
                                                action: 'sign_team_sheet',
                                                iconCls: 'fa-solid fa-signature',
                                                text: 'Signer la fiche équipe'
                                            },
                                        ]
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        layout: 'fit',
                        title: 'Feuille de match',
                        items: [
                            {
                                xtype: 'form_match'
                            }
                        ]
                    },
                ]
            }

        });
    }
});