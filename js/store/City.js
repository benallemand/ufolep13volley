Ext.define('Ufolep13Volley.store.City', {
    extend: 'Ext.data.Store',
    alias: 'store.City',
    config: {
        model: 'Ufolep13Volley.model.City',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/competition/get_city',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});