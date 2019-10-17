Ext.define('Ufolep13Volley.store.BlacklistTeams', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.BlacklistTeams',
        proxy: {
            type: 'ajax',
            url: 'ajax/getBlacklistTeams.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
}));