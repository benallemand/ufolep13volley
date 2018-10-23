Ext.define('Ufolep13Volley.view.window.BlacklistGymnase', {
    extend: 'Ext.window.Window',
    alias: 'widget.blacklistgymnase_edit',
    title: "Edition de date interdite pour les matchs",
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
        url: 'ajax/saveBlacklistGymnase.php',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id',
                name: 'id'
            },
            {
                xtype: 'combo',
                fieldLabel: 'Gymnase',
                name: 'id_gymnase',
                displayField: 'full_name',
                valueField: 'id',
                store: 'Gymnasiums',
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
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