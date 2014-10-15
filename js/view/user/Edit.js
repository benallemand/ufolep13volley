Ext.define('Ufolep13Volley.view.user.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.useredit',
    title: 'Utilisateur',
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
        url: 'ajax/saveUser.php',
        autoScroll: true,
        layout: 'anchor',
        items: [
            {
                xtype: 'hidden',
                name: 'id',
                fieldLabel: 'Id',
                msgTarget: 'under'
            },
            {
                name: 'login',
                fieldLabel: 'Login',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'password',
                fieldLabel: 'Password',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'email',
                fieldLabel: 'Email',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'id_team',
                xtype: 'combo',
                queryMode: 'local',
                fieldLabel: 'Equipe',
                store: 'Teams',
                displayField: 'team_full_name',
                valueField: 'id_equipe'
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