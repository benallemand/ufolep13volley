var store_notes_configuration = {
    fields: ['value', 'label'],
    data: [
        {"value": 0, "label": "0 - Sans avis"},
        {"value": 1, "label": "1 - Très déçu !"},
        {"value": 2, "label": "2 - Déçu"},
        {"value": 3, "label": "3 - Moyen"},
        {"value": 4, "label": "4 - Cool"},
        {"value": 5, "label": "5 - Top !"},
    ]
};
Ext.define('Ufolep13Volley.view.form.survey', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_survey',
    layout: 'form',
    url: 'rest/action.php/matchmgr/save_survey',
    trackResetOnLoad: true,
    defaults: {
        xtype: 'textfield',
        anchor: '95%',
        margin: '5 0 5 0',
    },
    autoScroll: true,
    items: [
        {
            xtype: 'hidden',
            name: 'id'
        },
        {
            xtype: 'hidden',
            name: 'id_match'
        },
        {
            xtype: 'displayfield',
            name: 'login',
            fieldLabel: "Compte utilisateur"
        },
        {
            xtype: 'combo',
            name: 'on_time',
            fieldLabel: "Ponctualité",
            store: Ext.create('Ext.data.Store', store_notes_configuration),
            displayField: 'label',
            valueField: 'value',
            forceSelection: true,
        },
        {
            xtype: 'combo',
            name: 'spirit',
            fieldLabel: "Etat d'esprit",
            store: Ext.create('Ext.data.Store', store_notes_configuration),
            displayField: 'label',
            valueField: 'value',
            forceSelection: true,
        },
        {
            xtype: 'combo',
            name: 'referee',
            fieldLabel: "Arbitrage",
            store: Ext.create('Ext.data.Store', store_notes_configuration),
            displayField: 'label',
            valueField: 'value',
            forceSelection: true,
        },
        {
            xtype: 'combo',
            name: 'catering',
            fieldLabel: "Apéro",
            store: Ext.create('Ext.data.Store', store_notes_configuration),
            displayField: 'label',
            valueField: 'value',
            forceSelection: true,
        },
        {
            xtype: 'combo',
            name: 'global',
            fieldLabel: "Impression globale",
            store: Ext.create('Ext.data.Store', store_notes_configuration),
            displayField: 'label',
            valueField: 'value',
            forceSelection: true,
        },
        {
            xtype: 'textarea',
            name: 'comment',
            fieldLabel: 'Commentaire',
        },
    ],
    buttons: [
        {
            text: 'Enregistrer le sondage',
            formBind: true,
            disabled: true,
            action: 'save',
            iconCls: 'fa-solid fa-floppy-disk',
        },
    ]
});