Ext.application({
    requires: ['Ext.container.Viewport'],
    controllers: ['my_players'],
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
                            handler: function() {
                                history.back();
                            }
                        },
                        {
                            text: "Télécharger la fiche équipe",
                            iconCls: 'fa-solid fa-clipboard-list',
                            href: '/teamSheetPdf.php'
                        }
                    ]
                },
                {
                    region: 'center',
                    flex: 2,
                    xtype: 'grid_my_players',
                },
                {
                    region: 'east',
                    flex: 1,
                    split: true,
                    xtype: 'form_player'
                }
            ]
        });
    }
});