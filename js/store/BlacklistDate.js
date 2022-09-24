Ext.define('Ufolep13Volley.store.BlacklistDate', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.BlacklistDate',
        proxy: {
            type: 'ajax',
            url: 'ajax/getBlacklistDate.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});