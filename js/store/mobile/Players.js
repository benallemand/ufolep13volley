Ext.define('Ufolep13Volley.store.mobile.Players', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.mobile.Player',
        proxy: {
            type: 'ajax',
            url: 'ajax/getPlayersFromTeam.php'
        },
        autoLoad: false
    }
});