Ext.application({
    requires: ['Ext.container.Viewport'],
    controllers: ['register'],
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
                            html: "<h2 style='text-align: center'>DATE LIMITE D'INSCRIPTION: " + limit + " !</h2>"
                        }
                    ]
                },
                {
                    region: 'center',
                    flex: 2,
                    xtype: 'form_register',
                },
                {
                    region: 'east',
                    flex: 1,
                    split: true,
                    xtype: 'grid_register'
                }
            ]
        });
    }
});