Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel', 'login.AutoCompleteField'],
    controllers: ['Login'],
    stores: [],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                layout: 'border',
                width: 1280,
                height: 2048,
                items: [
                    {
                        region: 'north',
                        xtype: 'headerPanel'
                    },
                    {
                        region: 'north',
                        xtype: 'titlePanel'
                    },
                    {
                        region: 'center',
                        flex: 1,
                        layout: 'center',
                        items: {
                            width: '50%',
                            xtype: 'form',
                            title: 'Connexion',
                            layout: 'anchor',
                            defaults: {
                                anchor: '100%',
                                margin: 10
                            },
                            autoEl: {
                                tag: 'form'
                            },
                            url: 'ajax/login.php',
                            items: [
                                {
                                    xtype: 'actextfield',
                                    name: 'login',
                                    fieldLabel: 'Login',
                                    allowBlank: false
                                },
                                {
                                    xtype: 'actextfield',
                                    name: 'password',
                                    fieldLabel: 'Mot de passe',
                                    inputType: 'password',
                                    allowBlank: false
                                }
                            ],
                            buttons: [
                                {
                                    text: 'Connexion',
                                    preventDefault: false,
                                    handler: function() {
                                        var form = this.up('form').getForm();
                                        if (form.isValid()) {
                                            form.submit({
                                                success: function(form, action) {
                                                    location.reload();
                                                },
                                                failure: function(form, action) {
                                                    Ext.Msg.alert('Erreur', action.result ? action.result.message : 'No response');
                                                }
                                            });
                                        }
                                    }
                                },
                                {
                                    text: 'Inscription',
                                    action: 'showUserRegistration',
                                    style: "background-color:green;background-image:none"
                                }
                            ]
                        }
                    }
                ]
            }
        });
    }
});


