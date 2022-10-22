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
                            html: "<h2 style='text-align: center'>DATE LIMITE D'INSCRIPTION: " + (Ext.Date.format(Ext.Date.parse(limit, 'd/m/Y'), 'l d F Y')).toUpperCase() + " !</h2>"
                        }
                    ]
                },
                (Ext.Date.now() > Ext.Date.parse(limit, 'd/m/Y') && (user_details.profile_name !== 'ADMINISTRATEUR')) ?
                    null : {
                        region: 'west',
                        flex: 2,
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