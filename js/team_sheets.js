Ext.application({
    requires: ['Ext.container.Viewport'],
    controllers: ['team_sheets'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function () {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                xtype: 'form_match_players',
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
                    '->',
                    {
                        xtype: 'displayfield',
                        name: 'confrontation-tbar'
                    },
                    '->', // greedy spacer so that the buttons are aligned to each side
                ],
            },
        });
    }
});