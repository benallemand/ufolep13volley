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
        url: '/rest/action.php/usermanager/saveUser',
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
                readOnly: true,
                msgTarget: 'under'
            },
            {
                name: 'email',
                fieldLabel: 'Email',
                allowBlank: false,
                msgTarget: 'under'
            },
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
                action: 'cancel',
            }
        ]
    }
});