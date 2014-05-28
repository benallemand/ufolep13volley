Ext.define('Ufolep13Volley.view.mobile.Tournaments', {
    extend: 'Ext.dataview.List',
    requires: [
        'Ext.dataview.List'
    ],
    xtype: 'listtournaments',
    config: {
        itemTpl: '{libelle}',
        store: 'Tournaments',
        flex: 1
    }
});
