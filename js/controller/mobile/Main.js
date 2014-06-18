Ext.define('Ufolep13Volley.controller.mobile.Main', {
    extend: 'Ext.app.Controller',
    requires: [],
    config: {
        refs: {
            mainPanel: 'navigationview',
            formPanel: 'formpanel'
        },
        control: {
            mainPanel: {
                show: 'showLogin'
            }
        }
    },
    showLogin: function() {
        this.getMainPanel().reset();
        var me = this;
        this.getMainPanel().push({
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            title: 'UFOLEP 13 VOLLEY',
            xtype: 'formpanel',
            url: 'ajax/login.php',
            items: [
                {
                    xtype: 'textfield',
                    name: 'login',
                    label: 'Login'
                },
                {
                    xtype: 'passwordfield',
                    name: 'password',
                    label: 'Mot de passe'
                },
                {
                    xtype: 'button',
                    text: 'Connexion',
                    action: 'connect',
                    handler: function() {
                        var form = me.getFormPanel();
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
            ]
        });
    }
}
);
