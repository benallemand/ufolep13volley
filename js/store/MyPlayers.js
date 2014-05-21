Ext.define('Ufolep13Volley.store.MyPlayers', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.Player',
    proxy: {
        type: 'ajax',
        url: 'ajax/getMyPlayers.php',
        reader: {
            type: 'json',
            root: 'results'
        }
    },
    autoLoad: true
});