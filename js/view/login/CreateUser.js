Ext.define('Ufolep13Volley.view.login.CreateUser', {
    extend: 'Ext.window.Window',
    alias: 'widget.createuser',
    title: 'Nouvel utilisateur',
    layout: 'fit',
    modal: true,
    width: 700,
    height: 500,
    autoShow: true,
    items: {
        xtype: 'form',
        trackResetOnLoad: true,
        defaults: {
            xtype: 'textfield',
            anchor: '90%'
        },
        url: 'ajax/createUser.php',
        autoScroll: true,
        layout: 'anchor',
        items: [
            {
                xtype: 'textfield',
                name: 'login',
                fieldLabel: 'Choisir un identifiant',
                allowBlank: false,
                maxLength: 200,
                vtype: 'alphanum'
            },
            {
                xtype: 'textfield',
                name: 'email',
                fieldLabel: 'Adresse Email',
                allowBlank: false,
                vtype: 'email'
            },
            {
                xtype: 'combo',
                name: 'id_equipe',
                fieldLabel: 'Equipe de rattachement',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'Teams'
            }
        ],
        buttons: [
            {
                text: 'Sauver',
                action: 'save',
                formBind: true,
                disabled: true
            },
            {
                text: 'Annuler',
                handler: function() {
                    this.up('window').close();
                }
            }
        ]
    }
});