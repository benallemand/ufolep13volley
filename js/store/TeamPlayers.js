Ext.define('Ufolep13Volley.store.TeamPlayers', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Player',
        proxy: {
            type: 'ajax',
            url: 'ajax/getPlayersFromTeam.php'
        },
        autoLoad: false}
}));