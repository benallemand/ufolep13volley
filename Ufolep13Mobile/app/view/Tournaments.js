Ext.define('Ufolep13Mobile.view.Tournaments', {
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
