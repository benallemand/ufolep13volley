Ext.define('Ufolep13Volley.store.BlacklistByCity', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.BlacklistByCity',
        proxy: {
            type: 'ajax',
            url: 'ajax/get_blacklist_by_city.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});