Ext.define('Ufolep13Volley.view.team.ModifyPassword', {
    extend: 'Ext.window.Window',
    alias: 'widget.modifypassword',
    autoShow: true,
    title: "Modification du mot de passe",
    height: 400,
    width: 700,
    modal: true,
    layout: 'fit',
    items: {
        xtype: 'form',
        layout: 'anchor',
        defaults: {
            anchor: '90%',
            margins: 10
        },
        url: 'ajax/modifierMonMotDePasse.php',
        items: [
            {
                xtype: 'textfield',
                inputType: 'password',
                fieldLabel: 'Mot de passe',
                name: 'password',
                allowBlank: false
            },
            {
                xtype: 'textfield',
                inputType: 'password',
                fieldLabel: 'Mot de passe (vérification)',
                name: 'password2',
                allowBlank: false,
                validator: function(val) {
                    var formPanelPassword = Ext.ComponentQuery.query('form[url=ajax/modifierMonMotDePasse.php]')[0];
                    if (val !== formPanelPassword.getForm().findField('password').getValue()) {
                        return 'Merci de saisir 2 fois le même mot de passe !';
                    }
                    return true;
                }
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                handler: function() {
                    this.up('window').close();
                }
            },
            {
                text: 'Sauver',
                formBind: true,
                disabled: true,
                handler: function() {
                    var button = this;
                    var form = button.up('form').getForm();
                    if (form.isValid()) {
                        form.submit({
                            success: function () {
                                window.location.reload();
                            },
                            failure: function(form, action) {
                                Ext.Msg.alert('Erreur', action.result.message);
                            }
                        });
                    }
                }
            }
        ]
    }
});