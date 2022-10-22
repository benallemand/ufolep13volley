Ext.define('Ufolep13Volley.store.BlacklistByCity', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.BlacklistByCity',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/competition/get_blacklist_by_city',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});