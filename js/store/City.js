Ext.define('Ufolep13Volley.store.City', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.City',
        proxy: {
            type: 'ajax',
            url: 'ajax/get_city.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});