Ext.define('Ufolep13Volley.view.limitdate.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.limitdateedit',
    title: "Modification de la date limite",
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
        url: '/rest/action.php/limitdate/saveLimitDate',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id_date',
                name: 'id_date'
            },
            {
                xtype: 'combo',
                fieldLabel: 'Competition',
                name: 'code_competition',
                displayField: 'libelle',
                valueField: 'code_competition',
                store: 'Competitions',
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Date Limite',
                name: 'date_limite',
                allowBlank: false
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