Ext.define('Ufolep13Volley.store.BlacklistDate', {
    extend: 'Ext.data.Store',
    alias: 'store.BlacklistDate',
    config: {
        model: 'Ufolep13Volley.model.BlacklistDate',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/blacklistdate/getBlacklistDate',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});