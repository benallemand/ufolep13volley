Ext.define('Ufolep13Volley.store.Players', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    alias: 'store.Players',
    config: {
        model: 'Ufolep13Volley.model.Player',
        proxy: {
            type: 'ajax',
            url: 'ajax/getPlayers.php'
        },
        autoLoad: true
    }
}));