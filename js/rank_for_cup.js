Ext.Loader.setConfig({
    enabled: true,
    paths: {
        'Ext.ux': 'js/ux' //Should be the path to the ux folder.
    }
});
Ext.application({
    requires: ['Ext.container.Viewport', 'Ext.ux.ExportableGrid'],
    controllers: ['rank_for_cup'],
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
                            xtype: 'label',
                            text: "Les 16 premiers sont qualifiés pour les phases finales"
                        }
                    ]
                },
                {
                    region: 'center',
                    flex: 1,
                    xtype: 'rank_for_cup_grid'
                }
            ]
        });
    }
});