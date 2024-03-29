Ext.define('Ufolep13Volley.view.day.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.dayedit',
    title: "Modification de la journée",
    height: 400,
    width: 700,
    modal: true,
    layout: 'fit',
    autoShow: true,
    items: {
        xtype: 'form',
        trackResetOnLoad: true,
        layout: 'anchor',
        defaults: {
            anchor: '90%',
            margins: 10
        },
        url: '/rest/action.php/day/save_day',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id',
                name: 'id'
            },
            {
                xtype: 'combo',
                fieldLabel: 'Competition',
                name: 'code_competition',
                displayField: 'libelle',
                valueField: 'code_competition',
                store: {type: 'Competitions'},
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                xtype: 'numberfield',
                fieldLabel: 'Numéro',
                name: 'numero',
                allowBlank: false,
                minValue: 0
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Nommage',
                name: 'nommage',
                allowBlank: false
            },
            {
                xtype: 'datefield',
                fieldLabel: 'Premier jour de la semaine',
                name: 'start_date',
                allowBlank: false,
                startDay: 1,
                disabledDays: [2,3,4,5,6,0],
                format: 'd/m/Y'
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                action: 'cancel',
            },
            {
                text: 'Sauver',
                formBind: true,
                disabled: true,
                action: 'save'
            }
        ]
    }
});