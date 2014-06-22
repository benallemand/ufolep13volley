Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['login.AutoCompleteField'],
    controllers: [],
    stores: [],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.form.Panel', {
            title: 'Connexion',
            renderTo: Ext.get('login'),
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
                    action: 'showUserRegistration'
                }
            ]
        });
    }
});


