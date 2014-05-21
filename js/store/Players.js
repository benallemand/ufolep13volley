Ext.define('Ufolep13Volley.store.Players', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.Player',
    proxy: {
        type: 'ajax',
        url: 'ajax/getPlayers.php'
    },
    autoLoad: true
});