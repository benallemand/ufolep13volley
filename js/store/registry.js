Ext.define('Ufolep13Volley.store.registry', {
    extend: 'Ext.data.Store',
    alias: 'store.registry',
    config: {
        model: 'Ufolep13Volley.model.registry',
        proxy: {
            type: 'rest',
            url: 'rest/action.php/registry/get'
        }
    }
});