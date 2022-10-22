Ext.define('Ufolep13Volley.view.profile.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.profileedit',
    title: 'Profile',
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
        url: '/rest/action.php/usermanager/saveProfile',
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
                name: 'name',
                fieldLabel: 'Nom',
                allowBlank: false,
                msgTarget: 'under'
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
                action: 'cancel',
            }
        ]
    }
});