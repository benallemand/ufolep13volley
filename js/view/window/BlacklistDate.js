Ext.define('Ufolep13Volley.view.window.BlacklistDate', {
    extend: 'Ext.window.Window',
    alias: 'widget.blacklistdate_edit',
    title: "Saisie de date interdite pour tous les gymnases",
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
        url: 'ajax/saveBlacklistDate.php',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id',
                name: 'id'
            },
            {
                xtype: 'datefield',
                fieldLabel: 'Date interdite',
                name: 'closed_date',
                allowBlank: false,
                startDay: 1,
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