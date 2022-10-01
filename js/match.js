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
                            href: 'javascript:history.back()',
                        }
                    ]
                },
                {
                    region: 'north',
                    layout: {
                        type: 'hbox',
                        align: 'stretch',
                    },
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
                },
            ]
        });
    }
});