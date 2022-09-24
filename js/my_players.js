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
                            text: "RETOUR A L'ACCUEIL",
                            iconCls: 'fa-solid fa-house',
                            href: '/',
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
                    flex: 1,
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