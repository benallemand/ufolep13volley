Ext.define('Ufolep13Volley.view.mobile.MyPlayers', {
    extend: 'Ext.dataview.List',
    requires: [
        'Ext.dataview.List'
    ],
    xtype: 'listmyplayers',
    config: {
        title : 'Mon Equipe',
        itemTpl: "<table><tr><td><img src='{path_photo}' width='80px'/></td><td>{nom}<br/>{prenom}<br/>{num_licence}</td></tr></table>",
        store: 'MyPlayers',
        flex: 1
    }
});
