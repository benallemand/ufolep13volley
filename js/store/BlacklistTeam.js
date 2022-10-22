Ext.define('Ufolep13Volley.store.BlacklistTeam', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.BlacklistTeam',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/blacklistteam/getBlacklistTeam',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});