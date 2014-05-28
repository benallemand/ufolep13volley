Ext.define('Ufolep13.view.Players', {
    extend: 'Ext.dataview.List',
    requires: [
        'Ext.dataview.List'
    ],
    xtype: 'listplayers',
    config: {
        itemTpl: "<img src='../{path_photo}' width='80px'/>{full_name}",
        store: 'Players',
        flex: 1
    }
});
