Ext.define('Ufolep13Volley.controller.Login', {
    extend: 'Ext.app.Controller',
    stores: ['Teams'],
    models: [],
    views: ['login.CreateUser'],
    refs: [
        {
            ref: 'CreateUserWindow',
            selector: "createuser"
        },
        {
            ref: 'CreateUserForm',
            selector: "createuser form"
        }
    ],
    init: function() {
        this.control(
                {
                    'button[action=showUserRegistration]': {
                        click: this.showUserRegistration
                    },
                    'createuser button[action=save]': {
                        click: this.createUser
                    }
                }
        );
    },
    showUserRegistration: function() {
        Ext.widget('createuser');
    },
    createUser: function() {
        var me = this;
        var form = this.getCreateUserForm().getForm();
        if (form.isValid()) {
            form.submit({
                success: function() {
                    Ext.Msg.alert('Inscription OK', 'Vous devriez recevoir un email indiquant vos identifiants de connexion. Bienvenue sur le site UFOLEP Volley 13 !');
                    me.getCreateUserWindow().close();
                },
                failure: function(form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    }
});