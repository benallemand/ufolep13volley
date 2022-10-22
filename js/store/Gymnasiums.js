Ext.define('Ufolep13Volley.store.Gymnasiums', {
    extend: 'Ext.data.Store',
    alias: 'store.Gymnasiums',
    config: {
        model: 'Ufolep13Volley.model.Gymnasium',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/court/getGymnasiums'
        },
        autoLoad: true
    }
});