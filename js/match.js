Ext.application({
    requires: ['Ext.container.Viewport'],
    controllers: ['match'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function () {
        Ext.create('Ext.container.Viewport', {
            layout: 'border',
            items: [
                {
                    region: 'north',
                    margin: 10,
                    defaults: {
                        xtype: 'button',
                        scale: 'medium',
                        margin: 5,
                    },
                    items: [
                        {
                            text: "RETOUR",
                            iconCls: 'fa-solid fa-arrow-left',
                            handler: function () {
                                history.back();
                            }
                        }
                    ]
                },
                {
                    region: 'north',
                    layout: {
                        type: 'hbox',
                        align: 'stretch',
                    },
                    bbar: [
                        {
                            xtype: 'button',
                            action: 'sign_team_sheet',
                            iconCls: 'fa-solid fa-signature',
                            text: 'Signer la fiche équipe'
                        },
                    ],
                    items: [
                        {
                            xtype: 'form_match_players',
                            flex: 1,
                        },
                        {
                            title: 'Présents',
                            flex: 2,
                            items: {
                                xtype: 'view_match_players',
                                scrollable: true,
                                height: 200
                            }
                        }
                    ]
                },
                {
                    region: 'center',
                    xtype: 'form_match',
                    bbar: [
                        {
                            xtype: 'button',
                            action: 'sign_match_sheet',
                            iconCls: 'fa-solid fa-signature',
                            text: 'Signer la feuille de match'
                        },
                    ],
                },
            ]
        });
    }
});