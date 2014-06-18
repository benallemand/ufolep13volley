Ext.define('Ufolep13Volley.store.WebSites', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.WebSite',
    proxy: {
        type: 'rest',
        url: 'ajax/getWebSites.php',
        reader: {
            type: 'json',
            root: 'results'
        }
    },
    autoLoad: true
});