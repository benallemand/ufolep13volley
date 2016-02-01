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
                            autoEl: {
                                tag: 'form',
                                method: 'POST',
                                action: 'blank.html',
                                target: 'submitTarget'
                            },
                            title: 'Connexion',
                            layout: 'anchor',
                            defaults: {
                                anchor: '100%',
                                margin: 10
                            },
                            url: 'ajax/login.php',
                            items: [
                                {
                                    xtype: 'textfield',
                                    name: 'login',
                                    fieldLabel: 'Login',
                                    allowBlank: false,
                                    'inputAttrTpl': [
                                        'autocomplete="on"'
                                    ]
                                },
                                {
                                    xtype: 'textfield',
                                    name: 'password',
                                    fieldLabel: 'Mot de passe',
                                    inputType: 'password',
                                    allowBlank: false,
                                    'inputAttrTpl': [
                                        'autocomplete="on"'
                                    ]
                                },
                                {
                                    xtype: 'component',
                                    html: '<iframe id="submitTarget" name="submitTarget" style="display:none"></iframe>'
                                },
                                {
                                    xtype: 'component',
                                    html: '<input type="submit" id="submitButton" style="display:none">'
                                }
                            ],
                            buttons: [
                                {
                                    text: 'Connexion',
                                    preventDefault: false,
                                    handler: function () {
                                        var form = this.up('form').getForm();
                                        if (form.isValid()) {
                                            form.submit({
                                                success: function () {
                                                    location.reload();
                                                },
                                                failure: function (form, action) {
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


