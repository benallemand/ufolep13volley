Ext.define('Ufolep13Volley.view.window.HallOfFame', {
    extend: 'Ext.window.Window',
    alias: 'widget.hall_of_fame_edit',
    title: "Edition de Palmarès",
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
        url: 'ajax/saveHallOfFame.php',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id',
                name: 'id'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Titre',
                name: 'title',
                allowBlank: false
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Equipe',
                name: 'team_name',
                allowBlank: false
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Année',
                name: 'period',
                allowBlank: false
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Catégorie',
                name: 'league',
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