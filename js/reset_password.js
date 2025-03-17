Ext.application({
    requires: ['Ext.container.Viewport', 'Ext.ux.ExportableGrid'],
    controllers: ['reset_password'],
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
                            hrefTarget: '_self',
                        },
                        {
                            xtype: 'label',
                            html: "<h1>Demande d'initialisation de mot de passe</h1>"
                        }
                    ]
                },
                {
                    region: 'center',
                    flex: 1,
                    xtype: 'form_reset_password',
                },
            ]
        });
    }
});