Ext.define('Ufolep13Volley.controller.mobile.Main', {
    extend: 'Ext.app.Controller',
    requires: [],
    config: {
        refs: {
            mainPanel: 'navigationview',
            formPanel: 'formpanel[url=login.php]',
            loginButton: 'button[action=login]'
        },
        control: {
            loginButton: {
                tap: 'login'
            }
        }
    },
    login: function() {
        var form = this.getFormPanel();
        form.submit({
            success: function () {
                location.reload();
            },
            failure: function(form, action) {
                Ext.Msg.alert('Erreur', action.result ? action.result.message : 'No response');
            }
        });
    }
}
);
