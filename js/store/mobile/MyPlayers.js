Ext.define('Ufolep13Volley.store.mobile.MyPlayers', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.mobile.Player',
        proxy: {
            type: 'ajax',
            url: 'ajax/getMyPlayers.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});