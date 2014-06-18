Ext.define('Ufolep13Volley.view.mobile.MyPlayers', {
    extend: 'Ext.dataview.List',
    requires: [
        'Ext.dataview.List'
    ],
    xtype: 'listmyplayers',
    config: {
        itemTpl: "<img src='{path_photo}' width='80px'/>{full_name}",
        store: 'MyPlayers',
        flex: 1
    }
});
