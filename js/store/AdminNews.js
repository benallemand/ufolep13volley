Ext.define('Ufolep13Volley.store.AdminNews', {
    extend: 'Ext.data.Store',
    alias: 'store.AdminNews',
    config: {
        model: 'Ufolep13Volley.model.News',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/news/getAllNews',
            reader: {
                type: 'json'
            }
        },
        autoLoad: true
    }
});
