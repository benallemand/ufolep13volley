Ext.define('Ufolep13Volley.store.ReinforcementPlayers', {
    extend: 'Ufolep13Volley.store.Ufolep',
    alias: 'store.ReinforcementPlayers',
    config: {
        model: 'Ufolep13Volley.model.Player',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/matchmgr/getReinforcementPlayers'
        },
        autoLoad: false,
    }
});