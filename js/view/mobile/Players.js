Ext.define('Ufolep13Volley.view.mobile.Players', {
    extend: 'Ext.dataview.List',
    requires: [
        'Ext.dataview.List'
    ],
    xtype: 'listplayers',
    config: {
        title : 'Joueurs',
        itemTpl: "<table><tr><td><img src='{path_photo}' width='80px'/></td><td>{nom}<br/>{prenom}<br/>{num_licence}</td></tr></table>",
        store: 'TeamPlayers',
        flex: 1
    }
});
