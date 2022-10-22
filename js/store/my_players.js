Ext.define('Ufolep13Volley.store.my_players', {
    extend: 'Ext.data.Store',
    alias: 'store.my_players',
    config: {
        model: 'Ufolep13Volley.model.Player',
        proxy: {
            type: 'rest',
            url: 'rest/action.php/player/getMyPlayers'
        },
        autoLoad: true
    }
});