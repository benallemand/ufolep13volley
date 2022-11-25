Ext.define('Ufolep13Volley.store.BlacklistTeams', {
    extend: 'Ext.data.Store',
    alias: 'store.BlacklistTeams',
    config: {
        model: 'Ufolep13Volley.model.BlacklistTeams',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/blacklistteams/getBlacklistTeams',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});