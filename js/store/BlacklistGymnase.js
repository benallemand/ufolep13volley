Ext.define('Ufolep13Volley.store.BlacklistGymnase', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.BlacklistGymnase',
        proxy: {
            type: 'ajax',
            url: 'ajax/getBlacklistGymnase.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});