Ext.define('Ufolep13Volley.store.commission', {
    extend: 'Ext.data.Store',
    alias: 'store.commission',
    config: {
        model: 'Ufolep13Volley.model.commission',
        proxy: {
            type: 'rest',
            url: 'rest/action.php/commission/get'
        }
    }
});