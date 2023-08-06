Ext.define('Ufolep13Volley.view.form.reset_password', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_reset_password',
    title: title,
    layout: 'form',
    url: 'rest/action.php/usermanager/request_reset_password',
    trackResetOnLoad: true,
    defaults: {
        xtype: 'textfield',
        margin: 10,
    },
    autoScroll: true,
    items: [
        {
            xtype: 'combo_team',
            fieldLabel: "Nom de l'équipe",
            allowBlank: false,
            name: 'user_team_id',
        },
        {
            name: 'user_email',
            fieldLabel: "Email",
            allowBlank: false,
            msgTarget: 'under',
            vtype: 'email',
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
});