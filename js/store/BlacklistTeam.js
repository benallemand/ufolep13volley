Ext.define('Ufolep13Volley.store.BlacklistTeam', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.BlacklistTeam',
        proxy: {
            type: 'ajax',
            url: 'ajax/getBlacklistTeam.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
}));