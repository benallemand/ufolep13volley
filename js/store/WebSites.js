Ext.define('Ufolep13Volley.store.WebSites', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.WebSite',
        proxy: {
            type: 'rest',
            url: 'ajax/getWebSites.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
}));