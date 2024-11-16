Ext.define('Ufolep13Volley.view.form.commission', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_commission',
    layout: 'form',
    url: 'rest/action.php/commission/save_with_args',
    trackResetOnLoad: true,
    defaults: {
        xtype: 'textfield',
        margin: 10,
        anchor: '100%'
    },
    autoScroll: true,
    items: [
        {
            xtype: 'hidden',
            name: 'id_commission',
            fieldLabel: 'Id',
            msgTarget: 'under'
        },
        {name: 'nom', fieldLabel: "nom", allowBlank: true,},
        {name: 'prenom', fieldLabel: "prenom", allowBlank: true,},
        {name: 'fonction', fieldLabel: "fonction", allowBlank: true,},
        {name: 'telephone1', fieldLabel: "telephone1", allowBlank: true,},
        {name: 'telephone2', fieldLabel: "telephone2", allowBlank: true,},
        {name: 'email', fieldLabel: "email", allowBlank: true,},
        {name: 'photo', fieldLabel: "photo", allowBlank: true,},
        {name: 'type', fieldLabel: "type", allowBlank: true,},
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