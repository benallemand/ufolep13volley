Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel'],
    controllers: controllers,
    stores: [],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function () {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                layout: 'border',
                width: 1280,
                height: 2048,
                items: [
                    {
                        region: 'north',
                        split: true,
                        xtype: 'headerPanel'
                    },
                    {
                        region: 'center',
                        flex: 1,
                        layout: 'center',
                        items: {
                            width: '80%',
                            xtype: 'form',
                            title: 'Connexion',
                            layout: 'form',
                            standardSubmit: true,
                            autoEl: {
                                tag: 'form',
                                method: 'POST',
                                action: 'login.php',
                                target: 'submitButton',
                                autocomplete: 'on'
                            },
                            items: [
                                {
                                    xtype: 'textfield',
                                    name: 'login',
                                    fieldLabel: 'Login',
                                    allowBlank: false
                                },
                                {
                                    xtype: 'textfield',
                                    name: 'password',
                                    fieldLabel: 'Mot de passe',
                                    inputType: 'password',
                                    allowBlank: false
                                },
                                {
                                    xtype: 'component',
                                    html: '<input type="submit" id="submitButton" formtarget="_self" style="display:none">'
                                }
                            ],
                            buttons: [
                                {
                                    text: 'Inscription',
                                    action: 'showUserRegistration',
                                    style: "background-color:green;background-image:none"
                                },
                                '->',
                                {
                                    text: 'Connexion',
                                    handler: function () {
                                        Ext.get('submitButton').dom.click();
                                    }
                                }

                            ]
                        }
                    }
                ]
            }
        });
    }
});


