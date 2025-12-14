Ext.define('Ufolep13Volley.view.form.registry', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_registry',
    layout: 'form',
    url: 'rest/action.php/registry/save_with_args',
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
            name: 'id',
            fieldLabel: 'Id',
            msgTarget: 'under'
        },
        {name: 'registry_key', fieldLabel: "clé", allowBlank: true,},
        {name: 'registry_value', fieldLabel: "valeur", allowBlank: true,},
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