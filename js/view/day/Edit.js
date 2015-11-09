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
        url: 'ajax/saveDay.php',
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
                store: 'Competitions',
                queryMode: 'local',
                allowBlank: false
            },
            {
                xtype: 'hidden',
                fieldLabel: 'Division',
                name: 'division',
                allowBlank: false,
                value: '1'
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
                xtype: 'textfield',
                fieldLabel: 'Libellé',
                name: 'libelle',
                allowBlank: false
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                handler: function () {
                    this.up('window').close();
                }
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