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
                    autoScroll: true,
                    margin: 10,
                    defaults: {
                        xtype: 'button',
                        scale: 'medium',
                        margin: 5,
                    },
                    layout: 'hbox',
                    items: [
                        {
                            text: "RETOUR A L'ACCUEIL",
                            iconCls: 'fa-solid fa-house',
                            href: '/',
                        },
                        {
                            xtype: 'label',
                            html: Ext.String.format("<div>Nous sommes le : {0}</div>", Ext.Date.format(new Date(), 'd/m/Y')),
                        },
                        {
                            xtype: 'label',
                            html: limit_html_label
                        }
                    ]
                },
                {
                    region: 'west',
                    flex: 3,
                    split: true,
                    xtype: 'form_register',
                },
                {
                    region: 'center',
                    flex: 1,
                    xtype: 'grid_register'
                }
            ]
        });
    }
});