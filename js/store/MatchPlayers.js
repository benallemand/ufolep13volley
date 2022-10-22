Ext.define('Ufolep13Volley.store.MatchPlayers', {
    extend: 'Ext.data.Store',
    alias: 'store.MatchPlayers',
    config: {
        model: 'Ufolep13Volley.model.Player',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/matchmgr/getMatchPlayers'
        }
    }
});