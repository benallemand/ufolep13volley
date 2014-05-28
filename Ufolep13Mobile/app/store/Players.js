Ext.define('Ufolep13.store.Players', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13.model.Player',
        proxy: {
            type: 'ajax',
            url: '../ajax/getPlayersFromTeam.php'
        },
        autoLoad: false
    }
});