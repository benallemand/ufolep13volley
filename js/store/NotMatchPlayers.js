Ext.define('Ufolep13Volley.store.NotMatchPlayers', {
    extend: 'Ext.data.Store',
    alias: 'store.NotMatchPlayers',
    config: {
        model: 'Ufolep13Volley.model.Player',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/matchmgr/getNotMatchPlayers'
        }
    }
});