Ext.define('Ufolep13Volley.store.MatchPlayers', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Player',
        proxy: {
            type: 'ajax',
            url: 'ajax/getMatchPlayers.php'
        }
    }
}));