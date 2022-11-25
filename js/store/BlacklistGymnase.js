Ext.define('Ufolep13Volley.store.BlacklistGymnase', {
    extend: 'Ext.data.Store',
    alias: 'store.BlacklistGymnase',
    config: {
        model: 'Ufolep13Volley.model.BlacklistGymnase',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/blacklistcourt/getBlacklistGymnase',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});